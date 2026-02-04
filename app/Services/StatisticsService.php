<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\UserStatistic;

/**
 * Service for managing user statistics.
 */
class StatisticsService
{
    /**
     * Record a game result for a user.
     *
     * @param int $userId
     * @param bool $won
     * @param int|null $attempts Number of attempts (only relevant if won)
     */
    public function recordGameResult(int $userId, bool $won, ?int $attempts = null): void
    {
        $statistic = UserStatistic::getOrCreateForUser($userId);

        if ($won && $attempts !== null) {
            $statistic->recordWin($attempts);
        } else {
            $statistic->recordLoss();
        }
    }

    /**
     * Get statistics for a user.
     *
     * @param int $userId
     * @return array{
     *     games_played: int,
     *     games_won: int,
     *     win_percentage: float,
     *     current_streak: int,
     *     max_streak: int,
     *     guess_distribution: array<string, int>,
     *     average_guesses: float
     * }
     */
    public function getStatistics(int $userId): array
    {
        $statistic = UserStatistic::getOrCreateForUser($userId);

        return [
            'games_played' => $statistic->games_played,
            'games_won' => $statistic->games_won,
            'win_percentage' => $statistic->win_percentage,
            'current_streak' => $statistic->current_streak,
            'max_streak' => $statistic->max_streak,
            'guess_distribution' => $statistic->guess_distribution ?? UserStatistic::defaultDistribution(),
            'average_guesses' => $statistic->average_guesses,
        ];
    }

    /**
     * Get formatted statistics suitable for display.
     *
     * @param int $userId
     * @return array
     */
    public function getFormattedStatistics(int $userId): array
    {
        $stats = $this->getStatistics($userId);
        $distribution = $stats['guess_distribution'];

        // Calculate max for distribution bar scaling
        $maxCount = max(array_values($distribution)) ?: 1;

        $formattedDistribution = [];
        foreach ($distribution as $attempts => $count) {
            $formattedDistribution[$attempts] = [
                'count' => $count,
                'percentage' => $maxCount > 0 ? round(($count / $maxCount) * 100) : 0,
            ];
        }

        return [
            'played' => $stats['games_played'],
            'win_percentage' => $stats['win_percentage'],
            'current_streak' => $stats['current_streak'],
            'max_streak' => $stats['max_streak'],
            'distribution' => $formattedDistribution,
        ];
    }
}
