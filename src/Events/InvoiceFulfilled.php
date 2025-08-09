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

namespace MCXV\PaymentAdapter\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

use MCXV\PaymentAdapter\DTO\CryptoInvoiceDTO;

/**
 * Handles the invoice fulfillment process when sufficient payment amount is received
 * and confirmation is obtained.
 * 
 * This method processes an invoice that has received adequate payment and proper
 * confirmation, marking it as fulfilled and completing the transaction workflow.
 * 
 * @param float $amount The payment amount received
 * @param bool $confirmation Whether the payment has been confirmed
 * @return bool Returns true if invoice is successfully fulfilled, false otherwise
 */

class InvoiceFulfilled
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public CryptoInvoiceDTO $invoice;

    /**
     * Create new event instance
     */
    public function __construct(CryptoInvoiceDTO $invoice)
    {
        $this->invoice = $invoice;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payment-adapter')
        ];
    }
}