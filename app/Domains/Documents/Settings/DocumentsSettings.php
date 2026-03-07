<?php

namespace App\Domains\Documents\Settings;

use App\Core\Settings\Contracts\DomainSettingsProvider;

class DocumentsSettings implements DomainSettingsProvider
{
    public static function settings(): array
    {
        return [
            [
                'key' => 'documents.allowed_types',
                'value' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,webp,svg',
                'display_name' => 'Allowed File Types',
                'description' => 'Comma-separated list of allowed file extensions',
                'type' => 'text',
                'group' => 'documents',
                'order' => 1,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'documents.max_file_size',
                'value' => '10240',
                'display_name' => 'Maximum File Size (KB)',
                'description' => 'Maximum file size in kilobytes',
                'type' => 'number',
                'group' => 'documents',
                'order' => 2,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'documents.enable_versioning',
                'value' => 'false',
                'display_name' => 'Enable File Versioning',
                'description' => 'Enable versioning for uploaded documents',
                'type' => 'select',
                'group' => 'documents',
                'options' => [
                    'true' => 'Yes',
                    'false' => 'No',
                ],
                'order' => 3,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
            [
                'key' => 'documents.storage_disk',
                'value' => 'local',
                'display_name' => 'Document Storage Disk',
                'description' => 'Storage disk for project documents',
                'type' => 'select',
                'group' => 'documents',
                'options' => [
                    'local' => 'Local Storage',
                    's3' => 'Amazon S3',
                    'public' => 'Public Storage',
                ],
                'order' => 4,
                'is_visible' => true,
                'is_public' => false,
                'is_required' => false,
                'encrypted' => false,
            ],
        ];
    }
}
