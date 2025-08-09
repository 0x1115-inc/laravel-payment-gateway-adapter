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
use MCXV\PaymentAdapter\Events\InvoiceCreated;
use MCXV\PaymentAdapter\Events\InvoiceFulfilled;
use MCXV\PaymentAdapter\Events\InvoiceCompleted;
use MCXV\PaymentAdapter\Events\InvoiceCancelled;
use MCXV\PaymentAdapter\Events\InvoiceTimedOut;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CoinPaymentsDriver implements PaymentGatewayInterface
{
    private $_currencyMapping = [
        '1' => '1', // Bitcoin
        '4' => '2', // Ethereum
        '35:0x55d398326f99059ff775485246999027b3197955' => '3', // Tether USD on BSC
        '9:TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t' => '4', // Tether USD on TRON
        '4:0xdac17f958d2ee523a2206206994597c13d831ec7' => '5', // Tether USD on Ethereum
        '1002' => 't6', // Litecoin Test
    ];

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
                new CryptoCurrencyDTO($this->_currencyMapping[$invoiceData['currency']['id']] ?? null),
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
            'url' => $url            
        ]))->get($url);

        // Handle request failure
        if ($response->failed()) {
            if ($response->status() === 404) {
                throw new \Exception('Invoice not found: ' . $id);
            }
            throw new \Exception('Failed to retrieve invoice: ' . $response->body());
        }

        // Parse the response data
        $data = $response->json();        
        if (!isset($data)) {
            throw new \Exception('Invoice not found: ' . $id);
        }
        
        $invoice = new CryptoInvoiceDTO(
            $data['id'],
            $data['amount']['total'],
            new CryptoCurrencyDTO($this->_currencyMapping[$data['currency']['id']] ?? null),
            $this->_statusMapping($data['status']),
            $this->_getPaymentAddress($data['id'], $data['currency']['id']),
            Carbon::parse($data['dueDate'])->unix(),
            $data['notes'] ?? null // Description
        );
        
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

        $paymentCurrency = '' . array_flip($this->_currencyMapping)[$invoice->currency->id] ?? null;
        // datetime format

        $payload = [
            'currency' => $paymentCurrency,
            'items' => [
                [                    
                    'name' => 'Invoice #' . $invoice->getId() ,
                    'quantity' => [
                        'value' => 1,
                        'type' => 'quantity'
                    ], 
                    'amount' => $invoice->getAmount(),
                    // Optional fields
                    // 'customId' => $invoice->getId(), // Custom ID for the invoice
                    // 'sku' => $invoice->getId(), // SKU for the invoice
                    // 'description' => $invoice->getDescription() ?? 'Invoice #' . $invoice->getId(),
                    // 'originalAmount' => $invoice->getAmount(), // Original amount of the invoice
                    // 'tax' => 0, // Set tax to 0 if not applicable
                ],
            ],
            'amount' => [
                'breakdown' => [
                    'subtotal' => $invoice->getAmount(),
                    // Optional fields
                    // 'shipping' => 0, // Set tax to 0 if not applicable          
                    // 'taxTotal' => 0, // Set tax to 0 if not applicable
                    // 'handling' => 0, // Set handling to 0 if not applicable
                    // 'discount' => 0, // Set discount to 0 if not applicable
                ],
                'total' => $invoice->getAmount(), // Total amount of the invoice
            ],
            'isEmailDelivery' => false, // Whether to send invoice via email
            'emailDelivery' => null, // Set the email address for delivery if needed            
            'buyer' => null,
            // 'buyer' => [
            //     'name' => [
            //         'firstName' => 'Duog',
            //         'lastName' => 'Lee',
            //     ],
            //     'address' => [
            //         'address1' => $invoice->getBuyerAddress1() ?? 'Unknown',
            //         'city' => $invoice->getBuyerCity() ?? 'Unknown',
            //         'provinOrState' => $invoice->getBuyerProvinceOrState() ?? 'Unknown',
            //         'countryCode' => $invoice->getBuyerCountryCode() ?? 'Unknown',
            //         'postalCode' => $invoice->getBuyerPostalCode() ?? 'Unknown',
            //     ],
            //     'hasData' => false, // Indicates that buyer data is provided
            // ],
            'shipping' => null,
            'merchantOptions' => [
                'showAddress' => false, // Indicates whether the address should be shown on the invoice. Default is don't show if not provided.
                'showEmail' => false, // indicates whether the email should be shown on the invoice. Default is show the email if not provided.
                'showPhone' => false, // Indicates whether the phone should be shown on the invoice. Default is don't show if not provided.
                'showRegistrationNumber' => false, // Indicates whether the registration number should be shown on the invoice. Default is don't show if not provided.
                'showTaxId' => false, // Indicates whether the tax number should be shown on the invoice. Default is don't show if not provided.            
            ],
            'payment' => [
                'paymentCurrency' => $paymentCurrency, // The currency in which the payment will be made
                'refundEmail' => $this->config['refund_email'], // Optional refund email address
            ],
            'hideShoppingCart' => true, // Indicates whether the shopping cart should be hidden on the invoice. Default is don't hide if not provided.
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
        if (!isset($data) || count($data['invoices']) == 0) {
            throw new \Exception('Failed to create invoice: ' . $response->body());
        }
        
        $createdInvoice = $this->getInvoiceById($data['invoices'][0]['id']);
        
        // Broadcast the invoice created event 
        event(new InvoiceCreated($createdInvoice));
        

        return $createdInvoice;
    }

    /**
     * Handle a webhook request from the payment provider.
     *
     * @param Request $request
     * @return CryptoInvoiceDTO
     */
    public function handleWebhook(\Illuminate\Http\Request $request): CryptoInvoiceDTO
    {
        // Validate the request signature
        $signature = $request->header('X-CoinPayments-Signature');
        if (!$signature) {
            throw new \Exception('Missing signature in request header');
        }

        $requestTimestamp = $request->header('X-CoinPayments-Timestamp');
        $url = $request->fullUrl();        
        
        // Verify the signature
        $expectedSignature = base64_encode(
            hash_hmac(
                'sha256',
                "\u{feff}" . $request->method() . $url . 
                $this->config['client_id'] . $requestTimestamp . 
                $request->getContent(),
                $this->config['client_secret'],
                true
            )
        );     
        
        if ($signature !== $expectedSignature) {
            throw new \Exception('Invalid signature in request header');
        }
        
        // Parse the request data
        $data = $request->json();

        // The coinpayments support following event types
        // - InvoiceCreated
        // - InvoicePaymentCreated
        // - InvoicePending
        // - InvoicePaid
        // - InvoiceCompleted
        // - InvoiceCancelled
        // - InvoiceTimedOut
        // - PaymentCreated
        // - PaymentTimedOut
        // - InvoicePaymentTimedOut
        // Reference: https://a-docs.coinpayments.net/api/webhooks/clients
        // We will handle only InvoiceCreated, Invoice Completed, InvoiceCancelled and InvoiceTimedOut events        

        $currency = new CryptoCurrencyDTO(); // TODO: Implement currency mapping 

        $invoice = new CryptoInvoiceDTO(
            $data['invoice']['id'],
            $data['invoice']['amount']['total'],
            $currency,
            $this->_statusMapping(strtolower($data['invoice']['state'])),
            '',
            Carbon::parse($data['dueDate'])->unix(),
            $data['notes'] ?? null // Description
        );

        switch (strtolower($data['type'])) {
            case 'invoicecreated':
            case 'invoicepaymentcreated':
                event(new InvoiceCreated($invoice));
                break;
            case 'invoicepaid':
                event(new InvoiceFulfilled($invoice));
                break;
            case 'invoicecompleted':
                event(new InvoiceCompleted($invoice));
                break;
            case 'invoicecancelled':
                event(new InvoiceCancelled($invoice));                
                break;
            case 'invoicetimedout':
            case 'invoicepaymenttimedout':
                event(new InvoiceTimedOut($invoice));            
                break;
            default:
                \Log::info('Unhandled CryptoPayment webhook event type: ' . $data['type'], [
                    'invoice_id' => $data['invoice']['id'],
                    'event_id' => $data['id'],
                ]);        
        }
        
        return $invoice;
    }
    
}
