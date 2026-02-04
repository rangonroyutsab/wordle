<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Guess;

/**
 * Service for calculating feedback on guesses.
 * Implements the two-pass algorithm for accurate Wordle feedback.
 */
class FeedbackService
{
    /**
     * Calculate feedback for a guess against the target word.
     * Returns an array of letter feedback with status (correct/present/absent).
     *
     * @param string $guess The guessed word (5 letters)
     * @param string $target The target word (5 letters)
     * @return array<int, array{letter: string, status: string}>
     */
    public function calculateFeedback(string $guess, string $target): array
    {
        $guess = strtoupper($guess);
        $target = strtoupper($target);

        $guessLetters = str_split($guess);
        $targetLetters = str_split($target);

        $feedback = array_fill(0, 5, null);
        $remaining = [];

        // First pass: Find exact matches (green/correct)
        foreach ($guessLetters as $i => $letter) {
            if ($letter === $targetLetters[$i]) {
                $feedback[$i] = [
                    'letter' => $letter,
                    'status' => Guess::STATUS_CORRECT,
                ];
            } else {
                // Track remaining target letters for second pass
                $remaining[] = $targetLetters[$i];
            }
        }

        // Second pass: Find present (yellow) or absent (gray) letters
        foreach ($guessLetters as $i => $letter) {
            // Skip already matched letters
            if ($feedback[$i] !== null) {
                continue;
            }

            $position = array_search($letter, $remaining, true);

            if ($position !== false) {
                // Letter exists in target but wrong position
                $feedback[$i] = [
                    'letter' => $letter,
                    'status' => Guess::STATUS_PRESENT,
                ];
                // Remove from remaining to handle duplicates correctly
                unset($remaining[$position]);
                $remaining = array_values($remaining);
            } else {
                // Letter not in target word
                $feedback[$i] = [
                    'letter' => $letter,
                    'status' => Guess::STATUS_ABSENT,
                ];
            }
        }

        return $feedback;
    }

    /**
     * Check if feedback indicates a winning guess.
     *
     * @param array<int, array{letter: string, status: string}> $feedback
     * @return bool
     */
    public function isWinningFeedback(array $feedback): bool
    {
        foreach ($feedback as $item) {
            if ($item['status'] !== Guess::STATUS_CORRECT) {
                return false;
            }
        }
        return true;
    }

    /**
     * Generate shareable emoji representation of game results.
     *
     * @param array<array<int, array{letter: string, status: string}>> $allFeedback
     * @return string
     */
    public function generateShareableResult(array $allFeedback): string
    {
        $emojis = [
            Guess::STATUS_CORRECT => '🟩',
            Guess::STATUS_PRESENT => '🟨',
            Guess::STATUS_ABSENT => '⬜',
        ];

        $lines = [];
        foreach ($allFeedback as $feedback) {
            $line = '';
            foreach ($feedback as $item) {
                $line .= $emojis[$item['status']] ?? '⬜';
            }
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }
}
