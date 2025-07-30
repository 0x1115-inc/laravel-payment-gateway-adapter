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

namespace MCXV\PaymentAdapter\Drivers;
use MCXV\PaymentAdapter\Contracts\PaymentGatewayInterface;
use MCXV\PaymentAdapter\DTO\CryptoInvoiceDTO;

use Illuminate\Support\Facades\Http;

class CoinPaymentDriver implements PaymentGatewayInterface
{
    protected $config;


    public function __construct(array $config)
    {
        $this->config = $config;
    }

    private function __header(array $data) : array
    {
        // Reference: https://a-docs.coinpayments.net/api/auth/generate-api-signature

        // The current timestamp in UTC ISO-8601 format 
        // without milliseconds and timezone (YYYY-MM-DDTHH:mm:ss)
        $currentTimestamp = now()->toIso8601String();
        $currentTimestamp = substr($currentTimestamp, 0, 19); // Remove milliseconds and timezone

        // Construct the signature message parts
        // The message will be included 
        // - \ufeff (Byte Order Mark)
        // - HTTP method
        // - URL
        // - Integration Client ID
        // - Timestamp (UTC ISO-8601 YYYY-MM-DDTHH:mm:ss)
        // - Request payload (JSON)
        $messageParts = [
            "\xEF\xBB\xBF",
            $data['method'],
            $data['url'],
            $this->config['client_id'],
            $currentTimestamp,
            json_encode($data['payload']),
        ]

        // Base64 encode the resulting SHA-256 hash.        
        $signature = hash_hmac(
            'sha256', 
            implode('', $messageParts), 
            $this->config['client_secret'], 
            true // return raw binary output
        );


        $return [
            // The integration client id
            'X-CoinPayments-Client': $this->config['client_id'],
            
            // The current timestamp in UTC ISO-8601 format (YYYY-MM-DDTHH:mm:ss)
            'X-CoinPayments-Timestamp': $currentTimestamp

            // The signature of the request, generated using the secret key
            'X-CoinPayments-Signature': base64_encode($signature),
        ]
        
    }

    /**
     * Get list of all invoices with optional filter.
     *
     * @param array $filters
     * @return CryptoInvoiceDTO[]
     */
     
    public function getInvoices(array $filters): array
    {        
        $url = 'https://a-api.coinpayments.net/api/v2/merchant/invoices'

        $response = Http::withHeaders($this->__header([
            'method' => 'GET',
            'url' => $url,
            'payload' => $filters,
        ]))->get($url, $filters);

        // Handle request failure
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve invoices: ' . $response->body());
        }

        // Parse the response data
        $data = $response->json();
        if (!isset($data['data']) || !is_array($data['data'])) {
            throw new \Exception('Invalid response format: ' . $response->body());
        }
        $invoices = [];

        // TODO: Implement logic to convert response data to CryptoInvoiceDTO objects

        return $invoices;
    }

    /**
     * Get an invoice by its unique identifier.
     *
     * @param string $id
     * @return CryptoInvoiceDTO
     */
    public function getInvoiceById(string $id): CryptoInvoiceDTO
    {
        $url = 'https://a-api.coinpayments.net/api/v2/merchant/invoices/' . $id;
        
        $response = Http::withHeaders($this->__header([
            'method' => 'GET',
            'url' => $url,
            'payload' => [],
        ]))->get($url);

        // Handle request failure
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve invoice: ' . $response->body());
        }

        // Parse the response data
        $data = $response->json();
        if (!isset($data['data'])) {
            throw new \Exception('Invoice not found: ' . $id);
        }

        
        // TODO: Handle the response data and convert it to CryptoInvoiceDTO
        $invoice = new CryptoInvoiceDTO();
        
        return $invoice;
    }

    /**
     * Create a new invoice with the provided data.
     *
     * @param CryptoInvoiceDTO $invoice
     * @return CryptoInvoiceDTO
     */
    public function createInvoice(CryptoInvoiceDTO $invoice): CryptoInvoiceDTO
    {
        $url = 'https://a-api.coinpayments.net/api/v2/merchant/invoices';
        
        $payload = [
            // TODO: Prepare the payload for the request
        ];

        $response = Http::withHeaders($this->__header([
            'method' => 'POST',
            'url' => $url,
            'payload' => $payload,
        ]))->post($url, $payload);

        // Handle request failure
        if ($response->failed()) {
            throw new \Exception('Failed to create invoice: ' . $response->body());
        }

        // Parse the response data
        $data = $response->json();
        if (!isset($data['data'])) {
            throw new \Exception('Failed to create invoice: ' . $response->body());
        }

        // TODO: Handle the response data and convert it to CryptoInvoiceDTO
        $invoice = new CryptoInvoiceDTO();        

        // TODO: Broadcast the invoice created event 

        return $invoice;
    }



    
}