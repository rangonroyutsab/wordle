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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('session_id', 255)->nullable()->comment('For guest tracking');
            $table->foreignId('daily_word_id')->constrained('daily_words')->onDelete('cascade');
            $table->enum('status', ['in_progress', 'won', 'lost'])->default('in_progress');
            $table->unsignedTinyInteger('attempts_used')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Ensure one game per user per day
            $table->unique(['user_id', 'daily_word_id']);
            // Ensure one game per session per day (for guests)
            $table->unique(['session_id', 'daily_word_id']);
            
            $table->index('session_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
