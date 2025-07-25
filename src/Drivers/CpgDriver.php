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

namespace MCXV\LaravelPaymentAdapter\Drivers;
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

    /**
     * Get list of all payments with optional filter.
     *
     * @return PaymentDTO[]
     */
    public function getPayments(array $filters): array
    {
        // Call CPG API to retrieve payments
        $response = HTTP::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],            
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
            $payment->id = $data['id'];
            $payment->amount = $data['amount'];
            $payment->currency = $data['currency']['symbol'];
            $payment->cryptoNetwork = $data['currency']['network'];
            $payment->status = $data['status'];
            $payment->address = $data['receive_address'];
            $payment->expirationTime = Carbon::parse($data['payment_deadline'])->timestamp;
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
        $response = HTTP::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],            
        ])->get($this->config['api_url'] . '/payments/' . $id);
        if ($response->failed()) {
            throw new \Exception('Failed to retrieve payment: ' . $response->body());
        }
        $data = $response->json();
        $payment = new PaymentDTO();
        $payment->id = $data['id'];
        $payment->amount = $data['amount'];
        $payment->currency = $data['currency']['symbol'];
        $payment->cryptoNetwork = $data['currency']['network'];
        $payment->status = $data['status'];
        $payment->address = $data['receive_address'];
        $payment->expirationTime = Carbon::parse($data['payment_deadline'])->timestamp;
        
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
        $response = HTTP::withHeaders([
            'Authorization' => 'Bearer ' . $this->config['api_key'],            
        ])->post($this->config['api_url'] . '/payments', [
            'amount' => $payment->amount,
            'currency_symbol' => $payment->currency,
            'currency_network' => $payment->cryptoNetwork,            
        ]);
        
        if ($response->failed()) {
            throw new \Exception('Failed to create payment: ' . $response->body());
        }
        $data = $response->json();
        $payment->id = $data['id'];
        $payment->status = $data['status'];
        $payment->address = $data['receive_address'];
        $payment->expirationTime = Carbon::parse($data['payment_deadline'])->timestamp;
        $payment->cryptoNetwork = $data['currency']['network'];
        $payment->currency = $data['currency']['symbol'];
        $payment->amount = $data['amount'];
        $payment->status = $data['status'];        

        return $payment;
    }
}