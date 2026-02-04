<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API resource for Guess model.
 */
class GuessResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'word' => $this->word,
            'attempt_number' => $this->attempt_number,
            'feedback' => $this->feedback,
            'is_correct' => $this->isCorrect(),
            'emoji' => $this->emoji_representation,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
