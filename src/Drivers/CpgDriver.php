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
use Illuminate\Http\Request;

class CpgDriver implements PaymentGatewayInterface
{
    protected $config;    
    protected $acceptCurrencies = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->acceptCurrencies = config('payment_adapter.currencies', []);
    }

    private function statusMapping($status): string
    {
        $mapping = [
            'PENDING' => 'PENDING',
            'PARTIAL_FULFILLED' => 'PARTIAL_FULFILLED',
            'FULFILLED' => 'FULFILLED',
            'SUCCESSED' => 'SUCCESSED',
            'EXPIRED' => 'EXPIRED'
        ];
        return $mapping[$status] ?? 'EXPIRED';
    }

    /**
     * Get currency key by network and symbol
     *
     * @param string $network
     * @param string $symbol
     * @return string|null
     */
    private function getCurrencyKey(string $network, string $symbol): ?string
    {        
        
        foreach ($this->acceptCurrencies as $key => $currency) {
            if (strtolower($currency['network']) === strtolower($network) && strtolower($currency['symbol']) === strtolower($symbol)) {
                return $key;
            }
        }
        
        return null;
    }

    /**
     * Get list of all payments with optional filter.
     *
     * @return CryptoInvoiceDTO[]
     */
    public function getInvoices(array $filters): array
    {
        // Call CPG API to retrieve payments
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apikey'],            
        ])->get($this->config['api_url'] . '/payments', [
            'filters' => $filters
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to retrieve payments: ' . $response->body());
        }
        $paymentsData = $response->json();
        $payments = [];
        foreach ($paymentsData as $data) {
            $payment = new CryptoInvoiceDTO();
            $payment->id = $data['paymentId'];
            $payment->amount = $data['amount'];
            $payment->currency = $data['currency'];
            $payment->cryptoNetwork = $data['networkType'];
            $payment->status = $this->statusMapping($data['status']);
            $payment->address = $data['receivingAddress'];
            $payment->expirationTime = Carbon::parse($data['expiresAt'])->timestamp;
            $payments[] = $payment;
        }
        return $payments;
    }

    /**
     * Get a payment by its unique identifier.
     *
     * @param string $id
     * @return CryptoInvoiceDTO
     */
    public function getInvoiceById(string $id): CryptoInvoiceDTO
    {
        // Call CPG API to retrieve a specific payment by ID
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apikey'],
        ])->get($this->config['api_url'] . '/payments/' . $id);
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve payment: ' . $response->body());
        }
        $data = $response->json();
        $payment = new CryptoInvoiceDTO(
            $data['paymentId'],
            $data['amount'],
            new CryptoCurrencyDTO($this->getCurrencyKey($data['network'], $data['currency'])),
            $this->statusMapping($data['status']),
            $data['receivingAddress'],
            Carbon::parse($data['expiresAt'])->timestamp
        );
        
        return $payment;
    }

    /**
     * Create a new payment with the provided data.
     *
     * @param CryptoInvoiceDTO $payment
     * @return CryptoInvoiceDTO
     */
    public function createInvoice(CryptoInvoiceDTO $payment): CryptoInvoiceDTO
    {
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apikey'],
        ])->post($this->config['api_url'] . '/payments', [
            'amount' => $payment->amount,
            'currency' => $payment->currency->symbol,
            'network' => $payment->currency->network,
            'description' => 'Payment for order #' . $payment->id,
            'callback_url' => 'https://yourdomain.com/api/payment/callback',
            'merchantOrderId' => $payment->id
        ]);
        
        if ($response->failed()) {
            throw new \Exception('Failed to create payment: ' . $response->body());
        }
        $data = $response->json();

        // Clone invoice object
        $responsePaymentDTO = new CryptoInvoiceDTO(
            $data['paymentId'],
            $data['amount'],
            $payment->currency,
            $this->statusMapping($data['status']),
            $data['receivingAddress'],
            Carbon::parse($data['expiresAt'])->timestamp
        );
        
        return $responsePaymentDTO;
    }

    public function handleWebhook(Request $request): CryptoInvoiceDTO
    {
        $payload = $request->all();
        // Validate webhook signature if necessary
        // Process the webhook payload
        $paymentId = $payload['paymentId'] ?? null;
        if (!$paymentId) {
            throw new \Exception('Invalid webhook payload: missing paymentId');
        }
        return $this->getPaymentById($paymentId);
    }
}