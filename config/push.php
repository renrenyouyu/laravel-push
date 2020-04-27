<?php

return [
    'redis' => [

        'client' => 'predis',

        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DATABASE', 0),
        ]
    ],
    "pkgName" => env("APP_PKG_NAME", ""),
    "platform" => [
        "mi" => [
            "appSecret" => env("XIAOMI_APP_SECRET", null),
            "intentUri" => env("XIAOMI_APP_INTENT_URI", null),
            "httpSendType" => env("XIAOMI_APP_SEND_TYPE", "alias")
        ],

        "huawei" => [
            "appId" => env("HUAWEI_CLIENT_ID", null),
            "appSecret" => env("HUAWEI_CLIENT_SECRET", null),
            'intentUri' => env('HUAWEI_APP_INTENT', null)
        ],

        "apple" => [
            "appId" => env("APNS_CERTIFICATE_PATH", null),
            "appSecret" => env("APNS_CERTIFICATE_PASSPHRASE", null),
            "appEnvironment" => env("APNS_ENVIRONMENT", "sandbox") // production
        ],

        "vivo" => [
            "appId" => env("VIVO_APP_ID", null),
            "appKey" => env("VIVO_APP_KEY", null),
            "appSecret" => env("VIVO_APP_SECRET", null),
            "httpSendType" => env("VIVO_APP_SEND_TYPE", "alias"),
            "intentUri" => env("VIVO_APP_INTENT_URI", null),
        ],

        "oppo" => [
            "appId" => env("OPPO_APP_ID", null),
            "appKey" => env("OPPO_APP_KEY", null),
            "appSecret" => env("OPPO_APP_SECRET", null),
            "masterSecret" => env("OPPO_MASTER_SECRET", null),
            "channelId" => env("OPPO_APP_CHANNEL_ID", "subscribe"),
            "intentUri" => env("OPPO_APP_INTENT_URI", null),
            "httpSendType" => env("OPPO_APP_SEND_TYPE", null)
        ]
    ]
];
