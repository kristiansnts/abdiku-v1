<?php

return [

    /*
    |--------------------------------------------------------------------------
    | FCM Driver
    |--------------------------------------------------------------------------
    |
    | This option defines the default FCM driver that will be used to send
    | push notifications. The default driver is 'fcm' which uses the
    | Firebase Cloud Messaging HTTP v1 API.
    |
    */

    'driver' => env('FCM_DRIVER', 'fcm'),

    /*
    |--------------------------------------------------------------------------
    | FCM Drivers Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the FCM drivers for your application. The
    | 'credentials' option should point to your Firebase service account
    | JSON file path.
    |
    */

    'drivers' => [
        'fcm' => [
            'credentials' => storage_path('app/json/firebase-service-account.json'),
        ],
    ],

];
