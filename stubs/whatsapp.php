<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Whatsapp Token
    |--------------------------------------------------------------------------
    |
    | Your Whatsapp application you received after creating
    | the messenger page / application on Whatsapp.
    |
    */
    'token' => env('WHATSAPP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Whatsapp App Secret
    |--------------------------------------------------------------------------
    |
    | Your Whatsapp application secret, which is used to verify
    | incoming requests from Whatsapp.
    |
    */
    'app_secret' => env('WHATSAPP_APP_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Whatsapp Verification
    |--------------------------------------------------------------------------
    |
    | Your Whatsapp verification token, used to validate the webhooks.
    |
    */
    'verification' => env('WHATSAPP_VERIFICATION'),

    /*
    |--------------------------------------------------------------------------
    | Whatsapp Start Button Payload
    |--------------------------------------------------------------------------
    |
    | The payload which is sent when the Get Started Button is clicked.
    |
    */
    'start_button_payload' => 'GET_STARTED',

    /*
    |--------------------------------------------------------------------------
    | Whatsapp Greeting Text
    |--------------------------------------------------------------------------
    |
    | Your Whatsapp Greeting Text which will be shown on your message start screen.
    |
    */
    'greeting_text' => [
        'greeting' => [
            [
                'locale' => 'default',
                'text' => 'Hello!',
            ],
            [
                'locale' => 'en_US',
                'text' => 'Timeless apparel for the masses.',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Whatsapp Persistent Menu
    |--------------------------------------------------------------------------
    |
    | Example items for your persistent Whatsapp menu.
    | See https://developers.whatsapp.com/docs/messenger-platform/reference/messenger-profile-api/persistent-menu/#example
    |
    */
    'persistent_menu' => [
        [
            'locale' => 'default',
            'composer_input_disabled' => 'true',
            'call_to_actions' => [
                [
                    'title' => 'My Account',
                    'type' => 'nested',
                    'call_to_actions' => [
                        [
                            'title' => 'Pay Bill',
                            'type' => 'postback',
                            'payload' => 'PAYBILL_PAYLOAD',
                        ],
                    ],
                ],
                [
                    'type' => 'web_url',
                    'title' => 'Latest News',
                    'url' => 'http://botman.io',
                    'webview_height_ratio' => 'full',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Whatsapp Domain Whitelist
    |--------------------------------------------------------------------------
    |
    | In order to use domains you need to whitelist them
    |
    */
    'whitelisted_domains' => [
        'https://petersfancyapparel.com',
    ],
];
