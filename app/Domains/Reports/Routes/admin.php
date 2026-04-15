<?php

use App\Domains\Reports\Livewire\Admin\Reports\Index as AdminReportsIndex;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')
	->name('reports.')
	->group(function (): void {
		Route::get('/', AdminReportsIndex::class)
			->name('index');
	});
