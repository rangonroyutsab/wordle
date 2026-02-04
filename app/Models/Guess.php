<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Guess model representing a single attempt in a game.
 *
 * @property int $id
 * @property int $game_id
 * @property string $word
 * @property int $attempt_number
 * @property array $feedback
 * @property \Illuminate\Support\Carbon $created_at
 * @property-read Game $game
 */
class Guess extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const STATUS_CORRECT = 'correct';   // Green - right letter, right position
    public const STATUS_PRESENT = 'present';   // Yellow - right letter, wrong position
    public const STATUS_ABSENT = 'absent';     // Gray - letter not in word

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'game_id',
        'word',
        'attempt_number',
        'feedback',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'feedback' => 'array',
        'attempt_number' => 'integer',
    ];

    /**
     * Get the game this guess belongs to.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Check if this guess was correct (all letters in correct position).
     */
    public function isCorrect(): bool
    {
        foreach ($this->feedback as $letterFeedback) {
            if ($letterFeedback['status'] !== self::STATUS_CORRECT) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the feedback as an array of status strings.
     */
    public function getStatusArrayAttribute(): array
    {
        return array_map(fn($item) => $item['status'], $this->feedback);
    }

    /**
     * Get emoji representation of the feedback for sharing.
     */
    public function getEmojiRepresentationAttribute(): string
    {
        $emojis = [
            self::STATUS_CORRECT => '🟩',
            self::STATUS_PRESENT => '🟨',
            self::STATUS_ABSENT => '⬜',
        ];

        return collect($this->feedback)
            ->map(fn($item) => $emojis[$item['status']] ?? '⬜')
            ->join('');
    }
}
