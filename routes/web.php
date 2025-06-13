<?php

use Inertia\Inertia;
use App\Http\Controllers\InstructionsController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use App\Http\Controllers\AskController;
use App\Http\Controllers\SharedConversationController;

Route::get('shared/{conversation}', [SharedConversationController::class, 'show'])->name('conversation.share');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // Routes chat
    Route::get('ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('ask', [AskController::class, 'ask'])->name('ask.post');
    Route::post('ask/new', [AskController::class, 'createConversation'])->name('ask.new');
    Route::post('ask/{id}/share', [AskController::class, 'share'])->name('ask.share');
    Route::delete('ask/{id}', [AskController::class, 'deleteConversation'])->name('ask.delete');
    Route::get('/instructions', [InstructionsController::class, 'edit'])->name('instructions.edit');
    Route::post('/instructions', [InstructionsController::class, 'update'])->name('instructions.update');
});
