<?php

use App\Http\Controllers\CharacterController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/characters');

Route::get('/characters', [CharacterController::class, 'index'])->name('characters.index');

// Digits only: "/characters/abc" is a 404 before any code runs.
Route::get('/characters/{id}', [CharacterController::class, 'show'])->whereNumber('id')->name('characters.show');
