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

class PaymentDTO
{
    /**
     * Unique identifier for the payment.
     *
     * @var string
     */
    public string $id;

    /**
     * Amount of the payment in crypto currency.
     *
     * @var string
     */
    public string $amount;

    /**
     * Currency of the payment.     
     *
     * @var string
     */
    public string $currency;

    /**
     * Status of the payment 
     * Accepted values: 'PENDING', 'PARTIAL_FULFILLED', 'FULFILLED', 'SUCCESSED', 'EXPIRED'.
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
     * The latest time in UNIX timestamp format when the payment is valid.     
     *
     * @var int
     */
    public int $expirationTime;

    /**
     * Constructor to initialize the PaymentDTO.
     *
     * @param string $id Unique identifier for the payment.
     * @param string $amount Amount of the payment in crypto currency.
     * @param string $currency Currency of the payment.
     * @param string $status Status of the payment.
     * @param string $cryptoNetwork Network used for the cryptocurrency payment.
     * @param string $cryptoAddress Cryptocurrency address for the payment.
     * @param int $expirationTime The latest time in UNIX timestamp format when the payment is valid.
     */

    public function __construct(
        string $id,
        string $amount,
        string $currency,
        string $status,
        string $cryptoNetwork,
        string $cryptoAddress,
        int $expirationTime
    ) {
        $this->id = $id;
        $this->amount = $amount;
        $this->currency = $currency;
        $this->status = $status;
        $this->cryptoNetwork = $cryptoNetwork;
        $this->cryptoAddress = $cryptoAddress;
        $this->expirationTime = $expirationTime;
    }
}