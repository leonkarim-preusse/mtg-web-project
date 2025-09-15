<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CardSearchPageController;
use App\Http\Controllers\Api\CardsController as ApiCardsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CardLookupController;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->middleware('auth');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Placeholder pages (you can replace with controllers later)
    Route::get('/decks', fn() => view('decks.index'))->name('decks.index');
    Route::get('/favorites', fn() => view('favorites.index'))->name('favorites.index');
});

// Card Search UI
Route::get('/cards', [CardSearchPageController::class, 'show'])->name('cards.search');

// Cards API (JSON)
Route::get('/api/cards', [ApiCardsController::class, 'index'])->name('api.cards.index');

// Global search (public): finds cards and (if logged in) user's decks
Route::get('/search', [SearchController::class, 'global'])->name('search.global');

// Favorites API (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/api/favorites', [FavoriteController::class, 'index'])->name('api.favorites.index');
    Route::post('/api/favorites/toggle', [FavoriteController::class, 'toggle'])->name('api.favorites.toggle');
});

// Card Lookup API
Route::get('/api/cards/resolve', [CardLookupController::class, 'resolve'])->name('api.cards.resolve');
