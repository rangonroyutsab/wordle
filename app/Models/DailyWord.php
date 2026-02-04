<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * DailyWord model representing the scheduled word for each day.
 *
 * @property int $id
 * @property int $word_id
 * @property Carbon $game_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Word $word
 */
class DailyWord extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'word_id',
        'game_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'game_date' => 'date',
    ];

    /**
     * Get the word for this daily challenge.
     */
    public function word(): BelongsTo
    {
        return $this->belongsTo(Word::class);
    }

    /**
     * Get all games played for this daily word.
     */
    public function games(): HasMany
    {
        return $this->hasMany(Game::class);
    }

    /**
     * Get today's daily word.
     */
    public static function today(): ?self
    {
        return static::where('game_date', Carbon::today())->first();
    }

    /**
     * Get or create today's daily word.
     * If no word is scheduled, picks a random solution word.
     */
    public static function getOrCreateToday(): self
    {
        $today = Carbon::today();
        
        $dailyWord = static::where('game_date', $today)->first();
        
        if ($dailyWord) {
            return $dailyWord;
        }

        // Pick a random word that hasn't been used recently
        $usedWordIds = static::where('game_date', '>=', $today->copy()->subDays(365))
            ->pluck('word_id');

        $word = Word::solution()
            ->whereNotIn('id', $usedWordIds)
            ->inRandomOrder()
            ->first();

        // Fallback to any solution word if all have been used
        if (!$word) {
            $word = Word::solution()->inRandomOrder()->first();
        }

        if (!$word) {
            throw new \RuntimeException('No solution words available in the database.');
        }

        return static::create([
            'word_id' => $word->id,
            'game_date' => $today,
        ]);
    }

    /**
     * Get the actual word string.
     */
    public function getWordStringAttribute(): string
    {
        return $this->word->word;
    }
}
