<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Signed Storage
    |--------------------------------------------------------------------------
    | When using local storage (FILESYSTEM_DRIVER=local), disable Vapor's
    | signed storage URL to avoid AWS credential errors. The app uses
    | /api/local-storage-upload for local file uploads instead.
    |
    */

    'signed_storage' => [
        'enabled' => config('filesystems.default') !== 'local',
        'url' => '/vapor/signed-storage-url',
    ],

];
