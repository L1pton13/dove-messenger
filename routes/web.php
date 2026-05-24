<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MessageController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [ContactController::class, 'getUserChats'])->name('dashboard');
    Route::get('/search-contact', [ContactController::class, 'search'])->name('contacts.search');
    Route::post('conversations', [ContactController::class, 'startConversation'])->name('conversations.start');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::delete('/profile/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store');
    Route::post('/conversations/{conversation}/read', [MessageController::class, 'markAsRead']);
    Route::delete('/messages/{message}', [MessageController::class, 'destroy']);
    Route::post('/conversations/{conversation}/clear-for-me', [MessageController::class, 'clearForMe']);
    Route::post('/conversations/{conversation}/clear-own-for-everyone', [MessageController::class, 'clearOwnForEveryone']);       
});

require __DIR__.'/auth.php';
