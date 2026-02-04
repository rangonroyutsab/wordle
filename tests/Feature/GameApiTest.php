<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed some test words
        Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);
        Word::create(['word' => 'CRANE', 'is_solution' => false, 'is_valid' => true]);
        Word::create(['word' => 'BRAIN', 'is_solution' => false, 'is_valid' => true]);
        Word::create(['word' => 'GRAPE', 'is_solution' => false, 'is_valid' => true]);
    }

    public function test_can_get_todays_game(): void
    {
        $response = $this->withSession(['_token' => 'test'])
            ->get('/api/game/today');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'attempts_used',
                    'max_attempts',
                    'remaining_attempts',
                    'guesses',
                    'keyboard_state',
                    'is_completed',
                    'is_won',
                    'is_lost',
                ],
            ]);
    }

    public function test_can_submit_valid_guess(): void
    {
        // First get a game
        $this->withSession(['_token' => 'test'])
            ->get('/api/game/today');

        $response = $this->withSession(['_token' => 'test'])
            ->postJson('/api/game/guess', [
                'word' => 'CRANE',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'guess' => [
                        'word',
                        'feedback',
                        'attempt',
                    ],
                    'game',
                    'won',
                    'lost',
                ],
            ]);
    }

    public function test_rejects_invalid_word(): void
    {
        // First get a game
        $this->withSession(['_token' => 'test'])
            ->get('/api/game/today');

        $response = $this->withSession(['_token' => 'test'])
            ->postJson('/api/game/guess', [
                'word' => 'ZZZZZ', // Not in word list
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_rejects_wrong_length_word(): void
    {
        $response = $this->withSession(['_token' => 'test'])
            ->postJson('/api/game/guess', [
                'word' => 'CAT', // Too short
            ]);

        $response->assertStatus(422);
    }

    public function test_game_state_persists_across_requests(): void
    {
        // Create a game directly in the database for a specific session
        $dailyWord = \App\Models\DailyWord::create([
            'word_id' => Word::where('word', 'APPLE')->first()->id,
            'game_date' => now()->toDateString(),
        ]);

        $game = \App\Models\Game::create([
            'daily_word_id' => $dailyWord->id,
            'session_id' => 'test-session-id',
            'status' => 'in_progress',
            'max_attempts' => 6,
        ]);

        // Submit a guess
        \App\Models\Guess::create([
            'game_id' => $game->id,
            'word' => 'CRANE',
            'feedback' => json_encode([
                ['letter' => 'C', 'status' => 'absent'],
                ['letter' => 'R', 'status' => 'absent'],
                ['letter' => 'A', 'status' => 'present'],
                ['letter' => 'N', 'status' => 'absent'],
                ['letter' => 'E', 'status' => 'present'],
            ]),
            'attempt_number' => 1,
        ]);

        // Verify the game state persisted
        $game->refresh();
        $this->assertEquals(1, $game->guesses()->count());
        $this->assertEquals('CRANE', $game->guesses()->first()->word);
    }

    public function test_keyboard_state_updates_after_guess(): void
    {
        $this->withSession(['_token' => 'test'])
            ->get('/api/game/today');

        $response = $this->withSession(['_token' => 'test'])
            ->postJson('/api/game/guess', ['word' => 'CRANE']);

        $keyboardState = $response->json('data.game.keyboard_state');

        // All letters in CRANE should have a state
        $this->assertArrayHasKey('C', $keyboardState);
        $this->assertArrayHasKey('R', $keyboardState);
        $this->assertArrayHasKey('A', $keyboardState);
        $this->assertArrayHasKey('N', $keyboardState);
        $this->assertArrayHasKey('E', $keyboardState);
    }
}
