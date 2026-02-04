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
        Schema::create('words', function (Blueprint $table) {
            $table->id();
            $table->string('word', 5)->unique();
            $table->boolean('is_solution')->default(true)->comment('Can be used as daily word');
            $table->boolean('is_valid')->default(true)->comment('Valid for guessing');
            $table->timestamps();

            $table->index('word');
            $table->index(['is_solution', 'is_valid']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('words');
    }
};
