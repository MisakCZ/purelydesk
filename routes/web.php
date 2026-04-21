<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\TicketCommentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
Route::get('/tickets', [TicketController::class, 'index'])->name('tickets.index');
Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])->name('announcements.edit');
Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update'])->name('announcements.update');
Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
Route::post('/tickets/{ticket}/comments', [TicketCommentController::class, 'store'])->name('tickets.comments.store');
Route::post('/tickets/{ticket}/internal-notes', [TicketCommentController::class, 'storeInternal'])->name('tickets.internal-notes.store');
Route::patch('/tickets/{ticket}/assignee', [TicketController::class, 'updateAssignee'])->name('tickets.assignee.update');
Route::patch('/tickets/{ticket}/status', [TicketController::class, 'updateStatus'])->name('tickets.status.update');
