<?php

use App\Livewire\Layouts\App;
use Livewire\Component;

it('defines a dedicated livewire app layout class', function (): void {
    expect(is_subclass_of(App::class, Component::class))->toBeTrue();
});

it('maps the app layout class to the shared app layout view', function (): void {
    $source = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/App.php');

    expect($source)->toContain("return view('livewire.layouts.app');");
});
