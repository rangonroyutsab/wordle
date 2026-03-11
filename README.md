# Wordle Clone

A full-featured Wordle clone built with Laravel 12, Livewire, and MySQL. Features daily words, guess validation, color-coded feedback, and statistics tracking.

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

- **Backend**: Laravel 12, PHP 8.4+
- **Frontend**: Livewire 4, Alpine.js, Tailwind CSS
- **Database**: MySQL 8+
- **Testing**: PHPUnit with 23 tests covering services, APIs, and features

## Installation

### Prerequisites

- PHP 8.4 or higher
- Composer
- Node.js & npm (for frontend assets)
- MySQL 8+

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
   Then update the database credentials in `.env` to match your MySQL setup:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=wordle
   DB_USERNAME=your_user
   DB_PASSWORD=your_password
   ```

4. **Create the database**
   ```sql
   CREATE DATABASE wordle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. **Run migrations**
   ```bash
   php artisan migrate
   ```

6. **Import word dictionary**
   ```bash
   php artisan wordle:import-words
   ```

7. **Generate daily words** (optional - creates words for next 30 days)
   ```bash
   php artisan wordle:set-daily-word --days=30
   ```

8. **Build frontend assets**
   ```bash
   npm run build
   ```

9. **Start the development server**
   ```bash
   php artisan serve
   ```

Visit `http://localhost:8000` to play!

## Running with Docker

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/) installed and running

### Build and Run

1. **Build the image**
   ```bash
   docker build -t wordle .
   ```

2. **Run the container**
   ```bash
   docker run -d \
     -p 8080:8080 \
     -e APP_KEY=base64:$(openssl rand -base64 32) \
     -e APP_ENV=production \
     -e APP_DEBUG=false \
     --name wordle \
     wordle
   ```

3. Visit `http://localhost:8080` to play.

### Environment Variables

Pass any `.env` values as `-e` flags or use an env file:

```bash
docker run -d -p 8080:8080 --env-file .env --name wordle wordle
```

The container entrypoint automatically runs migrations and caches config/routes/views on startup.

---

## Deploying to Railway

### Prerequisites

- A [Railway](https://railway.app) account
- The [Railway CLI](https://docs.railway.app/develop/cli) (optional, for CLI-based deploys)

### Deploy via GitHub

1. Push this repository to GitHub.
2. In the Railway dashboard, click **New Project → Deploy from GitHub repo** and select this repo.
3. Railway will detect the `Dockerfile` and build automatically.

### Required Environment Variables

Set these in your Railway service's **Variables** tab:

| Variable | Description | Example |
|----------|-------------|---------|
| `APP_KEY` | Laravel application key | `base64:...` (generate with `php artisan key:generate --show`) |
| `APP_ENV` | Environment | `production` |
| `APP_DEBUG` | Debug mode | `false` |
| `APP_URL` | Your Railway public URL | `https://your-app.railway.app` |

### Optional: Deploy via CLI

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login and link project
railway login
railway link

# Deploy
railway up
```

---

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

Tests run against a dedicated MySQL database (`wordle_test`). Create it before running the suite:

```sql
CREATE DATABASE wordle_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Update `DB_USERNAME` / `DB_PASSWORD` in `phpunit.xml` if your local MySQL credentials differ from the defaults.

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
| `DB_CONNECTION` | Database driver | `mysql` |
| `DB_HOST` | MySQL host | `127.0.0.1` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_DATABASE` | Database name | `wordle` |
| `DB_USERNAME` | Database user | `root` |
| `DB_PASSWORD` | Database password | _(empty)_ |
| `SESSION_DRIVER` | Session storage | `database` |

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
