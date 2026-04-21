<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TicketWatcherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
Route::post('/tickets/{ticket}/internal-notes', [TicketCommentController::class, 'storeInternal'])->name('tickets.internal-notes.store');
Route::post('/tickets/{ticket}/watchers', [TicketWatcherController::class, 'store'])->name('tickets.watchers.store');
Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
Route::patch('/tickets/{ticket}/assignee', [TicketController::class, 'updateAssignee'])->name('tickets.assignee.update');
Route::patch('/tickets/{ticket}/category', [TicketController::class, 'updateCategory'])->name('tickets.category.update');
Route::patch('/tickets/{ticket}/close', [TicketController::class, 'close'])->name('tickets.close');
Route::patch('/tickets/{ticket}/pin', [TicketController::class, 'updatePin'])->name('tickets.pin.update');
Route::patch('/tickets/{ticket}/priority', [TicketController::class, 'updatePriority'])->name('tickets.priority.update');
Route::patch('/tickets/{ticket}/reopen', [TicketController::class, 'reopen'])->name('tickets.reopen');
Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status.update');
Route::patch('/tickets/{ticket}/visibility', [TicketController::class, 'updateVisibility'])->name('tickets.visibility.update');
Route::delete('/tickets/{ticket}/watchers', [TicketWatcherController::class, 'destroy'])->name('tickets.watchers.destroy');
