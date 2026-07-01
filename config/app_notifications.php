<?php

declare(strict_types=1);

return [
    'queue' => env('NOTIFICATIONS_QUEUE', 'default'),

    'fcm_chunk_size' => 400,

    'firebase_projects' => [
        'mobile' => 'mobile',
        'web' => 'web',
    ],
];
