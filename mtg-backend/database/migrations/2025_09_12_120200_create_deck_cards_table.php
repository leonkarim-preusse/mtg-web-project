<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('deck_cards', function (Blueprint $table) {
            $table->id();
            $table->uuid('deck_id');
            $table->string('mtg_card_id');
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->foreign('deck_id')->references('id')->on('decks')->cascadeOnDelete();
            $table->unique(['deck_id', 'mtg_card_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('deck_cards');
    }
};