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

it('defines a dedicated livewire head layout class', function (): void {
    $source = file_get_contents(__DIR__.'/../../../../../app/Livewire/Layouts/Head.php');

    expect($source)->toContain('class Head extends Component');
    expect($source)->toContain("return view('livewire.layouts.head');");
});

it('uses the shared livewire head component in all primary livewire layouts', function (): void {
    $appLayout = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/app.blade.php');
    $mobileLayout = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/mobile.blade.php');
    $publicShareLayout = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/public-share.blade.php');
    $authCardLayout = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/auth/card.blade.php');
    $authSimpleLayout = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/auth/simple.blade.php');
    $authSplitLayout = file_get_contents(__DIR__.'/../../../../../resources/views/livewire/layouts/auth/split.blade.php');

    expect($appLayout)->toContain("@include('livewire.layouts.head', ['title' => \$title ?? null])");
    expect($mobileLayout)->toContain("@include('livewire.layouts.head', ['title' => \$title ?? null])");
    expect($publicShareLayout)->toContain("@include('livewire.layouts.head', ['title' => \$title ?? null])");
    expect($authCardLayout)->toContain("@include('livewire.layouts.head', ['title' => \$title ?? null])");
    expect($authSimpleLayout)->toContain("@include('livewire.layouts.head', ['title' => \$title ?? null])");
    expect($authSplitLayout)->toContain("@include('livewire.layouts.head', ['title' => \$title ?? null])");
});
