<?php

namespace App\Http\Controllers;

use App\Models\Deck;
use App\Services\MtgApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    public function __construct(private MtgApi $mtg) {}

    // Global search: searches cards by name and user's decks by name
    public function global(Request $request)
    {
        $validated = $request->validate([
            'q'        => 'nullable|string|max:255',
            'page'     => 'nullable|integer|min:1',
            'pageSize' => 'nullable|integer|min:1|max:100',
        ]);

        $q = trim((string)($validated['q'] ?? ''));
        $page = (int)($validated['page'] ?? 1);
        $pageSize = (int)($validated['pageSize'] ?? 30);
        $unique = $request->boolean('unique', true);

        $cards = [];
        if ($q !== '') {
            try {
                $cards = $this->mtg->searchCards([
                    'name'     => $q,
                    'page'     => $page,
                    'pageSize' => $pageSize,
                    'orderBy'  => 'name',
                    'dir'      => 'asc',
                ]);
                if ($unique) {
                    $cards = $this->mtg->dedupeByNamePreferImage($cards);
                }
            } catch (\Throwable $e) {
                Log::warning('Global search failed (cards)', ['error' => $e->getMessage()]);
                $cards = [];
            }
        }

        // Search user's decks (if logged in)
        $decks = collect();
        if (Auth::check() && $q !== '') {
            $decks = Deck::query()
                ->where('owner_id', Auth::id())
                ->where('name', 'like', '%' . $q . '%')
                ->orderBy('name')
                ->limit(20)
                ->get(['id', 'name', 'owner_id']);
        }

        return view('search.results', [
            'q'        => $q,
            'cards'    => $cards,
            'decks'    => $decks,
            'page'     => $page,
            'pageSize' => $pageSize,
            'unique'   => $unique,
        ]);
    }
}