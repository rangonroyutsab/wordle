<?php

namespace Tests\Feature;

use App\Models\Word;
use App\Models\DailyWord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Seed the required data for the home page to work
        $word = Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);
        DailyWord::create(['word_id' => $word->id, 'game_date' => now()->toDateString()]);

        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
