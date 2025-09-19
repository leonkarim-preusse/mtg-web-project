<?php


namespace App\Http\Controllers;

use App\Models\Deck;
use App\Models\DeckCard;
use App\Services\MtgApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeckController extends Controller
{
    // List user's decks with counts and a preview image
    public function index(MtgApi $mtg)
    {
        $decks = Deck::where('owner_id', Auth::id())->orderBy('name')->get();

        $deckCounts = [];
        $deckImages = [];

        if ($decks->isNotEmpty()) {
            $deckIds = $decks->pluck('id')->all();

            $counts = DeckCard::selectRaw('deck_id, SUM(quantity) as total')
                ->whereIn('deck_id', $deckIds)
                ->groupBy('deck_id')
                ->get();
            foreach ($counts as $c) {
                $deckCounts[(string)$c->deck_id] = (int) $c->total;
            }

            // Pick one random mtg_card_id per deck
            $randomPerDeck = [];
            foreach ($deckIds as $did) {
                $row = DeckCard::where('deck_id', $did)->inRandomOrder()->limit(1)->value('mtg_card_id');
                if ($row) $randomPerDeck[(string)$did] = (string)$row;
            }

            if (!empty($randomPerDeck)) {
                $ids = array_values($randomPerDeck);
                $cards = $mtg->resolveCardsByIds($ids);
                $byId = [];
                foreach ($cards as $c) $byId[(string)$c['id']] = $c;
                foreach ($randomPerDeck as $did => $cid) {
                    $deckImages[$did] = $byId[$cid]['imageArtUrl'] ?? ($byId[$cid]['imageUrl'] ?? null);
                }
            }
        }

        return view('decks.index', compact('decks', 'deckCounts', 'deckImages'));
    }

    // Show a deck with resolved card details
    public function show(Deck $deck, MtgApi $mtg)
    {
    abort_unless($deck->owner_id === Auth::id(), 403);

    $rows = DeckCard::where('deck_id', $deck->id)->get(['mtg_card_id','quantity']);
    $ids = $rows->pluck('mtg_card_id')->all();
    $cards = $mtg->resolveCardsByIds($ids);
    // Index by id
        $byId = [];
        foreach ($cards as $c) $byId[(string)$c['id']] = $c;

        $items = [];
        foreach ($rows as $r) {
            $card = $byId[(string)$r->mtg_card_id] ?? ['id'=>$r->mtg_card_id, 'name'=>$r->mtg_card_id, 'imageUrl'=>null];
            $items[] = ['card' => $card, 'quantity' => (int) $r->quantity];
        }

        return view('decks.show', compact('deck', 'items'));
    }
}