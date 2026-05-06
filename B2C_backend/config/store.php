<?php

return [
    'currency' => env('STORE_CURRENCY', 'NZD'),

    'tax' => [
        'gst_rate' => (float) env('STORE_GST_RATE', 0.15),
        'prices_include_gst' => (bool) env('STORE_PRICES_INCLUDE_GST', true),
        'label' => env('STORE_TAX_LABEL', 'GST included'),
    ],

    'shipping' => [
        'free_shipping_threshold' => (float) env('STORE_FREE_SHIPPING_THRESHOLD', 200),
        'standard_rate' => (float) env('STORE_STANDARD_SHIPPING_RATE', 8),
        'express_rate' => (float) env('STORE_EXPRESS_SHIPPING_RATE', 14),
        'rural_surcharge' => (float) env('STORE_RURAL_SHIPPING_SURCHARGE', 5),
        'origin' => [
            'postcode' => env('STORE_ORIGIN_POSTCODE'),
            'city' => env('STORE_ORIGIN_CITY'),
            'country' => env('STORE_ORIGIN_COUNTRY', 'NZ'),
        ],
    ],

    'nzpost' => [
        'enabled' => (bool) env('NZPOST_ENABLED', false),
        'base_url' => env('NZPOST_API_BASE_URL', 'https://api.nzpost.co.nz'),
        'client_id' => env('NZPOST_CLIENT_ID'),
        'client_secret' => env('NZPOST_CLIENT_SECRET'),
        'api_key' => env('NZPOST_API_KEY'),
    ],
];
