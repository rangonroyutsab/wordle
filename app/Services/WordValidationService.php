<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Word;
use Illuminate\Support\Facades\Cache;

/**
 * Service for validating words against the dictionary.
 */
class WordValidationService
{
    private const CACHE_KEY_VALID_WORDS = 'wordle:valid_words';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Check if a word is valid for guessing.
     *
     * @param string $word The word to validate
     * @return bool
     */
    public function isValidWord(string $word): bool
    {
        $word = strtoupper(trim($word));

        // Basic validation
        if (strlen($word) !== 5) {
            return false;
        }

        // Only allow alphabetic characters
        if (!ctype_alpha($word)) {
            return false;
        }

        // Check against dictionary
        return Word::isValidGuess($word);
    }

    /**
     * Validate word and return validation result with message.
     *
     * @param string $word
     * @return array{valid: bool, message: string|null}
     */
    public function validate(string $word): array
    {
        $word = strtoupper(trim($word));

        if (strlen($word) !== 5) {
            return [
                'valid' => false,
                'message' => 'Word must be exactly 5 letters.',
            ];
        }

        if (!ctype_alpha($word)) {
            return [
                'valid' => false,
                'message' => 'Word must contain only letters.',
            ];
        }

        if (!Word::isValidGuess($word)) {
            return [
                'valid' => false,
                'message' => 'Not in word list.',
            ];
        }

        return [
            'valid' => true,
            'message' => null,
        ];
    }

    /**
     * Get a set of all valid words for quick lookup.
     * Results are cached for performance.
     *
     * @return array<string>
     */
    public function getValidWordSet(): array
    {
        return Cache::remember(self::CACHE_KEY_VALID_WORDS, self::CACHE_TTL, function () {
            return Word::valid()
                ->pluck('word')
                ->map(fn($word) => strtoupper($word))
                ->toArray();
        });
    }

    /**
     * Clear the cached word set.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY_VALID_WORDS);
    }
}
