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

namespace MCXV\PaymentAdapter\Contracts;
/**
 * Interface PaymentInterface
 *
 * Represents a payment entity with methods to access its properties.
 */

interface PaymentInterface
{
    /**
     * Get the unique identifier for the payment.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get the amount of the payment.
     *
     * @return float
     */
    public function getAmount(): float;

    /**
     * Get the currency of the payment.
     *
     * @return string
     */
    public function getCurrency(): string;

    /**
     * Get the status of the payment.
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Get the crypto network of the payment.
     *
     * @return string
     */
    public function getCryptoNetwork(): string;

    /**
     * Get the crypto address of the payment.
     *
     * @return string
     */
    public function getCryptoAddress(): string;
}