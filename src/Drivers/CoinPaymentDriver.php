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
use MCXV\PaymentAdapter\DTO\CryptoCurrencyDTO;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

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
            "\u{feff}", // Byte Order Mark
            $data['method'],
            $data['url'],
            $this->config['client_id'],
            $currentTimestamp,
            // If payload is provided, include it as a JSON string            
            isset($data['payload']) ? json_encode($data['payload']) : '',            
        ];
        
        // Base64 encode the resulting SHA-256 hash.        
        $signature = hash_hmac(
            'sha256', 
            implode('', $messageParts), 
            $this->config['client_secret'], 
            true // return raw binary output
        );


        return [            
            // The integration client id
            'X-CoinPayments-Client' => $this->config['client_id'],
            
            // The current timestamp in UTC ISO-8601 format (YYYY-MM-DDTHH:mm:ss)
            'X-CoinPayments-Timestamp' => $currentTimestamp,

            // The signature of the request, generated using the secret key
            'X-CoinPayments-Signature' => base64_encode($signature),
        ];        
    }

    private function _statusMapping(string $status): string
    {
        // Coin Payment avaiable statuses
        // Enum: draft, scheduled, unpaid, pending, paid, completed, cancelled, timedOut, deleted

        switch ($status) {
            case 'draft':
                return CryptoInvoiceDTO::STATUS_PENDING;
            case 'scheduled':
                return CryptoInvoiceDTO::STATUS_PENDING;
            case 'unpaid':
                return CryptoInvoiceDTO::STATUS_PENDING;
            case 'pending':
                return CryptoInvoiceDTO::STATUS_PENDING;
            case 'paid':
                return CryptoInvoiceDTO::STATUS_FULFILLED;
            case 'completed':
                return CryptoInvoiceDTO::STATUS_SUCCESSED;
            case 'cancelled':
                return CryptoInvoiceDTO::STATUS_EXPIRED;
            case 'timedOut':
                return CryptoInvoiceDTO::STATUS_EXPIRED;
            case 'deleted':
                return CryptoInvoiceDTO::STATUS_EXPIRED;
            default:
                return CryptoInvoiceDTO::STATUS_PENDING; // Default to pending if unknown
        }
    }

    private function _currencyMapping(string $currencyId): CryptoCurrencyDTO
    {
        // Map Coin Payment currency to CryptoCurrency DTO
        switch ($currencyId) {
            case '1':
                return new CryptoCurrencyDTO('Bitcoin', 'BTC', CryptoCurrencyDTO::NETWORK_BITCOIN);
            case '4':
                return new CryptoCurrencyDTO('Ethereum', 'ETH', CryptoCurrencyDTO::NETWORK_ETHEREUM);            
            case '35:0x55d398326f99059ff775485246999027b3197955':
                return new CryptoCurrencyDTO('Tether USD', 'USDT', CryptoCurrencyDTO::NETWORK_BCS);
            case '9:TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t':
                return new CryptoCurrencyDTO('Tether USD', 'USDT', CryptoCurrencyDTO::NETWORK_TRON);
            case '4:0xdac17f958d2ee523a2206206994597c13d831ec7':
                return new CryptoCurrencyDTO('Tether USD', 'USDT', CryptoCurrencyDTO::NETWORK_ETHEREUM);
            case '1002':
                return new CryptoCurrencyDTO('Litecoin Test', 'LTCT', 'litecoin-test');
            default:
                // Handle unknown currency
                return new CryptoCurrencyDTO('Unknown', $currencyId, 'unknown');
        
        }
    }

    /**
     * Get the payment address for a specific invoice.
     *
     * @param string $invoiceId
     * @param string $currencyId
     * @return string
     */
    private function _getPaymentAddress(string $invoiceId, string $currencyId): string
    {
        if ($this->config['environment'] !== 'production') {
            // In test environment, return a dummy address
            return '0x1115DummyAddressForTesting';
        }

        $url = "https://a-api.coinpayments.net/api/v1/invoices/{$invoiceId}/payment-currencies/{$currencyId}";
     
        $response = Http::withHeaders($this->__header([
            'method' => 'GET',
            'url' => $url
        ]))->get($url);

        // Handle request failure
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve payment address: ' . $response->body());
        }
        // Parse the response data
        $data = $response->json();

        // The address is expected to be in the 'addresses.address' field
        if (!isset($data['addresses']['address'])) {
            throw new \Exception('Payment address not found for invoice: ' . $invoiceId);
        }

        // Return the payment address for the specified currency
        return $data['addresses']['address'];        
    }
    /**
     * Get list of all invoices with optional filter.
     *
     * @param array $filters
     * @return CryptoInvoiceDTO[]
     */
     
    public function getInvoices(array $filters): array
    {        
        $url = 'https://a-api.coinpayments.net/api/v2/merchant/invoices';
        
        $response = Http::withHeaders($this->__header([
            'method' => 'GET',
            'url' => $url            
        ]))->get($url, $filters);

        // Handle request failure
        if ($response->failed()) {            
            throw new \Exception('Failed to retrieve invoices: ' . $response->body());
        }

        // Parse the response data
        $data = $response->json();        
        foreach($data['items'] as $invoiceData) {            
            $invoices[] = new CryptoInvoiceDTO(
                $invoiceData['id'],
                $invoiceData['amount']['total'],
                $this->_currencyMapping($invoiceData['currency']['id']),
                $this->_statusMapping($invoiceData['status']),                
                $this->_getPaymentAddress($invoiceData['id'], $invoiceData['currency']['id']),
                Carbon::parse($invoiceData['dueDate'])->unix(),
                $invoiceData['notes'] ?? null // Description                
            );
        }
         
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