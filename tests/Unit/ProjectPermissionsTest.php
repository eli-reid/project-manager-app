<?php

use App\Domains\Projects\Permissions\ProjectPermissions;

it('contains project document permissions', function () {
    $actions = array_column(ProjectPermissions::all(), 'action');

    expect($actions)->toContain('view-documents')->toContain('upload-documents');
});
