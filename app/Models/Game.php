<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Game model representing a player's game session for a daily word.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $session_id
 * @property int $daily_word_id
 * @property string $status
 * @property int $attempts_used
 * @property Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read DailyWord $dailyWord
 * @property-read \Illuminate\Database\Eloquent\Collection|Guess[] $guesses
 */
class Game extends Model
{
    use HasFactory;

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';

    public const MAX_ATTEMPTS = 6;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'daily_word_id',
        'status',
        'attempts_used',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'completed_at' => 'datetime',
        'attempts_used' => 'integer',
    ];

    /**
     * Get the user who owns this game.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the daily word for this game.
     */
    public function dailyWord(): BelongsTo
    {
        return $this->belongsTo(DailyWord::class);
    }

    /**
     * Get all guesses made in this game.
     */
    public function guesses(): HasMany
    {
        return $this->hasMany(Guess::class)->orderBy('attempt_number');
    }

    /**
     * Check if the game is still in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if the game was won.
     */
    public function isWon(): bool
    {
        return $this->status === self::STATUS_WON;
    }

    /**
     * Check if the game was lost.
     */
    public function isLost(): bool
    {
        return $this->status === self::STATUS_LOST;
    }

    /**
     * Check if the game is completed (won or lost).
     */
    public function isCompleted(): bool
    {
        return $this->status !== self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if there are attempts remaining.
     */
    public function hasAttemptsRemaining(): bool
    {
        return $this->attempts_used < self::MAX_ATTEMPTS;
    }

    /**
     * Get remaining attempts count.
     */
    public function getRemainingAttemptsAttribute(): int
    {
        return self::MAX_ATTEMPTS - $this->attempts_used;
    }

    /**
     * Mark the game as won.
     */
    public function markAsWon(): void
    {
        $this->update([
            'status' => self::STATUS_WON,
            'completed_at' => Carbon::now(),
        ]);
    }

    /**
     * Mark the game as lost.
     */
    public function markAsLost(): void
    {
        $this->update([
            'status' => self::STATUS_LOST,
            'completed_at' => Carbon::now(),
        ]);
    }

    /**
     * Get the target word for this game.
     */
    public function getTargetWordAttribute(): string
    {
        return $this->dailyWord->word->word;
    }

    /**
     * Scope for games in progress.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for completed games.
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_WON, self::STATUS_LOST]);
    }

    /**
     * Scope for won games.
     */
    public function scopeWon($query)
    {
        return $query->where('status', self::STATUS_WON);
    }

    /**
     * Get or create a game for the current user/session and daily word.
     */
    public static function getOrCreateForToday(?int $userId, ?string $sessionId): self
    {
        $dailyWord = DailyWord::getOrCreateToday();

        $query = static::where('daily_word_id', $dailyWord->id);

        if ($userId) {
            $query->where('user_id', $userId);
        } else {
            $query->where('session_id', $sessionId);
        }

        $game = $query->first();

        if ($game) {
            return $game;
        }

        return static::create([
            'user_id' => $userId,
            'session_id' => $userId ? null : $sessionId,
            'daily_word_id' => $dailyWord->id,
            'status' => self::STATUS_IN_PROGRESS,
            'attempts_used' => 0,
        ]);
    }
}
