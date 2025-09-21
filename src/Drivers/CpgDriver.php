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
use MCXV\PaymentAdapter\DTO\PaymentDTO;

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class CpgDriver implements PaymentGatewayInterface
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
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
     * Get list of all payments with optional filter.
     *
     * @return PaymentDTO[]
     */
    public function getPayments(array $filters): array
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
            $payment = new PaymentDTO();
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
     * @return PaymentDTO
     */
    public function getPaymentById(string $id): PaymentDTO
    {
        // Call CPG API to retrieve a specific payment by ID
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apikey'],
        ])->get($this->config['api_url'] . '/payments/' . $id);
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve payment: ' . $response->body());
        }
        $data = $response->json();
        $payment = new PaymentDTO();
        $payment->id = $data['paymentId'];
        $payment->amount = $data['amount'];
        $payment->currency = $data['currency'];
        $payment->cryptoNetwork = $data['network'];
        $payment->status = $this->statusMapping($data['status']);
        $payment->address = $data['receivingAddress'];
        $payment->expirationTime = Carbon::parse($data['expiresAt'])->timestamp;
        
        return $payment;
    }

    /**
     * Create a new payment with the provided data.
     *
     * @param PaymentDTO $payment
     * @return PaymentDTO
     */
    public function createPayment(PaymentDTO $payment): PaymentDTO
    {
        $response = Http::withHeaders([
            'X-API-KEY' => $this->config['apikey'],
        ])->post($this->config['api_url'] . '/payments', [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'network' => $payment->cryptoNetwork,
            'description' => 'Payment for order #' . $payment->id,
            'callback_url' => 'https://yourdomain.com/api/payment/callback',
            'merchantOrderId' => $payment->id
        ]);
        
        if ($response->failed()) {
            throw new \Exception('Failed to create payment: ' . $response->body());
        }
        $data = $response->json();

        $responsePaymentDTO = new PaymentDTO();
        $responsePaymentDTO->id = $data['paymentId'];
        $responsePaymentDTO->status = $this->statusMapping($data['status']);
        $responsePaymentDTO->address = $data['receivingAddress'];
        $responsePaymentDTO->expirationTime = Carbon::parse($data['expiresAt'])->timestamp;
        $responsePaymentDTO->cryptoNetwork = $data['network'];
        $responsePaymentDTO->currency = $data['currency'];
        $responsePaymentDTO->amount = $data['amount'];        

        return $responsePaymentDTO;
    }

    public function handleWebhook(Request $request): PaymentDTO
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