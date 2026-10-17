<?php

use App\Http\Controllers\CharacterController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/characters');

Route::get('/characters', [CharacterController::class, 'index'])->name('characters.index');
