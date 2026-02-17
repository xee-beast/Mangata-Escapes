<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Password Grant Client Credentials
    |--------------------------------------------------------------------------
    |
    | Since you cannot safely store the password grant client secret on your
    | front end, you will be able to do so here, this way you could intersept
    | the request and inject the client credentials into it.
    |
    */

    'password_client_id' => env('PASSPORT_PASSWORD_CLIENT_ID'),

    'password_client_secret' => env('PASSPORT_PASSWORD_CLIENT_SECRET'),

];
