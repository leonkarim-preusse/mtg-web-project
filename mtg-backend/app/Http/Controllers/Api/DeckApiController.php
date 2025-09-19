<?php


namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deck;
use App\Models\DeckCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeckApiController extends Controller
{
    // List user's decks (JSON)
    public function index()
    {
    $decks = Deck::query()->where('owner_id', Auth::id())->orderBy('name')->get(['id','name']);
        return response()->json(['decks' => $decks]);
    }

    // Create a new deck
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:1|max:191',
        ]);

        $deck = Deck::create(['owner_id' => Auth::id(), 'name' => $data['name']]);

        return response()->json(['deck' => ['id' => $deck->id, 'name' => $deck->name]], 201);
    }

    // Add or increment a card in a deck
    public function add(Request $request, Deck $deck)
    {
    abort_unless($deck->owner_id === Auth::id(), 403);

        $data = $request->validate([
            'card_id' => 'required|string|max:191',
            'qty'     => 'nullable|integer|min:1',
        ]);
        $qty = (int) ($data['qty'] ?? 1);

        $dc = DeckCard::query()
            ->where('deck_id', $deck->id)
            ->where('mtg_card_id', $data['card_id'])
            ->first();

        if ($dc) {
            $dc->quantity = (int) $dc->quantity + $qty;
            $dc->save();
        } else {
            DeckCard::create([
                'deck_id'    => $deck->id,
                'mtg_card_id'=> (string) $data['card_id'],
                'quantity'   => $qty,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    // Remove or decrement a card from a deck
    public function remove(Request $request, Deck $deck)
    {
    abort_unless($deck->owner_id === Auth::id(), 403);

        $data = $request->validate([
            'card_id' => 'required|string|max:191',
            'qty'     => 'nullable|integer|min:1',
        ]);
        $qty = (int) ($data['qty'] ?? 1);

        $dc = DeckCard::query()
            ->where('deck_id', $deck->id)
            ->where('mtg_card_id', $data['card_id'])
            ->first();

        if (!$dc) return response()->json(['ok' => true]);

        $newQty = (int) $dc->quantity - $qty;
        if ($newQty > 0) {
            $dc->quantity = $newQty;
            $dc->save();
        } else {
            $dc->delete();
        }

        return response()->json(['ok' => true]);
    }
}