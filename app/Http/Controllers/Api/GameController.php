<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\GameException;
use App\Http\Controllers\Controller;
use App\Http\Requests\GuessRequest;
use App\Http\Resources\GameResource;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for game operations.
 */
class GameController extends Controller
{
    public function __construct(
        private readonly GameService $gameService,
    ) {}

    /**
     * Get or create today's game.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function today(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $game = $this->gameService->getOrCreateTodayGame($userId, $sessionId);
        $gameState = $this->gameService->getGameState($game);

        return response()->json([
            'success' => true,
            'data' => $gameState,
        ]);
    }

    /**
     * Get a specific game.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $game = \App\Models\Game::where('id', $id)
            ->where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })
            ->first();

        if (!$game) {
            throw GameException::gameNotFound();
        }

        $gameState = $this->gameService->getGameState($game);

        return response()->json([
            'success' => true,
            'data' => $gameState,
        ]);
    }

    /**
     * Submit a guess for today's game.
     *
     * @param GuessRequest $request
     * @return JsonResponse
     */
    public function guess(GuessRequest $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $game = $this->gameService->getOrCreateTodayGame($userId, $sessionId);

        try {
            $result = $this->gameService->submitGuess($game, $request->validated('word'));

            return response()->json([
                'success' => true,
                'data' => [
                    'guess' => [
                        'word' => $result['guess']->word,
                        'feedback' => $result['feedback'],
                        'attempt' => $result['guess']->attempt_number,
                    ],
                    'game' => $this->gameService->getGameState($result['game']),
                    'won' => $result['won'],
                    'lost' => $result['lost'],
                ],
            ]);
        } catch (GameException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }

    /**
     * Get shareable result for a completed game.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function share(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();

        $game = $this->gameService->getOrCreateTodayGame($userId, $sessionId);

        try {
            $shareText = $this->gameService->generateShareText($game);

            return response()->json([
                'success' => true,
                'data' => [
                    'text' => $shareText,
                ],
            ]);
        } catch (GameException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 400);
        }
    }
}
