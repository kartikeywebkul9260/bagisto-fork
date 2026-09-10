<?php

return [
    'oauth' => [
        'authorization-failed' => 'Razorpay authorization failed. Please try connecting again.',
        'connect-info' => 'Connect your Razorpay account to authorize payments through OAuth. You will be redirected to Razorpay to grant access.',
        'connect' => 'Connect with Razorpay',
        'connected-account' => 'Connected Razorpay account: :account',
        'connected' => 'Your Razorpay account has been connected successfully.',
        'credentials-missing' => 'Please enter and save your Razorpay Client ID and Client Secret before connecting.',
        'disconnect' => 'Disconnect',
        'disconnected' => 'Your Razorpay account has been disconnected.',
        'invalid-state' => 'The authorization request could not be verified. Please try again.',
        'not-connected' => 'No Razorpay account is connected. Please connect your account to continue.',
        'title' => 'OAuth Connection',
        'token-error' => 'Unable to retrieve the Razorpay access token. Please try again.',
    ],
    
    'drop-in-ui' => [
        'title' => 'Razorpay',
    ],

    'response' => [
        'payment' => [
            'cancelled' => 'Razorpay 付款已被取消。',
        ],

        'something-went-wrong' => '出现错误。',
        'supported-currency-error' => '货币 :currency 不受支持。支持的货币有: :supportedCurrencies.',
    ],
];
