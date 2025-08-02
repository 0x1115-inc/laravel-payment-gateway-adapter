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

use Illuminate\Support\Manager;
use MCXV\PaymentAdapter\Contracts\PaymentGatewayInterface;

class PaymentAdapterManager extends Manager
{
    /**
     * Create a new payment gateway instance.
     *
     * @param string $driver
     * @return PaymentGatewayInterface
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment_adapter.default');
    }

    protected function createCoinbaseDriver(): PaymentGatewayInterface
    {
        $config = $this->config->get('payment_adapter.drivers.coinbase');

        return new Drivers\CoinbaseDriver($config);        
    }

    protected function createCpgDriver(): PaymentGatewayInterface
    {
        $config = $this->config->get('payment_adapter.drivers.cpg');

        return new Drivers\CpgDriver($config);
    }

    protected function createCoinPaymentDriver(): PaymentGatewayInterface
    {
        $config = $this->config->get('payment_adapter.drivers.coinpayment');

        return new Drivers\CoinPaymentDriver($config);
    }

    public function extendDriver(string $name, callable $factory): void
    {
        $this->extend($name, $factory);
    }
}