# Official PingoLine Laravel SDK

[![Latest Version on Packagist](https://img.shields.io/packagist/v/pingoline/laravel-sdk.svg?style=flat-square)](https://packagist.org/packages/pingoline/laravel-sdk)
[![Total Downloads](https://img.shields.io/packagist/dt/pingoline/laravel-sdk.svg?style=flat-square)](https://packagist.org/packages/pingoline/laravel-sdk)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

The official Laravel package for [PingoLine](https://pingoline.com) — Bangladesh's premier Cloud PBX, Automated IVR Order Confirmation Calling, and SMS Gateway platform for eCommerce, businesses, and call centers.

---

## Features

- 📞 **Automated Order Confirmation Calls**: Instant IVR phone calls with Banglish/Bangla interactive voice prompt to reduce Cash on Delivery (COD) fake orders and return rate.
- 📱 **SMS Gateway**: Send OTP, transaction notifications, single & bulk marketing/transactional SMS across all Bangladeshi mobile operators.
- 🎧 **PBX & Telephony Integration**: Two-legged click-to-call, live extensions, and recording access.
- 🔔 **Secure Webhook Verification**: Seamless HMAC-SHA256 signature verification for instant order updates (confirmed, cancelled, no-answer).
- ⚡ **Laravel Auto-Discovery & Facade**: Native `PingoLine::createOrderCall(...)` facade support.

---

## Requirements

- PHP 8.1 or higher
- Laravel 10.x, 11.x, or 12.x

---

## Installation

Install the package via Composer:

```bash
composer require pingoline/laravel-sdk
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag="pingoline-config"
```

This will create `config/pingoline.php` in your Laravel app.

---

## Configuration

Add your PingoLine API credentials to your `.env` file:

```env
PINGOLINE_API_KEY="your_api_token_here"
PINGOLINE_API_URL="https://pingoline.com/api/v1"
PINGOLINE_WEBHOOK_SECRET="your_webhook_secret_here"
```

> 🔑 Get your API token and Webhook Secret from the [PingoLine Developer Portal](https://pingoline.com/portal/developers).

---

## Usage

### 1. Automated Order Confirmation Calls (eCommerce COD)

Dispatch an automated voice call to confirm a customer's order:

```php
use PingoLine\Laravel\Facades\PingoLine;

$call = PingoLine::createOrderCall([
    'phone'         => '01712345678',
    'order_id'      => 'ORD-98421',
    'customer_name' => 'Rahim Ahmed',
    'amount'        => 1450.00,
    'currency'      => 'BDT',
    'language'      => 'bn', // 'bn' or 'en'
    'send_sms'      => true, // Send SMS if customer doesn't pick up
    'sms_message'   => 'Dear customer, please confirm your order at our website.',
]);

// Returns:
// [
//     'data' => [
//         'id' => 'oc_8f7b2c9e',
//         'order_id' => 'ORD-98421',
//         'phone' => '01712345678',
//         'status' => 'queued',
//         'created_at' => '2026-11-21T12:00:00Z'
//     ]
// ]
```

#### Check Call Status

```php
$status = PingoLine::getOrderCall('oc_8f7b2c9e');
```

---

### 2. Handling Webhooks (Instant Confirmation Updates)

When the customer presses **1** (Confirm) or **2** (Cancel) on their dialpad, PingoLine sends an event to your webhook URL.

Create a route in `routes/api.php`:

```php
use Illuminate\Http\Request;
use PingoLine\Laravel\Facades\PingoLine;

Route::post('/webhooks/pingoline', function (Request $request) {
    // Automatically validates HMAC-SHA256 signature using PINGOLINE_WEBHOOK_SECRET
    $event = PingoLine::constructEventFromRequest($request);

    if ($event->isOrderCallCompleted()) {
        $orderId = $event->getOrderId();

        if ($event->isConfirmed()) {
            // Customer pressed 1: Update your order status to Confirmed / Processing
            Order::where('id', $orderId)->update(['status' => 'confirmed']);
        } elseif ($event->isCancelled()) {
            // Customer pressed 2: Mark order as cancelled
            Order::where('id', $orderId)->update(['status' => 'cancelled']);
        }
    }

    return response()->json(['status' => 'ok']);
});
```

> **Note:** Exclude your webhook route from CSRF verification in `bootstrap/app.php` (Laravel 11+) or `app/Http/Middleware/VerifyCsrfToken.php` (Laravel 10).

---

### 3. SMS Gateway

#### Send Single / OTP SMS

```php
use PingoLine\Laravel\Facades\PingoLine;

$res = PingoLine::sendSms(
    phone: '01700000000',
    message: 'Your verification code is 458921. Valid for 5 minutes.',
    type: 'otp'
);
```

#### Send Bulk SMS

```php
$numbers = ['01700000000', '01800000000', '01900000000'];

$res = PingoLine::sendBulkSms(
    recipients: $numbers,
    message: 'Exciting weekend discount! Enjoy 20% off on all products.'
);
```

#### Check SMS Balance

```php
$balance = PingoLine::getSmsBalance();
```

---

### 4. Telephony & Click-to-Call (CallDesk / PBX)

Initiate an automated call between your agent's extension and a customer's phone:

```php
$call = PingoLine::originateCall(
    extension: '101',       // Agent PBX extension
    phone: '01712345678'    // Customer phone number
);
```

---

### 5. Check Voice Wallet & Account Balance

```php
$balance = PingoLine::getBalance();

// Returns:
// [
//     'account' => 'Your Business Name',
//     'wallet' => [
//         'balance' => '1250.00',
//         'currency' => 'BDT'
//     ],
//     'numbers' => [...]
// ]
```

---

## Testing

Run unit tests via PHPUnit:

```bash
composer test
```

---

## Security Vulnerabilities

If you discover any security vulnerabilities, please send an e-mail to [hello@pingoline.com](mailto:hello@pingoline.com).

---

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. Developed with ❤️ by [PingoLine](https://pingoline.com).
