# Driver provider integration
This document describes how to integrate a payment gateway driver provider into your Laravel application using the `0x1115/laravel-payment-gateway-adapter` package.

## Implenenting the Driver
1. **Create the Driver Class**: Create a new class that implements the `Contracts/PaymentGatewayInterface` in directory `Drivers`. This class will handle the communication with the payment gateway's API.