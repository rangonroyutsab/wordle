<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\DailyWord;
use App\Models\Game;
use App\Models\Guess;
use App\Models\Word;
use App\Exceptions\GameException;
use Illuminate\Support\Facades\DB;

/**
 * Main service for game logic and orchestration.
 */
class GameService
{
    public function __construct(
        private readonly FeedbackService $feedbackService,
        private readonly WordValidationService $wordValidationService,
        private readonly StatisticsService $statisticsService,
    ) {}

    /**
     * Get or create a game for today.
     *
     * @param int|null $userId
     * @param string|null $sessionId
     * @return Game
     */
    public function getOrCreateTodayGame(?int $userId, ?string $sessionId): Game
    {
        return Game::getOrCreateForToday($userId, $sessionId);
    }

    /**
     * Submit a guess for a game.
     *
     * @param Game $game
     * @param string $word
     * @return array{guess: Guess, game: Game, won: bool, lost: bool, feedback: array}
     * @throws GameException
     */
    public function submitGuess(Game $game, string $word): array
    {
        $word = strtoupper(trim($word));

        // Validate game state
        if ($game->isCompleted()) {
            throw GameException::gameAlreadyCompleted();
        }

        if (!$game->hasAttemptsRemaining()) {
            throw GameException::noAttemptsRemaining();
        }

        // Validate word
        $validation = $this->wordValidationService->validate($word);
        if (!$validation['valid']) {
            throw GameException::invalidWord($validation['message']);
        }

        // Get target word
        $targetWord = $game->targetWord;

        // Calculate feedback
        $feedback = $this->feedbackService->calculateFeedback($word, $targetWord);
        $isCorrect = $this->feedbackService->isWinningFeedback($feedback);

        // Create guess and update game in a transaction
        return DB::transaction(function () use ($game, $word, $feedback, $isCorrect) {
            // Create the guess
            $guess = Guess::create([
                'game_id' => $game->id,
                'word' => $word,
                'attempt_number' => $game->attempts_used + 1,
                'feedback' => $feedback,
            ]);

            // Update game
            $game->increment('attempts_used');
            $game->refresh();

            $won = false;
            $lost = false;

            if ($isCorrect) {
                $game->markAsWon();
                $won = true;
                
                // Record statistics if user is logged in
                if ($game->user_id) {
                    $this->statisticsService->recordGameResult(
                        $game->user_id,
                        true,
                        $game->attempts_used
                    );
                }
            } elseif (!$game->hasAttemptsRemaining()) {
                $game->markAsLost();
                $lost = true;
                
                // Record statistics if user is logged in
                if ($game->user_id) {
                    $this->statisticsService->recordGameResult($game->user_id, false);
                }
            }

            return [
                'guess' => $guess,
                'game' => $game->fresh(['guesses']),
                'won' => $won,
                'lost' => $lost,
                'feedback' => $feedback,
            ];
        });
    }

    /**
     * Get game state with all guesses and keyboard state.
     *
     * @param Game $game
     * @return array
     */
    public function getGameState(Game $game): array
    {
        $game->load(['guesses', 'dailyWord.word']);

        $guesses = $game->guesses->map(function (Guess $guess) {
            return [
                'word' => $guess->word,
                'feedback' => $guess->feedback,
                'attempt' => $guess->attempt_number,
            ];
        })->toArray();

        // Calculate keyboard state from all guesses
        $keyboardState = $this->calculateKeyboardState($game->guesses);

        return [
            'id' => $game->id,
            'status' => $game->status,
            'attempts_used' => $game->attempts_used,
            'max_attempts' => Game::MAX_ATTEMPTS,
            'remaining_attempts' => $game->remaining_attempts,
            'guesses' => $guesses,
            'keyboard_state' => $keyboardState,
            'is_completed' => $game->isCompleted(),
            'is_won' => $game->isWon(),
            'is_lost' => $game->isLost(),
            'target_word' => $game->isCompleted() ? $game->targetWord : null,
        ];
    }

    /**
     * Calculate keyboard state from guesses.
     * Priority: correct > present > absent
     *
     * @param \Illuminate\Database\Eloquent\Collection $guesses
     * @return array<string, string>
     */
    private function calculateKeyboardState($guesses): array
    {
        $state = [];
        $priority = [
            Guess::STATUS_CORRECT => 3,
            Guess::STATUS_PRESENT => 2,
            Guess::STATUS_ABSENT => 1,
        ];

        foreach ($guesses as $guess) {
            foreach ($guess->feedback as $item) {
                $letter = $item['letter'];
                $status = $item['status'];

                if (!isset($state[$letter]) || $priority[$status] > $priority[$state[$letter]]) {
                    $state[$letter] = $status;
                }
            }
        }

        return $state;
    }

    /**
     * Generate shareable result text.
     *
     * @param Game $game
     * @return string
     */
    public function generateShareText(Game $game): string
    {
        if (!$game->isCompleted()) {
            throw GameException::gameNotCompleted();
        }

        $game->load('guesses');

        $allFeedback = $game->guesses->pluck('feedback')->toArray();
        $emojiGrid = $this->feedbackService->generateShareableResult($allFeedback);

        $attempts = $game->isWon() ? $game->attempts_used : 'X';
        $gameNumber = $game->daily_word_id;

        return "Wordle #{$gameNumber} {$attempts}/6\n\n{$emojiGrid}";
    }

    /**
     * Get today's daily word (for admin/debug purposes).
     *
     * @return DailyWord
     */
    public function getTodayWord(): DailyWord
    {
        return DailyWord::getOrCreateToday();
    }
}
