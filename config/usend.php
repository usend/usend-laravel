<?php

return [
    // An API key (starts with us_) from the usend dashboard.
    'key' => env('USEND_API_KEY'),

    // Only change this for a self-hosted usend.
    'base_url' => env('USEND_BASE_URL', 'https://api.usend.et'),
];
