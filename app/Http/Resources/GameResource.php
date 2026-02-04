<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for Game model.
 */
class GameResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Game $this */
        return [
            'id' => $this->id,
            'status' => $this->status,
            'attempts_used' => $this->attempts_used,
            'max_attempts' => Game::MAX_ATTEMPTS,
            'remaining_attempts' => $this->remaining_attempts,
            'is_completed' => $this->isCompleted(),
            'is_won' => $this->isWon(),
            'is_lost' => $this->isLost(),
            'guesses' => GuessResource::collection($this->whenLoaded('guesses')),
            'target_word' => $this->when($this->isCompleted(), $this->targetWord),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
