<?php

return [
    'log_channel' => env('BIS_LOG_CHANNEL', 'stack'),

    'mail_from' => [
        'address' => env('BIS_MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS')),
        'name'    => env('BIS_MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Scarcity Bar')),
    ],

    'attribution_window_hours' => (int) env('BIS_ATTRIBUTION_WINDOW_HOURS', 96),

    /*
    | Theme app extension identifiers used to build the Theme Editor deep
    | link that lands the merchant directly on the embed-toggle panel.
    |
    |  - extension_handle:   the extension's `handle` from shopify.extension.toml
    |  - app_embed_handle:   the BLOCK file name in extensions/<ext>/blocks/<file>.liquid
    |                        (i.e. `app-embed` for blocks/app-embed.liquid)
    |  - app_extension_uuid: the `uid` from shopify.extension.toml — Shopify
    |                        uses {uuid}/{block-handle} as the activateAppId.
    */
    'extension_handle'   => env('BIS_EXTENSION_HANDLE', 'backinstock-widget'),
    'app_embed_handle'   => env('BIS_APP_EMBED_HANDLE', 'app-embed'),
    'app_extension_uuid' => env('BIS_APP_EXTENSION_UUID', 'a4625ef2-dbcd-5ba2-f695-e6d427fa40cf8d0e9c11'),

    'sms_provider' => env('BIS_SMS_PROVIDER', 'log'),

    'queue' => [
        'evaluate' => env('BIS_QUEUE_EVALUATE', 'default'),
        'send'     => env('BIS_QUEUE_SEND', 'default'),
    ],
];
