<?php

namespace App\Http\Controllers;

use App\Services\MtgApi;
use Illuminate\Http\Request;

class CardsController extends Controller
{
    public function __construct(private MtgApi $mtg) {}
    // Search cards API (JSON)
    public function search(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'nullable|string',
            'colors'       => 'nullable|string',
            'types'        => 'nullable|string',
            'supertypes'   => 'nullable|string',
            'subtypes'     => 'nullable|string',
            'rarity'       => 'nullable|string',
            'setOrName'    => 'nullable|string',
            'set'          => 'nullable|string',
            'setName'      => 'nullable|string',
            'text'         => 'nullable|string',
            'cmc'          => 'nullable|string',
            'power'        => 'nullable|string',
            'toughness'    => 'nullable|string',
            'loyalty'      => 'nullable|string',
            'colorIdentity'=> 'nullable|string',
            'page'         => 'nullable|integer|min:1',
            'pageSize'     => 'nullable|integer|min:1|max:100',
            'orderBy'      => 'nullable|string',
            'dir'          => 'nullable|in:asc,desc',
        ]);

        $query = array_merge($request->query(), $validated);

        // Map setOrName to set or setName
        if (!empty($query['setOrName'])) {
            $val = trim((string)$query['setOrName']);
            if ($this->looksLikeSetCode($val)) {
                $query['set'] = strtoupper($val);
                unset($query['setName']);
            } else {
                $query['setName'] = $val;
                unset($query['set']);
            }
            unset($query['setOrName']);
        }

        $cards = $this->mtg->searchCards($query);

        // Map common UI fields
        $mapped = array_map(function (array $c) {
            return [
                'id'        => $c['id'] ?? null,
                'name'      => $c['name'] ?? '',
                'imageUrl'  => $c['imageUrl'] ?? null,
                'manaCost'  => $c['manaCost'] ?? null,
                'cmc'       => $c['cmc'] ?? null,
                'types'     => $c['types'] ?? [],
                'rarity'    => $c['rarity'] ?? null,
                'colors'    => $c['colors'] ?? [],
                'set'       => $c['set'] ?? null,
                'setName'   => $c['setName'] ?? null,
                'text'      => $c['text'] ?? null,
                'power'     => $c['power'] ?? null,
                'toughness' => $c['toughness'] ?? null,
            ];
        }, $cards);

        return response()->json([
            'cards' => $mapped,
            // No total count provided by upstream
            'page' => (int) ($request->query('page', 1)),
        ]);
    }
    // Render search page (Blade)
    public function page()
    {
        return view('cards.search');
    }

    // Heuristic: detect set code shape
    private function looksLikeSetCode(string $v): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9]{2,5}$/', trim($v));
    }
}