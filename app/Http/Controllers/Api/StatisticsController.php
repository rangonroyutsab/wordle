<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\StatisticsResource;
use App\Models\UserStatistic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API controller for statistics operations.
 */
class StatisticsController extends Controller
{
    /**
     * Get the current user's statistics.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required to view statistics.',
            ], 401);
        }

        $statistic = UserStatistic::getOrCreateForUser($user->id);

        return response()->json([
            'success' => true,
            'data' => new StatisticsResource($statistic),
        ]);
    }
}
