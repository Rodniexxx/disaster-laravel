<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Brand identity
    |--------------------------------------------------------------------------
    |
    | Central place for the MANGNO brand strings used across the web portal and
    | in transactional mail. Keep the tagline here so it can be changed once.
    |
    */

    'name' => 'MANGNO',
    'tagline' => env('MANGNO_TAGLINE', 'Ready Before It Happens.'),
    'full_title' => 'MANGNO: Educational Game for Disaster Preparedness',

    /*
    |--------------------------------------------------------------------------
    | Unity game integration
    |--------------------------------------------------------------------------
    |
    | Settings that control how the versioned REST API issues gameplay sessions
    | and authentication tokens to the Unity 3D client.
    |
    */

    'api' => [
        'token_ttl_minutes' => (int) env('MANGNO_API_TOKEN_TTL', 60 * 24 * 7),
        'token_name' => 'unity-client',
        'session_question_count' => (int) env('MANGNO_SESSION_QUESTION_COUNT', 10),
        'session_lifetime_minutes' => 180,
        'score_per_question' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Leaderboard scoring
    |--------------------------------------------------------------------------
    |
    | Overall leaderboard uses the average of each player's best percentage per
    | disaster category (only categories with at least one completed attempt).
    | This keeps scores comparable across categories with different maximums.
    |
    */

    'leaderboard' => [
        'min_sessions_for_rank' => 1,
        'per_page' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Game downloads
    |--------------------------------------------------------------------------
    |
    | The admin "Game Versions" page is the source of truth for the public
    | /download page. Uploaded builds are stored on the private disk below
    | (outside the web root) and streamed through a controller action.
    |
    | "extensions" lists the build file types accepted for each platform.
    | "max_upload_kb" is the server-side upload ceiling in kilobytes; keep the
    | PHP upload_max_filesize and post_max_size values at least as large.
    |
    */

    'downloads' => [
        'disk' => env('MANGNO_DOWNLOADS_DISK', 'local'),
        'path' => env('MANGNO_DOWNLOADS_PATH', 'downloads'),
        'max_upload_kb' => (int) env('MANGNO_MAX_UPLOAD_KB', 524288),

        'platforms' => [
            'windows' => [
                'name' => 'Windows',
                'title' => 'Windows Version',
                'platform' => 'Windows PC',
                'file_type' => '.exe',
                'button' => 'Download for Windows',
                'description' => 'Experience MANGNO on your Windows computer and explore interactive disaster preparedness scenarios.',
                'extensions' => ['exe', 'msi', 'zip'],
            ],

            'android' => [
                'name' => 'Android',
                'title' => 'Android Version',
                'platform' => 'Android',
                'file_type' => '.apk',
                'button' => 'Download for Android',
                'description' => 'Take MANGNO with you and learn disaster preparedness through interactive gameplay on your Android device.',
                'extensions' => ['apk'],
            ],
        ],
    ],

];
