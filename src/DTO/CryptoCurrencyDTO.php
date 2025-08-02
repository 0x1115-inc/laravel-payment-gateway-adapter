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

namespace MCXV\PaymentAdapter\DTO;

class CryptoCurrencyDTO
{
  
    /**
     * Unique identifier for the cryptocurrency.
     *
     * @var string
     */
    public string $id;

    /**
     * Name of the cryptocurrency.
     *
     * @var string
     */
    public string $name;

    /**
     * Symbol of the cryptocurrency.
     *
     * @var string
     */
    public string $symbol;

    /**
     * Network used for the cryptocurrency.
     *
     * @var string
     */
    public string $network;

    /**
     * Constructor to initialize the cryptocurrency properties.
     *
     * @param string $id
     * @param string $name
     * @param string $symbol
     * @param string $network
     */
    public function __construct(string $id)
    {        
        $this->id = $id;                
        $currency = config('payment_adapter.currencies')[$id] ?? null;
        if ($currency) {
            $this->name = $currency['name'] ?? '';
            $this->symbol = $currency['symbol'] ?? '';
            $this->network = $currency['network'] ?? '';
        } else {
            $this->name = 'Unsupported';
            $this->symbol = 'Unsupported';
            $this->network = 'Unsupported';
        }
    }

}