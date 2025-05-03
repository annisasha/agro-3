<?php

return [
    'default' => env('OPENAI_CONNECTION', 'production'),
    'connections' => [
        'production' => [
            'api_key'     => env('OPENAI_API_KEY'),
            'guzzle'      => [
                'verify'  => false,  // <— non-aktifkan SSL verify
                'timeout' => 30,
            ],
        ],
    ],
];
