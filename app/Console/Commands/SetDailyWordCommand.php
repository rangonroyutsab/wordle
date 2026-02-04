<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\DailyWord;
use App\Models\Word;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Command to set the daily word for today or a specific date.
 */
class SetDailyWordCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wordle:set-daily-word 
                            {--date= : The date to set the word for (YYYY-MM-DD)}
                            {--word= : Specific word to set (must exist in database)}
                            {--days=7 : Number of days ahead to generate words for}';

    /**
     * The console command description.
     */
    protected $description = 'Set the daily word for today or a specific date';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dateOption = $this->option('date');
        $wordOption = $this->option('word');
        $daysAhead = (int) $this->option('days');

        // If specific word is provided, set it for today or specified date
        if ($wordOption) {
            return $this->setSpecificWord($dateOption, strtoupper($wordOption));
        }

        // Generate words for multiple days
        return $this->generateDailyWords($dateOption, $daysAhead);
    }

    /**
     * Set a specific word for a date.
     */
    private function setSpecificWord(?string $dateOption, string $word): int
    {
        $date = $dateOption ? Carbon::parse($dateOption) : Carbon::today();

        // Check if word exists
        $wordModel = Word::where('word', $word)->where('is_solution', true)->first();

        if (!$wordModel) {
            $this->error("Word '{$word}' not found in solution words.");
            return Command::FAILURE;
        }

        // Check if date already has a word
        $existing = DailyWord::where('game_date', $date)->first();

        if ($existing) {
            if (!$this->confirm("Date {$date->format('Y-m-d')} already has word '{$existing->word->word}'. Replace?")) {
                return Command::SUCCESS;
            }
            $existing->delete();
        }

        DailyWord::create([
            'word_id' => $wordModel->id,
            'game_date' => $date,
        ]);

        $this->info("Set word '{$word}' for {$date->format('Y-m-d')}");
        return Command::SUCCESS;
    }

    /**
     * Generate random daily words for upcoming days.
     */
    private function generateDailyWords(?string $startDateOption, int $days): int
    {
        $startDate = $startDateOption ? Carbon::parse($startDateOption) : Carbon::today();

        $this->info("Generating daily words from {$startDate->format('Y-m-d')} for {$days} days...");

        // Get recently used word IDs (last 365 days)
        $recentWordIds = DailyWord::where('game_date', '>=', Carbon::today()->subDays(365))
            ->pluck('word_id')
            ->toArray();

        $bar = $this->output->createProgressBar($days);
        $bar->start();

        $generated = 0;

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);

            // Skip if already exists
            if (DailyWord::where('game_date', $date)->exists()) {
                $bar->advance();
                continue;
            }

            // Get available words (not recently used)
            $word = Word::solution()
                ->whereNotIn('id', $recentWordIds)
                ->inRandomOrder()
                ->first();

            // Fallback if all words have been used
            if (!$word) {
                $word = Word::solution()->inRandomOrder()->first();
            }

            if (!$word) {
                $this->newLine();
                $this->error('No solution words available. Run wordle:import-words first.');
                return Command::FAILURE;
            }

            DailyWord::create([
                'word_id' => $word->id,
                'game_date' => $date,
            ]);

            $recentWordIds[] = $word->id;
            $generated++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Generated {$generated} daily words.");

        return Command::SUCCESS;
    }
}
