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
     * @var string
     */
    public string $currency;

    /**
     * Status of the invoice.
     * Accepted values: 'PENDING', 'PARTIAL_FULFILLED', 'FULFILLED', 'SUCCESSED', 'EXPIRED'.
     *
     * @var string
     */
    public string $status;

    /**
     * Network used for the cryptocurrency payment.
     *
     * @var string
     */
    public string $cryptoNetwork;

    /**
     * Cryptocurrency address for the payment.
     *
     * @var string
     */
    public string $cryptoAddress;
    
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


    public function __construct(
        public string $id,
        public string $amount,
        public string $currency,
        public string $status,
        public string $cryptoNetwork,
        public string $cryptoAddress,
        public ?string $description = null,
        public ?string $callbackUrl = null,
        public ?string $cancelUrl = null,
        public ?string $successUrl = null
    ) { 
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->cryptoNetwork = $cryptoNetwork;
        $this->cryptoAddress = $cryptoAddress;
        $this->description = $description ?? '';
        $this->callbackUrl = $callbackUrl ?? '';
        $this->cancelUrl = $cancelUrl ?? '';
        $this->successUrl = $successUrl ?? '';
    }
    
}
