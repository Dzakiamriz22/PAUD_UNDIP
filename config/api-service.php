<?php

return [
    'navigation' => [
        'token' => [
            'cluster' => null,
            'group' => 'Access',
            'sort' => 99,
            'icon' => 'heroicon-o-key',
            'should_register_navigation' => true,
        ],
    ],
    'models' => [
        'token' => [
            'enable_policy' => true,
        ],
    ],
    'route' => [
        'panel_prefix' => true,
        'use_resource_middlewares' => false,
    ],
    'tenancy' => [
        'enabled' => false,
        'awareness' => false,
    ],
    'login-rules' => [
        // 'email' => 'required|email',
        'username' => 'required',
        'password' => 'required',
    ],
    'use-spatie-permission-middleware' => true,
];
