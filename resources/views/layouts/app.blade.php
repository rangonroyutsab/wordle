<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Wordle') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Styles -->
    <style>
        *, *::before, *::after {
            box-sizing: border-box;
        }

        :root {
            --color-correct: #538d4e;
            --color-present: #b59f3b;
            --color-absent: #3a3a3c;
            --color-empty: #121213;
            --color-border: #3a3a3c;
            --color-key: #818384;
            --color-text: #ffffff;
            --color-bg: #121213;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            border-bottom: 1px solid var(--color-border);
            padding: 1rem;
            text-align: center;
        }

        .header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.2rem;
        }

        .game-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            max-width: 500px;
            margin: 0 auto;
            width: 100%;
        }

        /* Grid Styles */
        .grid-container {
            display: flex;
            flex-direction: column;
            gap: 5px;
            margin-bottom: 1rem;
        }

        .grid-row {
            display: flex;
            gap: 5px;
        }

        .grid-tile {
            width: 62px;
            height: 62px;
            border: 2px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 700;
            text-transform: uppercase;
            transition: transform 0.1s;
        }

        .grid-tile.filled {
            border-color: #565758;
        }

        .grid-tile.correct {
            background-color: var(--color-correct);
            border-color: var(--color-correct);
        }

        .grid-tile.present {
            background-color: var(--color-present);
            border-color: var(--color-present);
        }

        .grid-tile.absent {
            background-color: var(--color-absent);
            border-color: var(--color-absent);
        }

        .grid-tile.pop {
            animation: pop 0.1s ease-in-out;
        }

        @keyframes pop {
            50% { transform: scale(1.1); }
        }

        .grid-row.shake {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        .grid-tile.reveal {
            animation: flip 0.5s ease forwards;
        }

        @keyframes flip {
            0% { transform: rotateX(0); }
            50% { transform: rotateX(90deg); }
            100% { transform: rotateX(0); }
        }

        /* Keyboard Styles */
        .keyboard {
            width: 100%;
            max-width: 500px;
        }

        .keyboard-row {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-bottom: 8px;
        }

        .key {
            height: 58px;
            min-width: 43px;
            padding: 0 15px;
            border-radius: 4px;
            border: none;
            background-color: var(--color-key);
            color: var(--color-text);
            font-size: 0.875rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-transform: uppercase;
            transition: background-color 0.2s;
        }

        .key:hover {
            opacity: 0.8;
        }

        .key.wide {
            min-width: 65px;
            font-size: 0.75rem;
        }

        .key.correct {
            background-color: var(--color-correct);
        }

        .key.present {
            background-color: var(--color-present);
        }

        .key.absent {
            background-color: var(--color-absent);
        }

        /* Error Message */
        .error-toast {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background-color: var(--color-text);
            color: var(--color-bg);
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            font-weight: 600;
            z-index: 100;
            animation: fadeIn 0.2s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateX(-50%) translateY(-10px); }
            to { opacity: 1; transform: translateX(-50%) translateY(0); }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 200;
        }

        .modal {
            background-color: #1a1a1b;
            border-radius: 8px;
            padding: 2rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
        }

        .modal h2 {
            margin: 0 0 1rem;
            font-size: 1.5rem;
        }

        .modal p {
            margin: 0 0 1rem;
            color: #818384;
        }

        .target-word {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 0.2rem;
            margin-bottom: 1.5rem;
        }

        .share-box {
            background-color: var(--color-bg);
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: monospace;
            white-space: pre-line;
            text-align: left;
            line-height: 1.4;
        }

        .btn {
            background-color: var(--color-correct);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            text-transform: uppercase;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .btn-secondary {
            background-color: var(--color-absent);
            margin-left: 0.5rem;
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
        }

        .stat-label {
            font-size: 0.75rem;
            color: #818384;
        }
    </style>

    @livewireStyles
</head>
<body>
    <header class="header">
        <h1>WORDLE</h1>
    </header>

    <main>
        {{ $slot }}
    </main>

    @livewireScripts

    <script>
        // Listen for custom events from Livewire
        document.addEventListener('livewire:initialized', () => {
            // Handle keyboard input
            document.addEventListener('keydown', (e) => {
                if (e.ctrlKey || e.metaKey || e.altKey) return;
                
                const key = e.key.toUpperCase();
                
                if (key === 'ENTER' || key === 'BACKSPACE' || (key.length === 1 && key.match(/[A-Z]/))) {
                    Livewire.dispatch('keyPress', { key: key === 'BACKSPACE' ? 'BACKSPACE' : key });
                }
            });

            // Handle copy to clipboard
            Livewire.on('copy-to-clipboard', (event) => {
                navigator.clipboard.writeText(event.text).then(() => {
                    alert('Copied to clipboard!');
                });
            });

            // Handle shake animation
            Livewire.on('shake-row', (event) => {
                const rows = document.querySelectorAll('.grid-row');
                if (rows[event.row]) {
                    rows[event.row].classList.add('shake');
                    setTimeout(() => rows[event.row].classList.remove('shake'), 500);
                }
            });
        });
    </script>
</body>
</html>
