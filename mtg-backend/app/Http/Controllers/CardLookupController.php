<?php

namespace App\Http\Controllers;

use App\Services\MtgApi;
use Illuminate\Http\Request;

class CardLookupController extends Controller
{
    public function __construct(private MtgApi $mtg) {}

    public function resolve(Request $request)
    {
        $ids = collect(explode(',', (string)$request->query('ids', '')))
            ->map(fn($s) => trim($s))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return response()->json(['cards' => []]);
        }

        $cards = $this->mtg->resolveCardsByIds($ids);
        return response()->json(['cards' => array_values($cards)]);
    }
}