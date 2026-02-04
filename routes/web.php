<?php

use App\Livewire\WordleGame;
use Illuminate\Support\Facades\Route;

Route::get('/', WordleGame::class)->name('home');
