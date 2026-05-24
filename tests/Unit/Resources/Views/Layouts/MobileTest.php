<?php

use App\Livewire\Layouts\Mobile;
use Livewire\Component;

it('defines a dedicated livewire mobile layout class', function (): void {
    expect(is_subclass_of(Mobile::class, Component::class))->toBeTrue();
});

it('maps the mobile layout class to the shared mobile layout view', function (): void {
    $source = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/Mobile.php');

    expect($source)->toContain("return view('livewire.layouts.mobile'");
    expect($source)->toContain("'mobileDashboardFallbackUrl' => ");
    expect($source)->toContain("route('mobile.dashboard')");
});
