<div class="game-container" x-data="{ }" @keydown.window="$wire.keyPress($event.key.toUpperCase())">
    {{-- Error Toast --}}
    @if($errorMessage)
        <div class="error-toast" wire:key="error-{{ now()->timestamp }}">
            {{ $errorMessage }}
        </div>
    @endif

    {{-- Game Grid --}}
    <div class="grid-container">
        {{-- Completed guesses --}}
        @foreach($guesses as $index => $guess)
            <div class="grid-row" wire:key="row-{{ $index }}">
                @foreach($guess['feedback'] as $i => $item)
                    <div class="grid-tile {{ $item['status'] }}" wire:key="tile-{{ $index }}-{{ $i }}">
                        {{ $item['letter'] }}
                    </div>
                @endforeach
            </div>
        @endforeach

        {{-- Current guess row (if game in progress) --}}
        @if(!$isCompleted)
            <div class="grid-row" wire:key="current-row">
                @foreach($this->currentGuessLetters as $i => $letter)
                    <div class="grid-tile {{ $letter ? 'filled' : '' }}" wire:key="current-tile-{{ $i }}">
                        {{ $letter }}
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Empty rows --}}
        @foreach($this->emptyRows as $index => $empty)
            <div class="grid-row" wire:key="empty-row-{{ $index }}">
                @for($i = 0; $i < 5; $i++)
                    <div class="grid-tile" wire:key="empty-tile-{{ $index }}-{{ $i }}"></div>
                @endfor
            </div>
        @endforeach
    </div>

    {{-- Keyboard --}}
    <div class="keyboard">
        @foreach($keyboardRows as $rowIndex => $row)
            <div class="keyboard-row">
                @foreach($row as $key)
                    @php
                        $keyState = $keyboardState[$key] ?? '';
                        $isWide = in_array($key, ['ENTER', 'BACKSPACE']);
                    @endphp
                    <button 
                        class="key {{ $keyState }} {{ $isWide ? 'wide' : '' }}"
                        wire:click="keyPress('{{ $key }}')"
                        wire:key="key-{{ $key }}"
                    >
                        @if($key === 'BACKSPACE')
                            ⌫
                        @else
                            {{ $key }}
                        @endif
                    </button>
                @endforeach
            </div>
        @endforeach
    </div>

    {{-- Result Modal --}}
    @if($showModal)
        <div class="modal-overlay" wire:click.self="closeModal">
            <div class="modal">
                @if($isWon)
                    <h2>🎉 Congratulations!</h2>
                    <p>You guessed the word in {{ $attemptsUsed }} {{ $attemptsUsed === 1 ? 'try' : 'tries' }}!</p>
                @else
                    <h2>😔 Game Over</h2>
                    <p>Better luck next time!</p>
                @endif

                <div class="target-word">{{ $targetWord }}</div>

                <div class="share-box">{{ $shareText }}</div>

                <div class="btn-group">
                    <button class="btn" wire:click="copyToClipboard">
                        Share
                    </button>
                    <button class="btn btn-secondary" wire:click="closeModal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
