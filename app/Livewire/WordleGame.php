<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Game;
use App\Models\Guess;
use App\Services\GameService;
use App\Exceptions\GameException;
use Livewire\Component;

/**
 * Main Wordle game Livewire component.
 */
class WordleGame extends Component
{
    // Game state
    public ?int $gameId = null;
    public string $status = 'in_progress';
    public int $attemptsUsed = 0;
    public int $maxAttempts = 6;
    public array $guesses = [];
    public array $keyboardState = [];
    public bool $isCompleted = false;
    public bool $isWon = false;
    public bool $isLost = false;
    public ?string $targetWord = null;

    // Current input
    public string $currentGuess = '';
    
    // UI state
    public string $errorMessage = '';
    public bool $showModal = false;
    public string $shareText = '';

    // Keyboard layout
    public array $keyboardRows = [
        ['Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P'],
        ['A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L'],
        ['ENTER', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', 'BACKSPACE'],
    ];

    protected GameService $gameService;

    public function boot(GameService $gameService): void
    {
        $this->gameService = $gameService;
    }

    public function mount(): void
    {
        $this->loadGame();
    }

    /**
     * Load or create today's game.
     */
    public function loadGame(): void
    {
        $userId = auth()->id();
        $sessionId = session()->getId();

        $game = $this->gameService->getOrCreateTodayGame($userId, $sessionId);
        $this->updateStateFromGame($game);
    }

    /**
     * Update component state from a game model.
     */
    private function updateStateFromGame(Game $game): void
    {
        $game->load('guesses');

        $this->gameId = $game->id;
        $this->status = $game->status;
        $this->attemptsUsed = $game->attempts_used;
        $this->isCompleted = $game->isCompleted();
        $this->isWon = $game->isWon();
        $this->isLost = $game->isLost();
        $this->targetWord = $game->isCompleted() ? $game->targetWord : null;

        // Map guesses
        $this->guesses = $game->guesses->map(function (Guess $guess) {
            return [
                'word' => $guess->word,
                'feedback' => $guess->feedback,
                'attempt' => $guess->attempt_number,
            ];
        })->toArray();

        // Calculate keyboard state
        $this->calculateKeyboardState();

        // Show modal if game is completed
        if ($this->isCompleted) {
            $this->showModal = true;
            $this->generateShareText();
        }
    }

    /**
     * Calculate keyboard state from guesses.
     */
    private function calculateKeyboardState(): void
    {
        $this->keyboardState = [];
        $priority = [
            Guess::STATUS_CORRECT => 3,
            Guess::STATUS_PRESENT => 2,
            Guess::STATUS_ABSENT => 1,
        ];

        foreach ($this->guesses as $guess) {
            foreach ($guess['feedback'] as $item) {
                $letter = $item['letter'];
                $status = $item['status'];

                if (!isset($this->keyboardState[$letter]) || 
                    $priority[$status] > $priority[$this->keyboardState[$letter]]) {
                    $this->keyboardState[$letter] = $status;
                }
            }
        }
    }

    /**
     * Handle key press from keyboard.
     */
    public function keyPress(string $key): void
    {
        if ($this->isCompleted) {
            return;
        }

        $this->errorMessage = '';

        if ($key === 'ENTER') {
            $this->submitGuess();
        } elseif ($key === 'BACKSPACE') {
            $this->currentGuess = substr($this->currentGuess, 0, -1);
        } elseif (strlen($this->currentGuess) < 5 && ctype_alpha($key)) {
            $this->currentGuess .= strtoupper($key);
        }
    }

    /**
     * Submit the current guess.
     */
    public function submitGuess(): void
    {
        if (strlen($this->currentGuess) !== 5) {
            $this->errorMessage = 'Not enough letters';
            $this->dispatch('shake-row', row: $this->attemptsUsed);
            return;
        }

        $game = Game::find($this->gameId);
        
        if (!$game) {
            $this->errorMessage = 'Game not found';
            return;
        }

        try {
            $result = $this->gameService->submitGuess($game, $this->currentGuess);
            
            // Clear current guess
            $this->currentGuess = '';
            
            // Update state
            $this->updateStateFromGame($result['game']);

            // Dispatch events for animations
            $this->dispatch('reveal-row', row: $this->attemptsUsed - 1);

            if ($result['won']) {
                $this->dispatch('game-won');
            } elseif ($result['lost']) {
                $this->dispatch('game-lost');
            }

        } catch (GameException $e) {
            $this->errorMessage = $e->getMessage();
            $this->dispatch('shake-row', row: $this->attemptsUsed);
        }
    }

    /**
     * Generate share text for completed game.
     */
    private function generateShareText(): void
    {
        if (!$this->isCompleted) {
            return;
        }

        $attempts = $this->isWon ? $this->attemptsUsed : 'X';
        $lines = ["Wordle #{$this->gameId} {$attempts}/6", ''];

        foreach ($this->guesses as $guess) {
            $emojis = [
                Guess::STATUS_CORRECT => '🟩',
                Guess::STATUS_PRESENT => '🟨',
                Guess::STATUS_ABSENT => '⬜',
            ];

            $line = '';
            foreach ($guess['feedback'] as $item) {
                $line .= $emojis[$item['status']] ?? '⬜';
            }
            $lines[] = $line;
        }

        $this->shareText = implode("\n", $lines);
    }

    /**
     * Copy share text to clipboard.
     */
    public function copyToClipboard(): void
    {
        $this->dispatch('copy-to-clipboard', text: $this->shareText);
    }

    /**
     * Close the result modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
    }

    /**
     * Get empty rows for the grid.
     */
    public function getEmptyRowsProperty(): array
    {
        $filledRows = count($this->guesses);
        $currentRowIncluded = $this->isCompleted ? 0 : 1;
        $emptyRows = $this->maxAttempts - $filledRows - $currentRowIncluded;
        
        return array_fill(0, max(0, $emptyRows), null);
    }

    /**
     * Get current guess as array of letters.
     */
    public function getCurrentGuessLettersProperty(): array
    {
        $letters = str_split($this->currentGuess);
        return array_pad($letters, 5, '');
    }

    public function render()
    {
        return view('livewire.wordle-game')
            ->layout('layouts.app');
    }
}
