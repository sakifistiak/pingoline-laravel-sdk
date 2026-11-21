<?php

namespace PingoLine\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array createOrderCall(array $data)
 * @method static array getOrderCall(string $callId)
 * @method static array listOrderCalls(array $filters = [])
 * @method static array sendSms(string $phone, string $message, ?string $type = null)
 * @method static array sendBulkSms(array|string $recipients, string $message, ?string $type = null)
 * @method static array getSmsBalance()
 * @method static array getSmsLogs(array $filters = [])
 * @method static array originateCall(string $extension, string $phone)
 * @method static array getCalls(array $filters = [])
 * @method static array getBalance()
 * @method static array ping()
 * @method static bool verifyWebhookSignature(string $payload, string $signature, ?string $secret = null)
 * @method static \PingoLine\Laravel\Webhooks\WebhookEvent constructEventFromRequest(\Illuminate\Http\Request $request, ?string $secret = null)
 *
 * @see \PingoLine\Laravel\PingoLineClient
 */
class PingoLine extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'pingoline';
    }
}
