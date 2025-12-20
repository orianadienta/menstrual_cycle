<?php

return [

    'credentials' => env('FIREBASE_PRIVATE_KEY')
        ? [
            'type' => 'service_account',
            'project_id' => env('FIREBASE_PROJECT_ID'),
            'client_email' => env('FIREBASE_CLIENT_EMAIL'),
            'private_key' => str_replace("\\n", "\n", env('FIREBASE_PRIVATE_KEY')),
        ]
        : env(
            'FIREBASE_CREDENTIALS',
            storage_path('app/firebase_credentials.json')
        ),

];
