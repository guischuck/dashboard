<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/appearance');
    })->name('appearance');

    // Rotas de usuários
    Route::get('settings/users', [UserController::class, 'index'])->name('users.index');
    Route::post('settings/users', [UserController::class, 'store'])->name('users.store');
    Route::patch('settings/users/{user}/status', [UserController::class, 'updateStatus'])->name('users.update-status');
    Route::patch('settings/users/{user}/password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    Route::delete('settings/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
});
