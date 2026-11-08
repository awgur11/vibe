<?php

return [
    'api_key' => env('ONEC_API_KEY', ''),
    'orders_disk' => env('ONEC_ORDERS_DISK', 'local'),
    'orders_directory' => env('ONEC_ORDERS_DIRECTORY', 'onec/orders'),
];
