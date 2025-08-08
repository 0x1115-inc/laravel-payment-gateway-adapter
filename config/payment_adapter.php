<?php
/**
 * Copyright 2025 0x1115 Inc <info@0x1115.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

return [
    /*
    |----------------------------------------------------------------------
    | Default Payment Adapter
    |----------------------------------------------------------------------
    |
    | This option controls the default payment adapter that will be used
    | by the application. 
    |    
    */
    
    'default' => env('PAYMENT_ADAPTER_PROVIDER', 'cpg'),

    /*
    |----------------------------------------------------------------------
    | Payment Adapter Drivers
    |----------------------------------------------------------------------
    |
    | Here you may configure the payment adapter drivers that will be used
    | by the application. Each driver should have its own configuration
    | options, such as API keys, secrets, and other necessary credentials.
    |
    | Drivers: "cpg", "coinbase"
    |
    */

    'drivers' => [
        'cpg' => [
            'api_url' => env('CPG_API_URL', 'https://api.cpg.com/api'),
            'apikey' => env('CPG_API_KEY', '')
        ],
        'coinbase' => [
            'api_key' => env('COINBASE_API_KEY', ''),
            'api_secret' => env('COINBASE_API_SECRET', ''),
            'webhook_secret' => env('COINBASE_WEBHOOK_SECRET', ''),
        ],
        'coinpayments' => [
            'environment' => env('COINPAYMENT_ENVIRONMENT', 'sandbox'), // 'sandbox' or 'production'
            'client_id' => env('COINPAYMENT_CLIENT_ID', ''),
            'client_secret' => env('COINPAYMENT_CLIENT_SECRET', ''),
            'refund_email' => env('COINPAYMENT_REFUND_EMAIL', 'john@example.com')
        ]
    ],

    /*
    |----------------------------------------------------------------------
    | Currency Acceptable
    |----------------------------------------------------------------------
    |
    | This option controls the acceptable currencies for payments.
    | You can specify multiple currencies as an array.
    |
    */
    'currencies' => [
        '1' => [
            'name' => 'Bitcoin',
            'symbol' => 'BTC',
            'network' => 'Bitcoin',
            'decimal_places' => 8,
        ],
        '2' => [
            'name' => 'Ethereum',
            'symbol' => 'ETH',
            'network' => 'Ethereum',
            'decimal_places' => 18,
        ],
        '3' => [
            'name' => 'USDT (BSC)',
            'symbol' => 'USDT',
            'network' => 'Binance Smart Chain',
            'decimal_places' => 18,
        ], 
        '4' => [
            'name' => 'USDT (TRC20)',
            'symbol' => 'USDT',
            'network' => 'Tron',
            'decimal_places' => 6,
        ],
        '5' => [
            'name' => 'USDT (ERC20)',
            'symbol' => 'USDT',
            'network' => 'Ethereum',
            'decimal_places' => 18,
        ],
        't6' => [
            'name' => 'Litecoin Testnet',
            'symbol' => 'LTC',
            'network' => 'Litecoin CryptoPayments Testnet',
            'decimal_places' => 8,
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Default Currency
    |----------------------------------------------------------------------
    |
    | This option controls the default currency that will be used for
    | transactions. It should match one of the currencies defined above.
    |
    */

    'default_currency' => env('PAYMENT_ADAPTER_DEFAULT_CURRENCY', 'USDT'),
    
    /*
    |----------------------------------------------------------------------
    | Payment Window Timeout
    |----------------------------------------------------------------------
    |
    | This option controls the timeout for payment windows. If a payment
    | is not completed within this time, it will be considered expired.
    | The value is in minutes.
    |
    */

    'window_timeout' => env('PAYMENT_ADAPTER_WINDOW_TIMEOUT', 15), // in minutes
];