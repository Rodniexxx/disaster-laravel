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

];
