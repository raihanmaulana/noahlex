<?php

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'prices' => [
        'basic' => env('STRIPE_PRICE_CORE', 'price_1Rt9BOGYQT0D85sePgebU2uJ'),
        'pro' => env('STRIPE_PRICE_PROFESSIONAL', 'price_1RuXZ5GYQT0D85seI2gaakWY'),
        'enterprise' => env('STRIPE_PRICE_ENTERPRISE', 'price_1RuXb7GYQT0D85sedZ680MUr'),
    ],
];