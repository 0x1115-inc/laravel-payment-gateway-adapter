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

namespace MCXV\PaymentAdapter\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use MCXV\PaymentAdapter\PaymentAdapterManager;

class WebhookController extends Controller 
{
    public function handle(Request $request, string $provider)
    {
        $gateway = app(PaymentAdapterManager::class)->driver($provider);
        
        if (!$gateway) {
            return response()->json(['error' => 'Payment provider not found'], 404);
        }

        try {
            $invoice = $gateway->handleWebhook($request);

            return response()->json([
                'status' => 'success',
                'invoice' => $invoice
            ], 200);            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}