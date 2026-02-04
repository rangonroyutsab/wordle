<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('guesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained('games')->onDelete('cascade');
            $table->string('word', 5);
            $table->unsignedTinyInteger('attempt_number');
            $table->json('feedback')->comment('Array of letter feedback [{letter, status}]');
            $table->timestamp('created_at')->useCurrent();

            // Ensure unique attempt number per game
            $table->unique(['game_id', 'attempt_number']);
            
            $table->index('game_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guesses');
    }
};
