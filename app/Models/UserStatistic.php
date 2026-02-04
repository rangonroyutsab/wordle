<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UserStatistic model for tracking player statistics.
 *
 * @property int $id
 * @property int $user_id
 * @property int $games_played
 * @property int $games_won
 * @property int $current_streak
 * @property int $max_streak
 * @property array|null $guess_distribution
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
class UserStatistic extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'games_played',
        'games_won',
        'current_streak',
        'max_streak',
        'guess_distribution',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'games_played' => 'integer',
        'games_won' => 'integer',
        'current_streak' => 'integer',
        'max_streak' => 'integer',
        'guess_distribution' => 'array',
    ];

    /**
     * Default guess distribution structure.
     */
    public static function defaultDistribution(): array
    {
        return [
            '1' => 0,
            '2' => 0,
            '3' => 0,
            '4' => 0,
            '5' => 0,
            '6' => 0,
        ];
    }

    /**
     * Get the user this statistic belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get win percentage.
     */
    public function getWinPercentageAttribute(): float
    {
        if ($this->games_played === 0) {
            return 0.0;
        }

        return round(($this->games_won / $this->games_played) * 100, 1);
    }

    /**
     * Get average guesses for won games.
     */
    public function getAverageGuessesAttribute(): float
    {
        $distribution = $this->guess_distribution ?? self::defaultDistribution();
        $totalGuesses = 0;
        $totalWins = 0;

        foreach ($distribution as $attempts => $count) {
            $totalGuesses += (int)$attempts * $count;
            $totalWins += $count;
        }

        if ($totalWins === 0) {
            return 0.0;
        }

        return round($totalGuesses / $totalWins, 2);
    }

    /**
     * Get or create statistics for a user.
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId],
            [
                'games_played' => 0,
                'games_won' => 0,
                'current_streak' => 0,
                'max_streak' => 0,
                'guess_distribution' => self::defaultDistribution(),
            ]
        );
    }

    /**
     * Record a won game.
     */
    public function recordWin(int $attempts): void
    {
        $distribution = $this->guess_distribution ?? self::defaultDistribution();
        $distribution[(string)$attempts] = ($distribution[(string)$attempts] ?? 0) + 1;

        $newStreak = $this->current_streak + 1;

        $this->update([
            'games_played' => $this->games_played + 1,
            'games_won' => $this->games_won + 1,
            'current_streak' => $newStreak,
            'max_streak' => max($this->max_streak, $newStreak),
            'guess_distribution' => $distribution,
        ]);
    }

    /**
     * Record a lost game.
     */
    public function recordLoss(): void
    {
        $this->update([
            'games_played' => $this->games_played + 1,
            'current_streak' => 0,
        ]);
    }
}
