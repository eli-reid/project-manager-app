<?php

use App\Core\Queue\Http\Controllers\QueueController;
use App\Core\Queue\Livewire\Admin\QueueMonitor;
use Illuminate\Support\Facades\Route;

// Queue Monitoring Routes - Admin only
Route::middleware(['auth', 'can:queue.manage'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::livewire('queue-monitor', QueueMonitor::class)->name('queue-monitor.index');

    // Queue processing
    Route::post('queue/process', [QueueController::class, 'store'])->name('queue.process');
});
