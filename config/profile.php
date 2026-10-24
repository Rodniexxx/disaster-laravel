<?php

return [
    // Fail closed until a durable bucket or explicitly mounted volume is connected.
    'uploads_enabled' => (bool) env('PROFILE_AVATAR_UPLOADS_ENABLED', false),
    'avatar_disk' => env('PROFILE_AVATAR_DISK', ''), // s3 or profile_volume
    'volume_root' => env('PROFILE_AVATAR_VOLUME_ROOT', ''),
];
