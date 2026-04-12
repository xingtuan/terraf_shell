<?php

return [
    'pagination' => [
        'default_per_page' => env('API_PER_PAGE', 20),
        'max_per_page' => env('API_MAX_PER_PAGE', 50),
    ],
    'uploads' => [
        'disk' => env('COMMUNITY_UPLOAD_DISK', env('FILESYSTEM_DISK', 's3')),
    ],
];
