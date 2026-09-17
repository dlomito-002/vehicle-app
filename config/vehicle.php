<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vehicle manager notification email
    |--------------------------------------------------------------------------
    |
    | Recipient for Help/support submissions and maintenance alert emails.
    | Must be set in .env — intentionally has no default so a missing
    | configuration fails loudly instead of silently emailing no one.
    |
    */

    'manager_email' => env('VEHICLE_MANAGER_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Photos disk
    |--------------------------------------------------------------------------
    |
    | The Storage disk used for vehicle photos, checklist photos, and
    | signatures (reception/delivery). Defaults to 'public' (local disk),
    | matching the app's current behavior.
    |
    | Once the "cloudinary-labs/cloudinary-laravel" package is installed
    | (composer require cloudinary-labs/cloudinary-laravel) and
    | CLOUDINARY_CLOUD_NAME / CLOUDINARY_API_KEY / CLOUDINARY_API_SECRET are
    | set in .env, set VEHICLE_PHOTOS_DISK=cloudinary to switch all new
    | uploads to Cloudinary without any code changes. Existing images
    | already stored on the 'public' disk are NOT migrated automatically.
    |
    */

    'photos_disk' => env('VEHICLE_PHOTOS_DISK', 'public'),

];
