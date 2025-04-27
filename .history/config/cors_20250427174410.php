<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie' , '/streamable_videos/*'],

    'allowed_methods' => ['*'], // جميع الميثودز مثل GET, POST, PUT

    'allowed_origins' => ['http://localhost:3000'], // <-- عدل هنا، لا تستخدم *

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true, // مهم اذا كنت ترسل كوكيز مع الطلبات

];
