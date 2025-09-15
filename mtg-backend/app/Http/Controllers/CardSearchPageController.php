<?php

namespace App\Http\Controllers;

class CardSearchPageController extends Controller
{
    public function show()
    {
        return view('cards.search');
    }
}