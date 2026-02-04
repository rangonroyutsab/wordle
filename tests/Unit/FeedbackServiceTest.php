<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\FeedbackService;
use App\Models\Guess;
use PHPUnit\Framework\TestCase;

class FeedbackServiceTest extends TestCase
{
    private FeedbackService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new FeedbackService();
    }

    public function test_all_correct_letters(): void
    {
        $feedback = $this->service->calculateFeedback('APPLE', 'APPLE');

        foreach ($feedback as $item) {
            $this->assertEquals(Guess::STATUS_CORRECT, $item['status']);
        }
    }

    public function test_all_absent_letters(): void
    {
        $feedback = $this->service->calculateFeedback('QUICK', 'FRAME');

        foreach ($feedback as $item) {
            $this->assertEquals(Guess::STATUS_ABSENT, $item['status']);
        }
    }

    public function test_present_and_absent_letters(): void
    {
        // Target: CRANE, Guess: REACT
        // R is present (wrong position - R is at position 1 in CRANE)
        // E is present (wrong position - E is at position 4 in CRANE)
        // A is correct (A is at position 2 in both)
        // C is present (wrong position - C is at position 0 in CRANE)
        // T is absent
        $feedback = $this->service->calculateFeedback('REACT', 'CRANE');

        $this->assertEquals(Guess::STATUS_PRESENT, $feedback[0]['status']); // R
        $this->assertEquals(Guess::STATUS_PRESENT, $feedback[1]['status']); // E
        $this->assertEquals(Guess::STATUS_CORRECT, $feedback[2]['status']); // A - correct position!
        $this->assertEquals(Guess::STATUS_PRESENT, $feedback[3]['status']); // C
        $this->assertEquals(Guess::STATUS_ABSENT, $feedback[4]['status']);  // T
    }

    public function test_duplicate_letters_handled_correctly(): void
    {
        // Target: HELLO, Guess: LEMON
        // L - present (L exists in HELLO at different positions)
        // E - correct (E is at position 1 in HELLO)
        // M - absent
        // O - present (O is at position 4 in HELLO)
        // N - absent
        $feedback = $this->service->calculateFeedback('LEMON', 'HELLO');

        $this->assertEquals(Guess::STATUS_PRESENT, $feedback[0]['status']); // L
        $this->assertEquals(Guess::STATUS_CORRECT, $feedback[1]['status']); // E
        $this->assertEquals(Guess::STATUS_ABSENT, $feedback[2]['status']);  // M
        $this->assertEquals(Guess::STATUS_PRESENT, $feedback[3]['status']); // O
        $this->assertEquals(Guess::STATUS_ABSENT, $feedback[4]['status']);  // N
    }

    public function test_duplicate_guess_letters_limited_by_target(): void
    {
        // Target: CRANE, Guess: EERIE
        // First E - absent (position 0, but E is at position 4 in CRANE)
        // Second E - absent (position 1)
        // R - present (R is at position 1 in CRANE)
        // I - absent
        // Third E - correct (E is at position 4 in CRANE)
        $feedback = $this->service->calculateFeedback('EERIE', 'CRANE');

        $this->assertEquals(Guess::STATUS_ABSENT, $feedback[0]['status']);  // E (first)
        $this->assertEquals(Guess::STATUS_ABSENT, $feedback[1]['status']);  // E (second)
        $this->assertEquals(Guess::STATUS_PRESENT, $feedback[2]['status']); // R
        $this->assertEquals(Guess::STATUS_ABSENT, $feedback[3]['status']);  // I
        $this->assertEquals(Guess::STATUS_CORRECT, $feedback[4]['status']); // E (third)
    }

    public function test_is_winning_feedback(): void
    {
        $winningFeedback = [
            ['letter' => 'A', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'P', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'P', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'L', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'E', 'status' => Guess::STATUS_CORRECT],
        ];

        $this->assertTrue($this->service->isWinningFeedback($winningFeedback));

        $losingFeedback = [
            ['letter' => 'A', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'P', 'status' => Guess::STATUS_PRESENT],
            ['letter' => 'P', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'L', 'status' => Guess::STATUS_CORRECT],
            ['letter' => 'E', 'status' => Guess::STATUS_CORRECT],
        ];

        $this->assertFalse($this->service->isWinningFeedback($losingFeedback));
    }

    public function test_generate_shareable_result(): void
    {
        $allFeedback = [
            [
                ['letter' => 'A', 'status' => Guess::STATUS_ABSENT],
                ['letter' => 'B', 'status' => Guess::STATUS_PRESENT],
                ['letter' => 'C', 'status' => Guess::STATUS_CORRECT],
                ['letter' => 'D', 'status' => Guess::STATUS_ABSENT],
                ['letter' => 'E', 'status' => Guess::STATUS_PRESENT],
            ],
        ];

        $result = $this->service->generateShareableResult($allFeedback);

        $this->assertEquals('⬜🟨🟩⬜🟨', $result);
    }

    public function test_case_insensitivity(): void
    {
        $feedbackUpper = $this->service->calculateFeedback('APPLE', 'APPLE');
        $feedbackLower = $this->service->calculateFeedback('apple', 'apple');
        $feedbackMixed = $this->service->calculateFeedback('ApPlE', 'aPpLe');

        $this->assertEquals($feedbackUpper, $feedbackLower);
        $this->assertEquals($feedbackUpper, $feedbackMixed);
    }
}
