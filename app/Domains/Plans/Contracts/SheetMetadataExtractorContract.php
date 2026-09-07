<?php

declare(strict_types=1);

namespace App\Domains\Plans\Contracts;

interface SheetMetadataExtractorContract
{
    /**
     * @return array{sheet_number:?string,title:?string,confidence:float,source:string,text:string}
     */
    public function extract(string $absolutePdfPath, int $page): array;
}
