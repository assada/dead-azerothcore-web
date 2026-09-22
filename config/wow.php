<?php

return [

    'default_realm_id' => env('REALM_DEFAULT_ID', 1),

    'world_database' => env('DB_WORLD_DATABASE', 'acore_world'),
    'tooltip_url' => env('WOW_TOOLTIP_URL', 'https://wowgaming.altervista.org/aowow'),
    'modelviewer_path' => 'modelviewer/9.2.0/',

    'auction' => [
        'shared' => env('WOW_AUCTION_SHARED', true),
    ],

    'realms' => [
        (int) env('REALM_DEFAULT_ID', 1) => [
            'name' => env('REALM_NAME', env('APP_NAME', 'AzerothCore')),
            'soap' => [
                'url' => env('REALM_SOAP_URL'),
                'username' => env('REALM_SOAP_USERNAME'),
                'password' => env('REALM_SOAP_PASSWORD'),
            ],
        ],
    ],

    'expansion' => 'Wrath of the Lich King (3.3.5a)',
    'rates' => [
        'xp' => env('WOW_RATE_XP', 'x1'),
        'drop' => env('WOW_RATE_DROP', 'x1'),
        'gold' => env('WOW_RATE_GOLD', 'x1'),
        'reputation' => env('WOW_RATE_REP', 'x1'),
    ],
    'realmlist' => env('WOW_REALMLIST', 'set realmlist logon.example.com'),
    'client_download_url' => env('WOW_CLIENT_URL'),

    'registration_enabled' => env('WOW_REGISTRATION_ENABLED', true),
    'community_public' => env('WOW_COMMUNITY_PUBLIC', false),
    'actions' => [
        'unstuck' => [
            'enabled' => env('WOW_UNSTUCK_ENABLED', true),
            'cooldown' => env('WOW_UNSTUCK_COOLDOWN', 'PT3H'),
        ],
        'rename' => [
            'enabled' => env('WOW_RENAME_ENABLED', true),
            'cooldown' => env('WOW_RENAME_COOLDOWN', 'P1Y'),
        ],
        'customize' => [
            'enabled' => env('WOW_CUSTOMIZE_ENABLED', true),
            'cooldown' => env('WOW_CUSTOMIZE_COOLDOWN', 'calendar_quarter'),
        ],
    ],

];
