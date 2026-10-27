<?php

return [
    // Fail closed until a durable bucket or explicitly mounted volume is connected.
    'uploads_enabled' => (bool) env('PROFILE_AVATAR_UPLOADS_ENABLED', env('APP_ENV') === 'local'),
    'avatar_disk' => env('PROFILE_AVATAR_DISK', env('APP_ENV') === 'local' ? 'profile_volume' : ''),
    'volume_root' => env('PROFILE_AVATAR_VOLUME_ROOT', env('APP_ENV') === 'local' ? storage_path('app/profile-avatars') : ''),
    'public_base_url' => env('PROFILE_PICTURE_BASE_URL'),
];
