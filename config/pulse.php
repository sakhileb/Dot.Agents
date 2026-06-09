<?php

use Laravel\Pulse\Http\Middleware\Authorize;
use Laravel\Pulse\Recorders\CacheInteractions;
use Laravel\Pulse\Recorders\Exceptions;
use Laravel\Pulse\Recorders\Queues;
use Laravel\Pulse\Recorders\Servers;
use Laravel\Pulse\Recorders\SlowJobs;
use Laravel\Pulse\Recorders\SlowOutgoingRequests;
use Laravel\Pulse\Recorders\SlowQueries;
use Laravel\Pulse\Recorders\SlowRequests;
use Laravel\Pulse\Recorders\UserJobs;
use Laravel\Pulse\Recorders\UserRequests;

return [
    /*
    |--------------------------------------------------------------------------
    | Dot.Agents — Laravel Pulse Configuration
    | ERP-90 Phase 3: Platform observability dashboard
    |--------------------------------------------------------------------------
    */

    'enabled' => env('PULSE_ENABLED', true),

    'domain' => env('PULSE_DOMAIN'),

    'path' => env('PULSE_PATH', 'pulse'),

    'middleware' => [
        'web',
        Authorize::class,
    ],

    'ingest' => [
        'driver' => env('PULSE_INGEST_DRIVER', 'storage'),
        'redis' => [
            'connection' => env('PULSE_REDIS_CONNECTION', 'default'),
            'chunk' => 1000,
        ],
        'trim' => [
            'lottery' => [1, 1000],
            'keep' => '7 days',
        ],
        'queue' => [
            'connection' => env('PULSE_QUEUE_CONNECTION'),
            'queue' => env('PULSE_QUEUE', 'default'),
        ],
    ],

    'cache' => env('PULSE_CACHE_DRIVER'),

    'db' => [
        'connection' => env('PULSE_DB_CONNECTION', 'mysql'),
        'chunk' => 500,
    ],

    'recorders' => [
        // Server metrics
        Servers::class => [
            'server_name' => env('PULSE_SERVER_NAME', gethostname()),
            'directories' => explode(':', env('PULSE_SERVER_DIRECTORIES', '/')),
        ],

        // Slow requests (>200ms)
        SlowRequests::class => [
            'enabled' => env('PULSE_SLOW_REQUESTS_ENABLED', true),
            'threshold' => env('PULSE_SLOW_REQUESTS_THRESHOLD', 200),
            'sample_rate' => env('PULSE_SLOW_REQUESTS_SAMPLE_RATE', 1),
            'ignore' => [
                '#^/up$#',
                '#^/pulse#',
            ],
        ],

        // Slow jobs (>2s)
        SlowJobs::class => [
            'enabled' => env('PULSE_SLOW_JOBS_ENABLED', true),
            'threshold' => env('PULSE_SLOW_JOBS_THRESHOLD', 2000),
            'sample_rate' => env('PULSE_SLOW_JOBS_SAMPLE_RATE', 1),
        ],

        // Slow queries (>100ms)
        SlowQueries::class => [
            'enabled' => env('PULSE_SLOW_QUERIES_ENABLED', true),
            'threshold' => env('PULSE_SLOW_QUERIES_THRESHOLD', 100),
            'sample_rate' => env('PULSE_SLOW_QUERIES_SAMPLE_RATE', 1),
            'location' => true,
        ],

        // Exceptions
        Exceptions::class => [
            'enabled' => env('PULSE_EXCEPTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_EXCEPTIONS_SAMPLE_RATE', 1),
        ],

        // Cache interactions
        CacheInteractions::class => [
            'enabled' => env('PULSE_CACHE_INTERACTIONS_ENABLED', true),
            'sample_rate' => env('PULSE_CACHE_INTERACTIONS_SAMPLE_RATE', 0.1),
        ],

        // Outgoing HTTP requests (AI API calls)
        SlowOutgoingRequests::class => [
            'enabled' => env('PULSE_SLOW_OUTGOING_REQUESTS_ENABLED', true),
            'threshold' => env('PULSE_SLOW_OUTGOING_REQUESTS_THRESHOLD', 1000),
            'sample_rate' => env('PULSE_SLOW_OUTGOING_REQUESTS_SAMPLE_RATE', 1),
        ],

        // Queue monitoring
        Queues::class => [
            'enabled' => env('PULSE_QUEUES_ENABLED', true),
            'sample_rate' => env('PULSE_QUEUES_SAMPLE_RATE', 1),
        ],

        // User requests
        UserRequests::class => [
            'enabled' => env('PULSE_USER_REQUESTS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_REQUESTS_SAMPLE_RATE', 0.1),
        ],

        // User jobs
        UserJobs::class => [
            'enabled' => env('PULSE_USER_JOBS_ENABLED', true),
            'sample_rate' => env('PULSE_USER_JOBS_SAMPLE_RATE', 1),
        ],
    ],
];
