# Wordle Clone

A full-featured Wordle clone built with Laravel 12, Livewire, and SQLite. Features daily words, guess validation, color-coded feedback, and statistics tracking.

## Features

- **Daily Word Challenge**: One new word per day, same for all players
- **Guess Validation**: Only valid 5-letter words from the dictionary are accepted
- **Color-Coded Feedback**: 
  - 🟩 Green: Correct letter in correct position
  - 🟨 Yellow: Correct letter in wrong position  
  - ⬛ Gray: Letter not in word
- **Interactive Keyboard**: On-screen keyboard with color-coded hints
- **Guest Play**: Play without registration using session-based identification
- **Statistics Tracking**: Win rate, streaks, and guess distribution (for authenticated users)
- **Share Results**: Copy shareable emoji grid to clipboard

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Livewire 4, Alpine.js, Tailwind CSS
- **Database**: SQLite (easily switchable to MySQL/PostgreSQL)
- **Testing**: PHPUnit with 23 tests covering services, APIs, and features

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & npm (for frontend assets)

### Setup

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd wordle
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations**
   ```bash
   php artisan migrate
   ```

5. **Import word dictionary**
   ```bash
   php artisan wordle:import-words
   ```

6. **Generate daily words** (optional - creates words for next 30 days)
   ```bash
   php artisan wordle:set-daily-word --days=30
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

8. **Start the development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to play!

## Scheduled Tasks

Add to your server's crontab for automatic daily word generation:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

This will automatically select a new word each day at midnight.

## API Endpoints

### Game Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/game/today` | Get or create today's game |
| GET | `/api/game/{id}` | Get specific game by ID |
| POST | `/api/game/guess` | Submit a guess |
| GET | `/api/game/share` | Get shareable result text |

### Statistics Endpoints (Authenticated)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/statistics` | Get user's game statistics |

### Example API Usage

**Get today's game:**
```bash
curl http://localhost:8000/api/game/today
```

**Submit a guess:**
```bash
curl -X POST http://localhost:8000/api/game/guess \
  -H "Content-Type: application/json" \
  -d '{"word": "CRANE"}'
```

## Project Structure

```
app/
├── Console/Commands/
│   ├── ImportWordsCommand.php     # Import word dictionary
│   └── SetDailyWordCommand.php    # Set daily challenge words
├── Exceptions/
│   └── GameException.php          # Custom game exceptions
├── Http/
│   ├── Controllers/Api/
│   │   ├── GameController.php     # Game API endpoints
│   │   └── StatisticsController.php
│   ├── Requests/
│   │   └── GuessRequest.php       # Guess validation
│   └── Resources/
│       ├── GameResource.php       # Game JSON transformation
│       └── GuessResource.php
├── Livewire/
│   └── WordleGame.php             # Main game component
├── Models/
│   ├── DailyWord.php              # Daily word selection
│   ├── Game.php                   # Player game state
│   ├── Guess.php                  # Individual guesses
│   ├── UserStatistic.php          # Player statistics
│   └── Word.php                   # Word dictionary
└── Services/
    ├── FeedbackService.php        # Calculate guess feedback
    ├── GameService.php            # Game orchestration
    ├── StatisticsService.php      # Stats calculation
    └── WordValidationService.php  # Dictionary validation
```

## Testing

Run all tests:
```bash
./vendor/bin/phpunit
```

Run specific test suites:
```bash
# Unit tests only
./vendor/bin/phpunit --testsuite=Unit

# Feature tests only
./vendor/bin/phpunit --testsuite=Feature

# Specific test file
./vendor/bin/phpunit --filter=FeedbackServiceTest
```

### Test Coverage

- **FeedbackService**: 8 tests for feedback calculation edge cases
- **GameService**: 7 tests for game flow and state management
- **API**: 6 tests for REST endpoints
- **Feature**: 2 tests for web routes

## Configuration

### Environment Variables

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_NAME` | Application name | Wordle |
| `DB_CONNECTION` | Database driver | sqlite |
| `SESSION_DRIVER` | Session storage | database |

### Switching to MySQL

1. Update `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=wordle
   DB_USERNAME=root
   DB_PASSWORD=
   ```

2. Run migrations:
   ```bash
   php artisan migrate:fresh
   php artisan wordle:import-words
   php artisan wordle:set-daily-word --days=30
   ```

## Game Rules

1. Guess the 5-letter word in 6 tries
2. Each guess must be a valid word from the dictionary
3. After each guess, tiles show feedback:
   - **Green**: Letter is correct and in the right spot
   - **Yellow**: Letter is in the word but wrong spot
   - **Gray**: Letter is not in the word
4. One word per day - everyone plays the same word

## License

MIT License

## Contributing

1. Fork the repository
2. Create a feature branch
3. Write tests for new features
4. Submit a pull request

## Acknowledgments

- Inspired by the original [Wordle](https://www.nytimes.com/games/wordle/index.html) by Josh Wardle
- Word list sourced from common 5-letter English words
