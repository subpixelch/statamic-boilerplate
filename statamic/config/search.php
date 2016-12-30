<?php

return [

    'default' => 'zend',

    'default_index' => 'default',

    'connections' => [

        'zend' => [
            'driver' => 'zend',
            'path'   => storage_path().'/search',
        ],

        'algolia' => [
            'driver' => 'algolia',
            'config' => [
                'application_id' => '',
                'admin_api_key' => ''
            ]
        ]

    ]

];
