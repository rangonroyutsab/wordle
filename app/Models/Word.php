<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Word model representing the dictionary of valid Wordle words.
 *
 * @property int $id
 * @property string $word
 * @property bool $is_solution
 * @property bool $is_valid
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Word extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'word',
        'is_solution',
        'is_valid',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_solution' => 'boolean',
        'is_valid' => 'boolean',
    ];

    /**
     * Scope for words that can be used as daily solutions.
     */
    public function scopeSolution($query)
    {
        return $query->where('is_solution', true);
    }

    /**
     * Scope for words that are valid guesses.
     */
    public function scopeValid($query)
    {
        return $query->where('is_valid', true);
    }

    /**
     * Get the daily words that use this word.
     */
    public function dailyWords(): HasMany
    {
        return $this->hasMany(DailyWord::class);
    }

    /**
     * Check if a word exists and is valid for guessing.
     */
    public static function isValidGuess(string $word): bool
    {
        return static::where('word', strtoupper($word))
            ->where('is_valid', true)
            ->exists();
    }
}
