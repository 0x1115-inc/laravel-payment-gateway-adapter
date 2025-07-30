# Laravel Payment Gateway Adapter
This package provides a simple adapter for integrating various payment gateways into Laravel 11 applications. It allows developers to easily switch between different payment providers without changing the core application logic.

The adapter does not store data or state; it simply provides a unified interface for payment operations. All actions will be broadcast via events.

## Procedures
### Primary Flow
```plantuml
@startuml
actor User
participant "Main Application" as App
participant "Payment Gateway Adapter" as Adapter
participant "Payment Provider" as Provider

== Payment request ==
User -> App : Initiate Payment
App -> App : Create Invoice
App -> Adapter : Process Payment
Adapter -> Provider : Payment Creation Request
Provider -> Adapter : Payment Creation Response
Adapter -> App : Invoice Information
== Webhook ==

Provider -> Adapter : Send Webhook Notification
Adapter -> Adapter : Create Payment Event
App -> App : Handle Payment Event

@enduml
```
