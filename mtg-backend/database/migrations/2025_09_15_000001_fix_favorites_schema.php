<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // SQLite safety
        DB::statement('PRAGMA foreign_keys = OFF');
        Schema::dropIfExists('favorites_tmp'); // ensure we can recreate it

        // Desired schema: id, user_id, card_id (string), unique(user_id, card_id)
        if (!Schema::hasTable('favorites')) {
            Schema::create('favorites', function (Blueprint $t) {
                $t->id();
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('card_id', 191);
                $t->unique(['user_id', 'card_id']);
            });
            DB::statement('PRAGMA foreign_keys = ON');
            return;
        }

        // Inspect existing columns to find a source column to copy
        $cols = collect(DB::select("PRAGMA table_info('favorites')"))
            ->pluck('name')->map(fn($n) => (string)$n)->all();
        $candidates = ['card_id','cardId','card','scryfall_id','mtg_id','external_id','uuid'];
        $src = null;
        foreach ($candidates as $cand) {
            if (in_array($cand, $cols, true)) { $src = $cand; break; }
        }

        // Build correct table
        Schema::create('favorites_tmp', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->string('card_id', 191);
            $t->unique(['user_id', 'card_id']);
        });

        // Copy if we have a source; otherwise skip copy
        if ($src) {
            DB::statement("INSERT INTO favorites_tmp (user_id, card_id) SELECT user_id, CAST($src AS TEXT) FROM favorites");
        }

        Schema::drop('favorites');
        Schema::rename('favorites_tmp', 'favorites');
        DB::statement('PRAGMA foreign_keys = ON');
    }

    public function down(): void
    {
        // no-op for dev
    }
};