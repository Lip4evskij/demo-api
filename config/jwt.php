<?php

return [
    'secret' => env('JWT_SECRET'),
    'algo'   => 'HS256',
    'ttl'    => (int) env('JWT_TTL', 3600),
    'issuer' => env('APP_URL'),
];
