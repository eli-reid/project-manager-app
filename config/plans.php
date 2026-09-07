<?php

return [
    'enabled' => false,
    'rasterizer_driver' => 'poppler',
    'poppler_bin_path' => '',
    'process_timeout' => 300,
    'render_dpi' => 150,
    'thumbnail_width' => 320,
    'preview_width' => 2000,
    'enable_tiles' => false,
    'derivative_format' => 'webp',
    'storage_disk' => 'local',
    'max_pages_per_set' => 500,
    'max_upload_kilobytes' => 512000,
    'title_block_region' => 'bottom-right',
    'sheet_number_pattern' => '^[A-Z]{1,3}[-.]?\\d{1,3}(\\.\\d+)?$',
    'null_page_count' => 1,
];
