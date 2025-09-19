<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MtgApi;
use Illuminate\Http\Request;

class CardsController extends Controller
{
    // List/search cards (JSON)
    public function index(Request $request, MtgApi $mtg)
    {
        // Accept known filters from the advanced form
        $params = $request->only([
            'name','types','subtypes','colors','rarity','setOrName','text',
            'page','pageSize','cmc','power','toughness','loyalty','colorIdentity',
            'orderBy','dir'
        ]);

        $cards = $mtg->searchCards($params);
        return response()->json(['cards' => $cards], 200);
    }
}