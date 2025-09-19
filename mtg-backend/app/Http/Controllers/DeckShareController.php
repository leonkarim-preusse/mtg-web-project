<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\DeckCard;
use App\Services\MtgApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeckShareController extends Controller
{
    // Toggle sharing on/off and optionally regenerate token
    public function update(Request $request, Deck $deck)
    {
        abort_unless($deck->owner_id === Auth::id(), 403);
        $data = $request->validate([
            'enabled' => ['required','boolean'],
            'regenerate' => ['sometimes','boolean'],
        ]);
        if (!empty($data['regenerate'])) {
            $deck->share_token = bin2hex(random_bytes(16));
        }
        $deck->share_enabled = (bool) $data['enabled'];
        if ($deck->share_enabled && empty($deck->share_token)) {
            $deck->share_token = bin2hex(random_bytes(16));
        }
        $deck->save();
        return response()->json(['ok'=>true, 'share_enabled'=>$deck->share_enabled, 'share_token'=>$deck->share_token]);
    }

    // Export deck list in simple text format
    public function export(Deck $deck)
    {
        abort_unless($deck->owner_id === Auth::id(), 403);
        $rows = DeckCard::where('deck_id', $deck->id)->orderBy('mtg_card_id')->get(['mtg_card_id','quantity']);
        // Format: "<qty> <card name>" per line
        $lines = ["// {$deck->name}"];
        $api = app(MtgApi::class);
        $cards = $api->resolveCardsByIds($rows->pluck('mtg_card_id')->all());
        $byId = collect($cards)->keyBy(fn($c) => (string)$c['id']);
        foreach ($rows as $r) {
            $name = $byId[(string)$r->mtg_card_id]['name'] ?? (string)$r->mtg_card_id;
            $lines[] = $r->quantity . ' ' . $name;
        }
        $text = implode("\n", $lines) . "\n";
        return response($text, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.preg_replace('/[^a-z0-9_\-]+/i','_', $deck->name).'_export.txt"',
        ]);
    }

    // Public read-only deck view by token
    public function showPublic(string $token)
    {
        $deck = Deck::where('share_enabled', true)->where('share_token', $token)->firstOrFail();
        $rows = DeckCard::where('deck_id', $deck->id)->get(['mtg_card_id','quantity']);
        $ids = $rows->pluck('mtg_card_id')->all();
        $cards = app(MtgApi::class)->resolveCardsByIds($ids);
        $byId = collect($cards)->keyBy(fn($c) => (string)$c['id']);
        $map = collect($rows)->map(function ($r) use ($byId) {
            $card = $byId[(string)$r->mtg_card_id] ?? ['id'=>$r->mtg_card_id, 'name'=>$r->mtg_card_id, 'imageUrl'=>null];
            return ['card'=>$card, 'quantity'=>$r->quantity];
        });
        return view('decks.share', ['deck'=>$deck, 'items'=>$map]);
    }

    // Import deck lines ("<qty> <card name>")
    public function import(Request $request, Deck $deck)
    {
        abort_unless($deck->owner_id === Auth::id(), 403);
        $data = $request->validate([
            'list' => ['required','string'],
        ]);
        $lines = preg_split("/\r?\n/", trim($data['list']));
        $api = app(MtgApi::class);
        $added = 0; $errors = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '//')) continue;
            if (!preg_match('/^(\d+)\s+(.+)$/', $line, $m)) { $errors[] = "Unrecognized: $line"; continue; }
            $qty = max(1, (int)$m[1]);
            $name = trim($m[2]);
            $id = $api->resolveIdByExactName($name);
            if (!$id) { $errors[] = "Not found: $name"; continue; }
            $row = DeckCard::where('deck_id',$deck->id)->where('mtg_card_id',$id)->first();
            if ($row) { $row->quantity += $qty; $row->save(); }
            else { DeckCard::create(['deck_id'=>$deck->id, 'mtg_card_id'=>$id, 'quantity'=>$qty]); }
            $added += $qty;
        }
        return back()->with('status', "Imported $added cards." . (count($errors) ? ' Errors: '.implode(' | ', $errors) : ''));
    }

    // Import list as a new deck (JSON)
    public function importNew(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'list' => ['required','string'],
        ]);
        $ownerId = Auth::id();
        if (!$ownerId) return response()->json(['ok'=>false, 'error'=>'unauthorized'], 401);

        // Name conflict check
        $exists = Deck::where('owner_id', $ownerId)->where('name', $data['name'])->exists();
        if ($exists) {
            return response()->json(['ok'=>false, 'error'=>'name_conflict'], 409);
        }

        // Create deck
        $deck = Deck::create(['owner_id'=>$ownerId, 'name'=>$data['name']]);

        // Parse and add cards
        $lines = preg_split("/\r?\n/", trim($data['list']));
        $api = app(MtgApi::class);
        $added = 0; $errors = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '//')) continue;
            if (!preg_match('/^(\d+)\s+(.+)$/', $line, $m)) { $errors[] = "Unrecognized: $line"; continue; }
            $qty = max(1, (int)$m[1]);
            $name = trim($m[2]);
            $id = $api->resolveIdByExactName($name);
            if (!$id) { $errors[] = "Not found: $name"; continue; }
            $row = DeckCard::where('deck_id',$deck->id)->where('mtg_card_id',$id)->first();
            if ($row) { $row->quantity += $qty; $row->save(); }
            else { DeckCard::create(['deck_id'=>$deck->id, 'mtg_card_id'=>$id, 'quantity'=>$qty]); }
            $added += $qty;
        }

        return response()->json(['ok'=>true, 'deck'=>['id'=>$deck->id, 'name'=>$deck->name], 'added'=>$added, 'errors'=>$errors], 201);
    }
}
