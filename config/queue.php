<?php

return [
    'default' => env('QUEUE_CONNECTION', 'database'),

    'connections' => [
        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION'),
            'table' => env('QUEUE_TABLE', 'jobs'),
            'queue' => env('QUEUE_QUEUE', 'default'),
            'retry_after' => env('QUEUE_RETRY_AFTER', 86400),
            'after_commit' => false,
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('QUEUE_REDIS_QUEUE', 'default'),
            'retry_after' => env('QUEUE_RETRY_AFTER', 86400),
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('DB_CONNECTION'),
        'table' => env('QUEUE_FAILED_TABLE', 'failed_jobs'),
    ],
];