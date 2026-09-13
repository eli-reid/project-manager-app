<?php

declare(strict_types=1);

use App\Domains\Plans\Jobs\RenderPlanPageJob;
use Illuminate\Bus\Batchable;

it('uses the Batchable trait on RenderPlanPageJob', function (): void {
    $uses = class_uses_recursive(RenderPlanPageJob::class);

    expect($uses)->toContain(Batchable::class);
});
