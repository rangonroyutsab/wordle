<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DailyWord;
use App\Models\Game;
use App\Models\Word;
use App\Services\GameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameServiceTest extends TestCase
{
    use RefreshDatabase;

    private GameService $gameService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gameService = app(GameService::class);
    }

    public function test_can_create_game_for_today(): void
    {
        // Seed a word
        $word = Word::create([
            'word' => 'APPLE',
            'is_solution' => true,
            'is_valid' => true,
        ]);

        $game = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');

        $this->assertNotNull($game);
        $this->assertEquals(Game::STATUS_IN_PROGRESS, $game->status);
        $this->assertEquals(0, $game->attempts_used);
    }

    public function test_same_session_gets_same_game(): void
    {
        Word::create([
            'word' => 'APPLE',
            'is_solution' => true,
            'is_valid' => true,
        ]);

        $game1 = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');
        $game2 = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');

        $this->assertEquals($game1->id, $game2->id);
    }

    public function test_can_submit_valid_guess(): void
    {
        // Create solution word and valid guess words
        Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);
        Word::create(['word' => 'CRANE', 'is_solution' => false, 'is_valid' => true]);

        $game = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');
        
        // Get the actual target word
        $targetWord = $game->targetWord;

        // Submit a guess that's different from target
        $result = $this->gameService->submitGuess($game, 'CRANE');

        $this->assertNotNull($result['guess']);
        $this->assertEquals('CRANE', $result['guess']->word);
        $this->assertEquals(1, $result['game']->attempts_used);
    }

    public function test_invalid_word_throws_exception(): void
    {
        Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);

        $game = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');

        $this->expectException(\App\Exceptions\GameException::class);
        $this->gameService->submitGuess($game, 'ZZZZZ');
    }

    public function test_winning_game_marks_as_won(): void
    {
        Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);

        $game = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');
        $targetWord = $game->targetWord;

        $result = $this->gameService->submitGuess($game, $targetWord);

        $this->assertTrue($result['won']);
        $this->assertEquals(Game::STATUS_WON, $result['game']->status);
    }

    public function test_game_state_includes_keyboard_state(): void
    {
        Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);
        Word::create(['word' => 'CRANE', 'is_solution' => false, 'is_valid' => true]);

        $game = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');
        $this->gameService->submitGuess($game, 'CRANE');

        $state = $this->gameService->getGameState($game->fresh());

        $this->assertArrayHasKey('keyboard_state', $state);
        $this->assertNotEmpty($state['keyboard_state']);
    }

    public function test_cannot_submit_guess_on_completed_game(): void
    {
        Word::create(['word' => 'APPLE', 'is_solution' => true, 'is_valid' => true]);

        $game = $this->gameService->getOrCreateTodayGame(null, 'test-session-id');
        $targetWord = $game->targetWord;

        // Win the game
        $this->gameService->submitGuess($game, $targetWord);

        // Try to submit another guess
        $this->expectException(\App\Exceptions\GameException::class);
        $this->gameService->submitGuess($game->fresh(), 'CRANE');
    }
}
