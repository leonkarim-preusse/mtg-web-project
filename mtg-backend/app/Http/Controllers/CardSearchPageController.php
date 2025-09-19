<?php

namespace App\Http\Controllers;

class CardSearchPageController extends Controller
{
    // Render card search page
    public function show()
    {
        return view('cards.search');
    }
}