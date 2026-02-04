<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for user statistics.
 */
class StatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'games_played' => $this->games_played,
            'games_won' => $this->games_won,
            'win_percentage' => $this->win_percentage,
            'current_streak' => $this->current_streak,
            'max_streak' => $this->max_streak,
            'guess_distribution' => $this->guess_distribution,
            'average_guesses' => $this->average_guesses,
        ];
    }
}
