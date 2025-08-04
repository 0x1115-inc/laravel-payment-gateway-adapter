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
use Illuminate\Http\Request;

use MCXV\PaymentAdapter\DTO\CryptoInvoiceDTO;


interface PaymentGatewayInterface
{
    /**
     * Get list of all invoices with optional filter.
     *
     * @return CryptoInvoiceDTO[]
     */
    public function getInvoices(array $filters): array;

    /**
     * Get an invoice by its unique identifier.
     *
     * @param string $id     
     * @return CryptoInvoiceDTO
     */
    public function getInvoiceById(string $id): CryptoInvoiceDTO;

    /**
     * Create a new invoice with the provided data.
     *
     * @param CryptoInvoiceDTO $payment
     * @return CryptoInvoiceDTO
     */
    public function createInvoice(CryptoInvoiceDTO $invoice): CryptoInvoiceDTO;

    /**
     * Handle a webhook request from the payment provider.
     *
     * @param Request $request
     * @return CryptoInvoiceDTO
     */
    public function handleWebhook(Request $request): CryptoInvoiceDTO;
}