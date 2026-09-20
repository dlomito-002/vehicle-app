<?php

// Published/expected by the "cloudinary-labs/cloudinary-laravel" package,
// which is NOT installed in this environment (no network access here —
// see LEEME.txt for the required `composer require` command).
//
// The package's own convention is a single CLOUDINARY_URL env var in the
// form cloudinary://API_KEY:API_SECRET@CLOUD_NAME. To keep the three
// separate variables already agreed on (CLOUDINARY_CLOUD_NAME,
// CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET), this file builds that URL
// from them so nothing else has to change. If CLOUDINARY_URL is set
// directly, it takes precedence.

return [

    'cloud_url' => env('CLOUDINARY_URL', sprintf(
        'cloudinary://%s:%s@%s',
        env('CLOUDINARY_API_KEY'),
        env('CLOUDINARY_API_SECRET'),
        env('CLOUDINARY_CLOUD_NAME'),
    )),

    'notification_url' => env('CLOUDINARY_NOTIFICATION_URL'),

    'upload_preset' => env('CLOUDINARY_UPLOAD_PRESET'),

];
