<?php

return [
    'enabled' => env('PLANS_ENABLED', true),
    'rasterizer_driver' => env('PLANS_RASTERIZER_DRIVER', 'imagick'),
    'poppler_bin_path' => env('PLANS_POPPLER_BIN_PATH', ''),
    'ghostscript_bin_path' => env('PLANS_GHOSTSCRIPT_BIN_PATH', ''),
    'process_timeout' => env('PLANS_PROCESS_TIMEOUT', 300),
    'render_dpi' => env('PLANS_RENDER_DPI', 150),
    'thumbnail_width' => env('PLANS_THUMBNAIL_WIDTH', 320),
    'preview_width' => env('PLANS_PREVIEW_WIDTH', 2000),
    'enable_tiles' => env('PLANS_ENABLE_TILES', false),
    'derivative_format' => env('PLANS_DERIVATIVE_FORMAT', 'webp'),
    'storage_disk' => env('PLANS_STORAGE_DISK', 'local'),
    'max_pages_per_set' => env('PLANS_MAX_PAGES_PER_SET', 500),
    'max_upload_kilobytes' => env('PLANS_MAX_UPLOAD_KILOBYTES', 512000),
    'title_block_region' => env('PLANS_TITLE_BLOCK_REGION', 'right-strip'),
    'sheet_number_pattern' => env('PLANS_SHEET_NUMBER_PATTERN', '(?<![A-Z0-9])[A-Z]{1,3}[\\s.-]?\\d{1,3}(?:\\.\\d+)?(?![A-Z0-9])'),
    'null_page_count' => env('PLANS_NULL_PAGE_COUNT', 1),
    'ocr_driver' => env('PLANS_OCR_DRIVER', 'ghostscript'),
    'ocr_fallback_enabled' => env('PLANS_OCR_FALLBACK_ENABLED', true),
    'ocr_min_text_length' => env('PLANS_OCR_MIN_TEXT_LENGTH', 12),
];
