<?php

declare(strict_types=1);

namespace App\Domains\Plans\Http\Controllers;

use App\Core\Settings\Facades\Settings;
use App\Domains\Plans\Models\PlanSheetRevision;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

final class PlanImageController
{
    public function __invoke(Request $request, PlanSheetRevision $revision, string $variant): Response
    {
        abort_unless(in_array($variant, ['thumb', 'preview'], true), 404);
        abort_unless($request->user()?->can('view', $revision->sheet) === true, 403);
        $path = $variant === 'thumb' ? $revision->thumbnail_path : $revision->preview_path;
        $disk = Storage::disk(Settings::get('plans.storage_disk', 'local')->toString());
        abort_unless(filled($path) && $disk->exists($path), 404);
        $etag = '"'.sha1((string) $path).'"';
        if ($request->headers->get('If-None-Match') === $etag) {
            return response('', 304, ['ETag' => $etag]);
        }

        return $disk->response($path, basename($path), [
            'Cache-Control' => 'private, max-age=31536000, immutable',
            'ETag' => $etag,
        ]);
    }
}
