<?php

use App\Core\UI\Navigation\DTO\NavItem;
use App\Core\UI\Navigation\DTO\NavSectionEnum;
use App\Core\UI\Navigation\Services\NavigationManager;

it('can register a section and item and resolve it', function () {
    $manager = new NavigationManager;

    $manager->registerSection('user', 'User', 0);

    $item = new NavItem(
        id: 'home',
        label: 'Home',
        icon: null,
        url: '/home',
        route: null,
        group: null,
        order: 0,
        active: false,
        visible: true,
        permissions: [],
        section: NavSectionEnum::USER,
        meta: []
    );

    $manager->registerItem('user', null, $item);

    $sections = $manager->resolve();

    expect(count($sections))->toBeGreaterThanOrEqual(1);
    $found = null;
    foreach ($sections as $sec) {
        if ($sec['key'] === 'user') {
            $found = $sec;
            break;
        }
    }

    expect($found)->not->toBeNull();
    expect($found['items'][0]['label'])->toBe('Home');
});
