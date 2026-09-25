<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vehicle manager notification email (fallback)
    |--------------------------------------------------------------------------
    |
    | Help/support submissions and maintenance alert emails go to the users
    | marked "Recibir correos de Fleet Desk" in User Management. This address
    | is only used when no user is selected (see
    | App\Support\NotificationRecipients), so existing deployments keep
    | receiving emails until an admin picks recipients. Optional.
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

    /*
    |--------------------------------------------------------------------------
    | Login code cooldown
    |--------------------------------------------------------------------------
    |
    | Minimum seconds between login (OTP) code emails for the same address,
    | regardless of IP or browser session. Set to 0 to disable. This sits on
    | top of the existing limit in LoginController (3 requests / 10 min per
    | email + IP).
    |
    */

    'login_code_cooldown_seconds' => (int) env('LOGIN_CODE_COOLDOWN_SECONDS', 60),

];
