<?php

return [
    // secret para geração de tokens
    'secret' => env('JWT_SECRET'),

    // TTL do token
    'ttl' => (int) env('JWT_TTL', 3600),

    // cripto utilizada
    'alg' => 'HS256',

    //salt hash

    'hash_salt' => env('HASH_SALT'),
];
