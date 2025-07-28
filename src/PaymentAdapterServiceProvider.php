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

namespace MCXV\PaymentAdapter;

use Illuminate\Support\ServiceProvider;

use MCXV\PaymentAdapter\PaymentAdapterManager;

class PaymentAdapterServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/payment_adapter.php' => config_path('payment_adapter.php')
        ]);
    }

    public function register()
    {        
        $this->mergeConfigFrom(
            __DIR__ . '/../config/payment_adapter.php',
            'payment_adapter'
        );
        
        $this->app->singleton('payment.adapter', function ($app) {            
            return new PaymentAdapterManager($app);
        });
    }
}