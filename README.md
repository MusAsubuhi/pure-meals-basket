# Pure Meals Basket

**Pure Meals Basket (PMB)** is a Laravel-based catering and food ordering platform for customers booking **catering services, juices & beverages, cakes, and celebration foods**.

The system is designed around the reality that not every request can be priced immediately. Some products have fixed or calculated prices, while custom catering and bespoke requests may require PMB staff to review the request and issue a quotation.

The platform therefore supports a complete customer journey from browsing the catalogue through request submission, quotation, payment, order confirmation, fulfillment, and completion.

> **Simply Delicious and Refreshing.**

---

## Table of Contents

* [Overview](#overview)
* [Core Business Model](#core-business-model)
* [Customer Journey](#customer-journey)
* [System Architecture](#system-architecture)
* [Core Modules](#core-modules)
* [Catalogue & Pricing](#catalogue--pricing)
* [Request Engine](#request-engine)
* [Quotation Engine](#quotation-engine)
* [Order Engine](#order-engine)
* [Payment Engine](#payment-engine)
* [Fulfillment Engine](#fulfillment-engine)
* [Customer Portal](#customer-portal)
* [Admin & Staff Operations](#admin--staff-operations)
* [Technology Stack](#technology-stack)
* [Architecture Principles](#architecture-principles)
* [Installation](#installation)
* [Configuration](#configuration)
* [Database](#database)
* [Queues & Scheduled Tasks](#queues--scheduled-tasks)
* [Testing](#testing)
* [Development Workflow](#development-workflow)
* [Project Structure](#project-structure)
* [Business Rules](#business-rules)
* [Product Rollout](#product-rollout)
* [Current Status](#current-status)
* [Future Modules](#future-modules)

---

# Overview

Pure Meals Basket is a customer-facing platform for managing food and catering bookings.

PMB serves customers requiring:

* Catering
* Tea baskets
* Juices and beverages
* Cakes
* Celebration foods
* Institutional and recurring food delivery
* Weddings and celebrations
* Corporate events
* Church events
* School events
* Other custom food requirements

The platform supports both **immediately priced products** and **custom requests requiring PMB review**.

---

# Core Business Model

Not every customer request follows the same pricing path.

A customer can request:

### Fixed Order

The price is already known.

```text
Customer
   ↓
Select product
   ↓
Price calculated
   ↓
Request
   ↓
Checkout
   ↓
Payment
   ↓
Order confirmed
```

### Custom Request

The price cannot be determined immediately.

```text
Customer
   ↓
Describe/select requirements
   ↓
Request submitted
   ↓
PMB review
   ↓
Quotation
   ↓
Customer accepts
   ↓
Payment
   ↓
Order confirmed
```

### Catering

Catering may use calculated pricing, but certain requirements may require PMB review and quotation.

```text
Customer
   ↓
Catering request
   ↓
PMB review
   ↓
Calculated price OR quotation
   ↓
Customer decision
   ↓
Payment
   ↓
Order
```

This distinction is fundamental to the application.

---

# Customer Journey

The complete customer-facing journey is:

```text
Registration
    ↓
Login
    ↓
Customer Dashboard
    ↓
Browse Catalogue
    ↓
Select Products / Services
    ↓
Build Request
    ↓
Provide Event Details
    ↓
Submit Request
    ↓
PMB Review
    │
    ├── Information required
    │       ↓
    │   Customer responds
    │       ↓
    │   PMB continues review
    │
    └── Quotation required
            ↓
        Quotation
            ↓
      ┌─────┼─────┐
      ↓     ↓     ↓
   Accept Changes Decline
      ↓
    Order
      ↓
   Payment
      ↓
  Confirmed
      ↓
  Preparing
      ↓
    Ready
      ↓
 Delivery / Collection / On-site Service
      ↓
  Completed
```

The underlying system is modular, but the customer should experience this as one continuous journey.

---

# System Architecture

```text
                         CUSTOMER
                            │
                            ↓
                     CUSTOMER PORTAL
                            │
                            ↓
                      PRODUCT CATALOGUE
                            │
                            ↓
                      REQUEST ENGINE
                            │
               ┌────────────┴────────────┐
               │                         │
        Calculated items           Custom items
               │                         │
               └────────────┬────────────┘
                            ↓
                    QUOTATION ENGINE
                            │
                            ↓
                       ORDER ENGINE
                            │
                            ↓
                      PAYMENT ENGINE
                            │
                            ↓
                    FULFILLMENT ENGINE
                            │
              ┌─────────────┼─────────────┐
              ↓             ↓             ↓
          DELIVERY      COLLECTION     ON-SITE
              │             │             │
              └─────────────┴─────────────┘
                            ↓
                        COMPLETED
                            │
                            ↓
                         FEEDBACK
```

---

# Core Modules

The application is divided into domain modules.

| Module          | Responsibility                                        |
| --------------- | ----------------------------------------------------- |
| Authentication  | Registration, login and authentication                |
| Customer        | Customer accounts and ownership                       |
| Catalogue       | Products, services, categories and availability       |
| Pricing         | Centralized product/service pricing                   |
| Request         | Customer requests and review workflow                 |
| Quotation       | Custom pricing and quotation lifecycle                |
| Order           | Commercial order created from accepted quotation      |
| Payment         | M-Pesa and cash payments                              |
| Fulfillment     | Preparation, delivery, collection and on-site service |
| Customer Portal | Unified customer-facing experience                    |
| Admin           | PMB staff operations and management                   |

---

# Catalogue & Pricing

The catalogue is the source of truth for products and services.

Supported pricing types include:

```text
FIXED
PER_UNIT
PER_WEIGHT
PER_VOLUME
PER_PERSON
TIERED
CUSTOM
```

The system does **not** unnecessarily duplicate prices.

For example, instead of storing:

```text
1kg → KSh 1,000
2kg → KSh 2,000
3kg → KSh 3,000
```

the system can store a base pricing rule and calculate the price according to quantity.

For example:

```text
1kg base price = KSh 1,000

3kg × KSh 1,000
= KSh 3,000
```

Where tiered pricing is genuinely required, explicit price tiers can be configured.

---

## Product Options

Products can have configurable options with price modifiers.

Example:

```text
Chocolate Cake

Base:
3kg → KSh 3,000

Options:
Fondant → +KSh 800
Premium decoration → +KSh 1,000

Total:
KSh 4,800
```

---

## Pricing Authority

Pricing is centralized in:

```text
app/Services/Pricing/ProductPricingService.php
```

The application should not duplicate pricing logic in controllers, Blade templates, or JavaScript.

The pricing engine:

1. Validates that the item is requestable.
2. Determines the pricing rule.
3. Calculates the base price.
4. Applies quantity.
5. Applies options/modifiers.
6. Determines whether PMB quotation is required.
7. Returns a `QuoteResult`.

The pricing engine does not write orders or requests.

---

# Request Engine

The Request Engine handles customer requests before they become orders.

A request may contain:

* Products
* Services
* Quantities
* Options
* Event date
* Event time
* Location
* Customer notes
* Pricing information
* PMB clarifications

A request can contain both calculated and custom-priced items.

Example:

```text
Request

Chocolate Cake
3kg
KSh 3,800

Passion Juice
20L
KSh 6,000

Custom Catering
150 guests
Quotation required
```

If any item requires quotation, the request reflects that state.

---

## Request Lifecycle

```text
DRAFT
  ↓
SUBMITTED
  ↓
UNDER_REVIEW
  │
  ├── NEEDS_INFORMATION
  │       ↓
  │   Customer responds
  │       ↓
  │   UNDER_REVIEW
  │
  ├── QUOTATION_REQUIRED
  │
  ├── READY_FOR_CHECKOUT
  │
  ├── DECLINED
  │
  └── CANCELLED
```

Requests are formal records.

The cart is temporary and session-based.

> **Request ≠ Cart**

---

# Request Snapshots

Once a request becomes commercially significant, important pricing and product information is preserved as a snapshot.

Request items retain information such as:

* Product/service reference
* Name
* Quantity
* Unit
* Options
* Pricing status
* Unit price
* Subtotal
* Notes

This protects historical records if catalogue information changes later.

---

# Request Events

Requests maintain an audit trail through `request_events`.

This makes it possible to reconstruct important activity such as:

```text
Request submitted
PMB started review
PMB requested information
Customer responded
Quotation required
Request approved for checkout
Request declined
Request cancelled
```

---

# Quotation Engine

The Quotation Engine handles requests where PMB needs to provide a formal price.

Quotation statuses:

```text
DRAFT
SENT
ACCEPTED
DECLINED
WITHDRAWN
EXPIRED
```

---

## Quotation Lifecycle

```text
DRAFT
  ↓
SENT
  │
  ├── ACCEPTED
  │
  ├── DECLINED
  │
  ├── WITHDRAWN
  │
  └── EXPIRED
```

---

## Quotation Rules

### Immutability

A sent quotation cannot simply be edited.

If changes are required:

```text
SENT
 ↓
WITHDRAW
 ↓
NEW QUOTATION
```

This protects the commercial record.

### Single Active Quotation

Only one `SENT` quotation is allowed for a request at a time.

### Validity

Quotations are valid for **7 days** from `sent_at`.

Expired quotations can be processed automatically using:

```bash
php artisan quotations:expire
```

### Acceptance

When a customer accepts a quotation:

```text
Quotation
    ↓
ACCEPTED

Request
    ↓
READY_FOR_CHECKOUT

Order
    ↓
PENDING_PAYMENT
```

The transition is performed atomically.

---

# Order Engine

The Order Engine represents the commercial booking after quotation acceptance.

Order statuses:

```text
DRAFT
PENDING_PAYMENT
CONFIRMED
PREPARING
READY
OUT_FOR_DELIVERY
DELIVERED
COMPLETED
CANCELLED
```

Payment statuses:

```text
UNPAID
PARTIALLY_PAID
PAID
```

Fulfillment methods:

```text
DELIVERY
CUSTOMER_COLLECTION
ON_SITE_SERVICE
```

---

## Order Creation

An accepted quotation creates an order.

The order stores a complete snapshot of:

* Customer details
* Event details
* Items
* Prices
* Discounts
* Delivery charges
* Totals
* Fulfillment information

The order should remain historically accurate even if the original catalogue or quotation changes.

---

## Payment Requirement

An order becomes confirmed only after the required payment has been received.

```text
PENDING_PAYMENT
       ↓
   PAYMENT
       ↓
CONFIRMED
```

---

# Payment Engine

The payment system supports:

* M-Pesa
* Cash

The M-Pesa integration is designed around PayNexus and asynchronous payment verification.

Payment processing must account for:

* Payment initiation
* Processing state
* Provider responses
* Webhooks/callbacks
* Idempotency
* Verification
* Reconciliation
* Failed payments
* Stale payments

Cash payments require PMB staff confirmation.

A customer indicating that cash has been paid does **not** independently confirm the order.

---

## Payment Verification

Queued verification jobs include:

```text
VerifyPendingMpesaPaymentJob
VerifyStaleMpesaPaymentsJob
```

Payment reconciliation is available through:

```bash
php artisan payments:reconcile
```

Options include:

```bash
--hours
--dry-run
```

---

# Fulfillment Engine

The Fulfillment Engine manages the operational delivery of confirmed orders.

Supported fulfillment methods:

```text
DELIVERY
CUSTOMER_COLLECTION
ON_SITE_SERVICE
```

The fulfillment system maintains its own lifecycle and audit trail.

---

## Delivery

Typical flow:

```text
PREPARING
   ↓
READY
   ↓
OUT_FOR_DELIVERY
   ↓
DELIVERED
   ↓
COMPLETED
```

Failed deliveries can be recorded and retried.

---

## Customer Collection

```text
PREPARING
   ↓
READY
   ↓
COLLECTED
   ↓
COMPLETED
```

---

## On-site Service

```text
PREPARING
   ↓
READY
   ↓
SERVICE_IN_PROGRESS
   ↓
COMPLETED
```

---

## Fulfillment Events

All significant fulfillment activity is recorded.

Examples:

```text
Preparation started
Order ready
Dispatched
Delivery failed
Delivery retried
Delivered
Collected
Service started
Fulfillment completed
```

This creates an operational audit trail.

---

# Customer Portal

The customer portal is the unified presentation layer over the domain modules.

Primary area:

```text
/customer
```

Customer navigation should include:

```text
Dashboard
Browse
My Requests
My Quotations
My Orders
Payments
Profile
```

---

## Customer Dashboard

The dashboard should prioritize **actionable information**.

Examples:

```text
Quotation awaiting your response

Payment required

PMB needs more information

Order currently being prepared

Order out for delivery
```

The customer should not have to understand backend status enums.

---

## Customer Status Language

Internal:

```text
UNDER_REVIEW
PENDING_PAYMENT
OUT_FOR_DELIVERY
```

Customer-facing:

```text
PMB is reviewing your request
Payment required
Your order is on the way
```

The UI should always translate technical domain states into clear customer language.

---

# Customer Request Experience

The intended experience is:

```text
Browse
   ↓
Product
   ↓
Add to Request
   ↓
Request / Cart
   ↓
Event Details
   ↓
Submit
   ↓
Request Tracking
```

---

# Customer Quotation Experience

```text
Quotation List
   ↓
Quotation Detail
   ↓
Review Price
   ↓
Accept / Request Changes / Decline
```

The customer should see:

* Items
* Quantities
* Prices
* Discounts
* Delivery charges
* Total
* Validity date
* Terms/notes
* Available actions

---

# Customer Order Experience

```text
Orders
   ↓
Order Detail
   ↓
Payment Status
   ↓
Fulfillment Status
```

The customer should always be able to answer:

> What is the status of my order?

---

# Customer Payment Experience

The customer can:

```text
View amount due
      ↓
Choose payment method
      ↓
M-Pesa / Cash
      ↓
Payment processing
      ↓
Payment confirmation
```

The payment page should clearly display:

```text
Order total
Amount paid
Balance
Payment status
Payment history
```

---

# Unified Customer Timeline

The customer journey should be represented as a single understandable timeline:

```text
✓ Request submitted
      ↓
✓ PMB reviewed request
      ↓
✓ Quotation sent
      ↓
✓ Quotation accepted
      ↓
✓ Payment received
      ↓
✓ Order confirmed
      ↓
● Preparing
      ↓
○ Ready
      ↓
○ Delivery / Collection / Service
      ↓
○ Completed
```

Internally these events belong to different domain modules, but the customer experiences them as one journey.

---

# Admin & Staff Operations

PMB staff need operational visibility into:

* Catalogue
* Requests
* Clarifications
* Quotations
* Orders
* Payments
* Fulfillments
* Events/audit history

The admin system is built around Filament.

Important operational principle:

> Customers initiate and respond to commercial activity; PMB staff control review, quotation, payment confirmation where applicable, and fulfillment operations.

---

# Technology Stack

## Backend

* Laravel
* PHP
* MySQL

## Authentication & Authorization

* Laravel authentication
* Spatie permissions/roles
* Customer and staff authorization policies

## Admin

* Filament

## Frontend

* Blade
* Alpine.js
* Existing PMB frontend styling/assets

## Payments

* PayNexus / M-Pesa
* Cash payment workflow

## Testing

* PHPUnit / Laravel testing tools

## Code Quality

* Laravel Pint

---

# Architecture Principles

## 1. Single Responsibility

Each domain owns its own rules.

```text
Catalogue → products/pricing
Request → customer requests
Quotation → quotations
Order → commercial orders
Payment → payments
Fulfillment → operational delivery
```

---

## 2. Orchestrators Are the Entry Point

Domain workflows are handled through orchestrators rather than duplicated across controllers.

Examples:

```text
ProductPricingService
RequestOrchestrator
QuotationOrchestrator
OrderOrchestrator
FulfillmentOrchestrator
```

Controllers should remain thin.

---

## 3. Pricing Has One Authority

All pricing calculations go through:

```text
ProductPricingService
```

No duplicate pricing algorithms should be introduced.

---

## 4. Commercial Records Are Immutable

Once commercial activity begins, historical information should not silently change.

This applies especially to:

* Request pricing snapshots
* Sent quotations
* Orders
* Payments
* Fulfillment events

Changes should be explicit and auditable.

---

## 5. Auditability

Important business transitions generate events.

This makes the system suitable for real operational use and troubleshooting.

---

## 6. Policies Protect Ownership

Customers may access only their own:

* Requests
* Quotations
* Orders
* Payments
* Fulfillments

Staff have appropriate operational permissions.

---

# Installation

Clone the repository:

```bash
git clone <repository-url>
cd pure-meals-basket
```

Install PHP dependencies:

```bash
composer install
```

Install frontend dependencies if required:

```bash
npm install
```

Create the environment file:

```bash
cp .env.example .env
```

Generate the application key:

```bash
php artisan key:generate
```

Configure the database in `.env`.

Run migrations:

```bash
php artisan migrate
```

Seed development data:

```bash
php artisan db:seed
```

Start the application:

```bash
php artisan serve
```

---

# Configuration

Important environment configuration includes:

```env
APP_NAME="Pure Meals Basket"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
```

Payment-provider configuration should be stored in environment variables and must never be hard-coded.

---

# Database

The application uses MySQL.

Major domain tables include:

```text
users
customers

categories
products
product_options
product_option_values
services
price_tiers

requests
request_items
request_events
request_clarifications

quotations
quotation_items
quotation_events

orders
order_items
order_events

payments
payment_events

fulfillments
fulfillment_events
```

UUIDs are used for several commercial/domain records.

Soft deletion is used where historical records must be retained.

---

# Queues & Scheduled Tasks

Some operations are asynchronous.

Examples include payment verification.

Jobs include:

```text
VerifyPendingMpesaPaymentJob
VerifyStaleMpesaPaymentsJob
```

Quotation expiration can be run using:

```bash
php artisan quotations:expire
```

Payment reconciliation:

```bash
php artisan payments:reconcile
```

For production, Laravel's scheduler and queue workers should be configured appropriately.

---

# Testing

The project uses automated tests to protect domain behaviour.

Run the complete suite:

```bash
php artisan test
```

Run a specific module:

```bash
php artisan test tests/Unit/Request
```

or:

```bash
php artisan test tests/Feature/Request
```

Code formatting:

```bash
./vendor/bin/pint
```

Tests should be run after changes to domain logic, workflows, migrations, controllers, policies, or payment integrations.

---

# Development Workflow

Recommended development process:

```text
1. Understand the business rule
        ↓
2. Define/update domain model
        ↓
3. Implement service/orchestrator
        ↓
4. Add authorization
        ↓
5. Add controller/UI
        ↓
6. Add tests
        ↓
7. Run full test suite
        ↓
8. Run Pint
        ↓
9. Perform smoke testing
```

Business logic should not be introduced directly into Blade templates or duplicated in JavaScript.

---

# Project Structure

A simplified structure:

```text
app/
├── Console/
│   └── Commands/
│
├── Enums/
│   ├── Catalogue/
│   ├── Request/
│   ├── Quotation/
│   ├── Order/
│   └── Payment/
│
├── Filament/
│   └── Resources/
│
├── Http/
│   ├── Controllers/
│   └── Requests/
│
├── Jobs/
│
├── Models/
│   ├── Catalogue/
│   ├── Request/
│   ├── Quotation/
│   ├── Order/
│   ├── Payment/
│   └── Fulfillment/
│
├── Policies/
│
└── Services/
    ├── Pricing/
    ├── Request/
    ├── Quotation/
    ├── Order/
    ├── Payment/
    └── Fulfillment/

database/
├── factories/
├── migrations/
└── seeders/

resources/
└── views/
    ├── catalogue/
    ├── request/
    ├── quotation/
    ├── order/
    ├── payment/
    ├── fulfillment/
    └── customer/

routes/
└── web.php

tests/
├── Unit/
└── Feature/
```

---

# Business Rules

Some of the most important rules in the application are:

### Catalogue

* Only active/requestable catalogue items can be requested.
* Products and services should be archived rather than hard-deleted.
* Pricing is calculated centrally.

### Requests

* Cart is temporary.
* Request is the formal customer record.
* A request can contain mixed calculated and quotation-required items.
* Submitted requests become commercially controlled.
* Post-review changes must be explicit.
* Customer ownership is enforced.

### Quotations

* Sent quotations are immutable.
* Changes require withdrawal and replacement.
* Only one sent quotation exists for a request at a time.
* Quotations expire after 7 days.
* Customer acceptance is authorized.
* Accepted quotations lead to order creation.

### Orders

* One order is created for an accepted quotation.
* Orders preserve commercial snapshots.
* Payment must satisfy the required amount before confirmation.
* Cancellation is restricted to permitted pre-confirmation states.

### Payments

* M-Pesa payments are verified rather than blindly trusted.
* Payment callbacks/webhooks must be idempotent.
* Cash requires PMB confirmation.
* Payment history must remain auditable.

### Fulfillment

* Fulfillment is created from a confirmed order.
* Delivery, collection, and on-site service follow different operational paths.
* Delivery failures can be retried.
* Fulfillment events are audited.

---

# Product Rollout

The PMB product strategy is divided into phases.

## Phase 1 — Build Now

### Catering — Basket Tiers

Core revenue engine.

### Tea Basket

Priority offering for recurring institutional demand.

### Juices & Beverages

Low equipment cost and suitable as both standalone and catering add-ons.

### Cakes & Celebration Foods

High-margin event add-ons.

### Office / Institutional Recurring Delivery

Uses existing basket and delivery capabilities rather than introducing an entirely new product.

---

## Phase 3 — Stagger

### Live Cooking Station

Requires additional equipment and trained staff.

---

## Phase 4 — Stagger

### Signature Table — Five Course

Requires a different operating model involving:

* Waitstaff
* Plateware
* Timed courses
* Premium event operations

These offerings should be introduced only when the underlying operational capacity and customer demand justify them.

---

# Current Status

The core backend domain has been implemented progressively.

### Completed

* User authentication
* User roles
* Customer accounts
* Registration flow
* Customer redirection
* Product & Service Catalogue
* Centralized pricing engine
* Request Engine
* Quotation Engine
* Order Engine
* Payment Engine
* Fulfillment Engine

The current automated test suite has reached:

```text
146 tests
384 assertions
```

with the Fulfillment Engine verification completing successfully.

The customer-facing portal is the next major presentation-layer phase, bringing all completed backend domains together into one coherent customer experience.

---

# Future Modules

The architecture intentionally leaves room for additional functionality.

Potential future areas include:

* Advanced customer notifications
* SMS/email notifications
* Delivery routing
* Driver management
* Recurring institutional orders
* Advanced reporting
* Kitchen production planning
* Inventory management
* Expense tracking
* Staff scheduling
* Customer loyalty
* Advanced analytics
* Live delivery tracking
* Additional payment providers

These should be introduced only when the corresponding business requirements are established.

---

# Guiding Principle

Pure Meals Basket is not simply an online menu.

It is a **booking and operations platform for a real catering business**.

The system therefore treats:

```text
Products
Requests
Quotations
Orders
Payments
Fulfillment
```

as distinct business concepts while connecting them into one customer journey.

The goal is simple:

> **Make booking food and catering with Pure Meals Basket straightforward for the customer, while giving PMB complete control and visibility over the operation.**

**Simply Delicious and Refreshing.**
