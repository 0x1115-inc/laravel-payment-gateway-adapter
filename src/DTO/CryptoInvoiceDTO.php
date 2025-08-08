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
use MCXV\PaymentAdapter\DTO\CryptoCurrencyDTO;

class CryptoInvoiceDTO
{

    /**
     * Unique identifier for the invoice.
     *
     * @var string
     */
    public string $id;
    
    /**
     * Amount of the invoice in crypto currency.
     *
     * @var string
     */
    public string $amount;

    /**
     * Currency of the invoice.
     *
     * @var CryptoCurrencyDTO
     */
    public CryptoCurrencyDTO $currency;

    /**
     * Status of the invoice.
     * Accepted values: 'PENDING', 'PARTIAL_FULFILLED', 'FULFILLED', 'SUCCESSED', 'EXPIRED'.
     *
     * @var string
     */
    public string $status;

    /**
     * Cryptocurrency address for the payment.
     *
     * @var string
     */
    public string $cryptoAddress;

    /**
     * Expiration time of the invoice in UNIX format.
     *
     * @var string
     */
    public int $expirationTime;
    
    /**
     * Description of the invoice.
     *
     * @var string|null
     */
    public ?string $description;

    /**
     * Callback URL for the invoice.
     *
     * @var string|null
     */
    public ?string $callbackUrl;

    /**
     * Cancel URL for the invoice.
     *
     * @var string|null
     */
    public ?string $cancelUrl;

    /**
     * Success URL for the invoice.
     *
     * @var string|null
     */
    public ?string $successUrl;

    // Define status constants
    public const STATUS_PENDING = 'PENDING';
    public const STATUS_PARTIAL_FULFILLED = 'PARTIAL_FULFILLED';
    public const STATUS_FULFILLED = 'FULFILLED';
    public const STATUS_SUCCESSED = 'SUCCESSED';
    public const STATUS_EXPIRED = 'EXPIRED';   

    public function __construct(
        string $id,
        string $amount,
        CryptoCurrencyDTO $currency,
        string $status,        
        string $cryptoAddress,
        int $expirationTime,
        string $description = null,
        string $callbackUrl = null,
        string $cancelUrl = null,
        string $successUrl = null
    ) { 
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;        
        $this->cryptoAddress = $cryptoAddress;
        $this->expirationTime = $expirationTime;
        $this->description = $description ?? '';
        $this->callbackUrl = $callbackUrl ?? '';
        $this->cancelUrl = $cancelUrl ?? '';
        $this->successUrl = $successUrl ?? '';
    }
    
    public function getId() : string
    {
        return $this->id;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCryptoAddress() : string
    {
        return $this->cryptoAddress;
    }

    public function getCurrency() : CryptoCurrencyDTO
    {
        return $this->currency;
    }

}
