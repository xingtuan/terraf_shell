<?php

return [
    'pagination' => [
        'default_per_page' => env('API_PER_PAGE', 20),
        'max_per_page' => env('API_MAX_PER_PAGE', 50),
    ],
    'discovery' => [
        'weights' => [
            'like' => 3,
            'comment' => 4,
            'favorite' => 2,
        ],
        'trending_window_days' => 7,
        'trending_recency_boost_hours' => 168,
    ],
    'uploads' => [
        'disk' => env('COMMUNITY_UPLOAD_DISK', env('FILESYSTEM_DISK', 'public')),
        'allow_guest_upload' => (bool) env('ALLOW_GUEST_UPLOAD', false),
        'azure' => [
            'use_sas_urls' => (bool) env('AZURE_STORAGE_USE_SAS_URLS', true),
            'signed_url_ttl_minutes' => (int) env('AZURE_STORAGE_SAS_URL_TTL_MINUTES', 10080),
        ],
    ],
    'idea_media' => [
        'directory' => env('IDEA_MEDIA_DIRECTORY', 'ideas'),
        'max_files' => (int) env('IDEA_MEDIA_MAX_FILES', 12),
        'max_external_links' => (int) env('IDEA_MEDIA_MAX_EXTERNAL_LINKS', 4),
        'max_file_size_kb' => (int) env('IDEA_MEDIA_MAX_FILE_SIZE_KB', env('MEDIA_ATTACHMENT_MAX_FILE_SIZE_KB', 10240)),
        'max_image_size_kb' => (int) env('IDEA_MEDIA_IMAGE_MAX_FILE_SIZE_KB', env('MEDIA_IMAGE_MAX_FILE_SIZE_KB', 5120)),
        'max_attachment_size_kb' => (int) env('IDEA_MEDIA_ATTACHMENT_MAX_FILE_SIZE_KB', env('MEDIA_ATTACHMENT_MAX_FILE_SIZE_KB', 10240)),
        'allowed_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('IDEA_MEDIA_ALLOWED_EXTENSIONS', 'jpg,jpeg,png,webp,gif,pdf,doc,docx,ppt,pptx,xls,xlsx,txt,md,csv,zip,rar,7z,stl,obj,glb,gltf,dwg,dxf,step,stp,iges,igs,srt'))
        ))),
        'image_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('IDEA_MEDIA_IMAGE_EXTENSIONS', 'jpg,jpeg,png,webp,gif'))
        ))),
        'document_extensions' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('IDEA_MEDIA_DOCUMENT_EXTENSIONS', 'pdf,doc,docx,ppt,pptx,xls,xlsx,txt,md,csv,srt'))
        ))),
        'image_mime_types' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('MEDIA_IMAGE_MIME_TYPES', 'image/jpeg,image/png,image/webp,image/gif'))
        ))),
        'attachment_mime_types' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('MEDIA_ATTACHMENT_MIME_TYPES', 'image/jpeg,image/png,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'))
        ))),
    ],
    'moderation' => [
        'submission_policy' => env('COMMUNITY_SUBMISSION_POLICY', 'all_require_approval'),
        'sensitive_words' => [
            'enabled' => (bool) env('COMMUNITY_SENSITIVE_WORDS_ENABLED', false),
            'terms' => array_values(array_filter(array_map(
                static fn (string $value): string => trim($value),
                explode(',', (string) env('COMMUNITY_SENSITIVE_WORDS', ''))
            ))),
        ],
    ],
    'b2b_leads' => [
        'notify_admins' => (bool) env('B2B_LEADS_NOTIFY_ADMINS', false),
        'notification_recipients' => array_values(array_filter(array_map(
            static fn (string $value): string => trim($value),
            explode(',', (string) env('B2B_LEAD_NOTIFICATION_RECIPIENTS', ''))
        ))),
    ],
    'funding' => [
        'default_support_button_text' => env('FUNDING_DEFAULT_SUPPORT_BUTTON_TEXT', 'Support this concept'),
    ],
];
