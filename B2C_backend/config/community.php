<?php

return [
    'pagination' => [
        'default_per_page' => env('API_PER_PAGE', 20),
        'max_per_page' => env('API_MAX_PER_PAGE', 50),
    ],
    'uploads' => [
        'disk' => env('COMMUNITY_UPLOAD_DISK', env('FILESYSTEM_DISK', 's3')),
    ],
    'idea_media' => [
        'directory' => env('IDEA_MEDIA_DIRECTORY', 'ideas'),
        'max_files' => (int) env('IDEA_MEDIA_MAX_FILES', 12),
        'max_external_links' => (int) env('IDEA_MEDIA_MAX_EXTERNAL_LINKS', 4),
        'max_file_size_kb' => (int) env('IDEA_MEDIA_MAX_FILE_SIZE_KB', 10240),
        'allowed_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('IDEA_MEDIA_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,webp,gif,pdf,doc,docx,ppt,pptx,xls,xlsx'))
        ))),
        'image_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('IDEA_MEDIA_IMAGE_EXTENSIONS', 'jpg,jpeg,png,webp,gif'))
        ))),
        'document_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('IDEA_MEDIA_DOCUMENT_EXTENSIONS', 'pdf,doc,docx,ppt,pptx,xls,xlsx'))
        ))),
    ],
];
