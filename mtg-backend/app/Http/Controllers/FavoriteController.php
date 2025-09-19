<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FavoriteController extends Controller
{
    // List favorite card IDs for current user
    public function index()
    {
        $ids = Favorite::where('user_id', Auth::id())->pluck('card_id')->all();
        return response()->json(['ids' => $ids], 200);
    }

    // Toggle favorite for a card ID
    public function toggle(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|string|max:191',
        ]);

        $uid = Auth::id();
        $cid = (string) $data['id'];

        try {
            $existing = Favorite::where('user_id', $uid)->where('card_id', $cid)->first();

            if ($existing) {
                $existing->delete();
                return response()->json(['favorited' => false], 200);
            }

            Favorite::create(['user_id' => $uid, 'card_id' => $cid]);
            return response()->json(['favorited' => true], 201);
        } catch (\Throwable $e) {
            Log::error('Favorite toggle failed', ['user_id' => $uid, 'card_id' => $cid, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to toggle favorite'], 500);
        }
    }
}