<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Exception for game-related errors.
 */
class GameException extends Exception
{
    /**
     * Create exception for already completed game.
     */
    public static function gameAlreadyCompleted(): self
    {
        return new self('This game has already been completed.', 400);
    }

    /**
     * Create exception for no remaining attempts.
     */
    public static function noAttemptsRemaining(): self
    {
        return new self('No attempts remaining.', 400);
    }

    /**
     * Create exception for invalid word.
     */
    public static function invalidWord(?string $message = null): self
    {
        return new self($message ?? 'Invalid word.', 422);
    }

    /**
     * Create exception for game not completed.
     */
    public static function gameNotCompleted(): self
    {
        return new self('Game is not completed yet.', 400);
    }

    /**
     * Create exception for game not found.
     */
    public static function gameNotFound(): self
    {
        return new self('Game not found.', 404);
    }
}
