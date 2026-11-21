<?php

namespace PingoLine\Laravel;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PingoLine\Laravel\Exceptions\AuthenticationException;
use PingoLine\Laravel\Exceptions\PingoLineException;
use PingoLine\Laravel\Exceptions\QuotaExceededException;
use PingoLine\Laravel\Exceptions\ValidationException;
use PingoLine\Laravel\Exceptions\WebhookVerificationException;
use PingoLine\Laravel\Webhooks\WebhookEvent;
use PingoLine\Laravel\Webhooks\WebhookSignature;

class PingoLineClient
{
    protected string $apiKey;
    protected string $apiUrl;
    protected ?string $webhookSecret;
    protected int $timeout;
    protected array $retry;

    public function __construct(
        string $apiKey,
        string $apiUrl = 'https://pingoline.com/api/v1',
        ?string $webhookSecret = null,
        int $timeout = 15,
        array $retry = ['times' => 2, 'sleep' => 200]
    ) {
        $this->apiKey = trim($apiKey);
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->webhookSecret = $webhookSecret ? trim($webhookSecret) : null;
        $this->timeout = $timeout;
        $this->retry = $retry;
    }

    /*
    |--------------------------------------------------------------------------
    | Order Confirmation Calls API
    |--------------------------------------------------------------------------
    */

    /**
     * Dispatch an automated order confirmation call.
     *
     * @param array{
     *     phone: string,
     *     order_id: string|int,
     *     customer_name?: string,
     *     amount?: float|int,
     *     currency?: string,
     *     language?: 'bn'|'en',
     *     callback_url?: string,
     *     send_sms?: bool,
     *     sms_message?: string,
     *     metadata?: array
     * } $data
     * @return array
     * @throws PingoLineException
     */
    public function createOrderCall(array $data): array
    {
        return $this->post('order-calls', $data);
    }

    /**
     * Get details and current status of an order confirmation call.
     *
     * @param string $callId Public ID of the order call (e.g. 'oc_xxx')
     * @return array
     * @throws PingoLineException
     */
    public function getOrderCall(string $callId): array
    {
        return $this->get("order-calls/{$callId}");
    }

    /**
     * List recent order confirmation calls with optional filters.
     *
     * @param array{order_id?: string, status?: string, per_page?: int} $filters
     * @return array
     * @throws PingoLineException
     */
    public function listOrderCalls(array $filters = []): array
    {
        return $this->get('order-calls', $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway API
    |--------------------------------------------------------------------------
    */

    /**
     * Send a single SMS or OTP message.
     *
     * @param string $phone Recipient mobile number (e.g. '017XXXXXXXX' or '+88017XXXXXXXX')
     * @param string $message Text content of the SMS
     * @param string|null $type Message type ('otp', 'order_confirmation', 'transactional', 'general', 'alert')
     * @return array
     * @throws PingoLineException
     */
    public function sendSms(string $phone, string $message, ?string $type = null): array
    {
        $payload = [
            'phone' => $phone,
            'message' => $message,
        ];

        if ($type) {
            $payload['type'] = $type;
        }

        return $this->post('sms/send', $payload);
    }

    /**
     * Send bulk SMS to multiple recipients.
     *
     * @param array|string $recipients Array of phone numbers or comma-separated string
     * @param string $message Text content of the SMS
     * @param string|null $type Message type
     * @return array
     * @throws PingoLineException
     */
    public function sendBulkSms(array|string $recipients, string $message, ?string $type = null): array
    {
        $payload = [
            'recipients' => $recipients,
            'message' => $message,
        ];

        if ($type) {
            $payload['type'] = $type;
        }

        return $this->post('sms/bulk', $payload);
    }

    /**
     * Get current SMS wallet balance.
     *
     * @return array
     * @throws PingoLineException
     */
    public function getSmsBalance(): array
    {
        return $this->get('sms/balance');
    }

    /**
     * Get SMS delivery logs.
     *
     * @param array $filters
     * @return array
     * @throws PingoLineException
     */
    public function getSmsLogs(array $filters = []): array
    {
        return $this->get('sms/logs', $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Telephony & PBX Click-to-Call
    |--------------------------------------------------------------------------
    */

    /**
     * Originate a two-legged Click-to-Call connecting an agent's extension to a destination number.
     *
     * @param string $extension Agent PBX extension (e.g. '101')
     * @param string $phone Destination phone number
     * @return array
     * @throws PingoLineException
     */
    public function originateCall(string $extension, string $phone): array
    {
        return $this->post('calls/originate', [
            'extension' => $extension,
            'phone' => $phone,
        ]);
    }

    /**
     * Query account call records.
     *
     * @param array $filters
     * @return array
     * @throws PingoLineException
     */
    public function getCalls(array $filters = []): array
    {
        return $this->get('calls', $filters);
    }

    /*
    |--------------------------------------------------------------------------
    | Account & Diagnostics
    |--------------------------------------------------------------------------
    */

    /**
     * Get account voice wallet balance and allocated phone numbers.
     *
     * @return array
     * @throws PingoLineException
     */
    public function getBalance(): array
    {
        return $this->get('balance');
    }

    /**
     * Ping API to check connectivity and token validity.
     *
     * @return array
     * @throws PingoLineException
     */
    public function ping(): array
    {
        return $this->get('ping');
    }

    /*
    |--------------------------------------------------------------------------
    | Webhooks Helper
    |--------------------------------------------------------------------------
    */

    /**
     * Verify HMAC-SHA256 signature for incoming webhooks.
     *
     * @param string $payload
     * @param string $signature
     * @param string|null $secret
     * @return bool
     */
    public function verifyWebhookSignature(string $payload, string $signature, ?string $secret = null): bool
    {
        $secret = $secret ?: $this->webhookSecret;

        if (empty($secret)) {
            return false;
        }

        return WebhookSignature::verify($payload, $signature, $secret);
    }

    /**
     * Parse and verify an incoming HTTP Request from PingoLine Webhooks.
     *
     * @param Request $request
     * @param string|null $secret
     * @return WebhookEvent
     * @throws WebhookVerificationException
     */
    public function constructEventFromRequest(Request $request, ?string $secret = null): WebhookEvent
    {
        $signature = $request->header('X-PingoLine-Signature')
            ?: $request->header('X-XPBX-Signature')
            ?: '';

        $rawBody = $request->getContent();
        $secret = $secret ?: $this->webhookSecret;

        if (! empty($secret)) {
            if (empty($signature) || ! $this->verifyWebhookSignature($rawBody, $signature, $secret)) {
                throw new WebhookVerificationException('Invalid or missing PingoLine webhook signature.', 403);
            }
        }

        $event = $request->header('X-PingoLine-Event')
            ?: $request->header('X-XPBX-Event')
            ?: ($request->input('event') ?? 'order_call.completed');

        $deliveryId = $request->header('X-PingoLine-Delivery')
            ?: $request->header('X-XPBX-Delivery');

        $payload = $request->json()->all();

        return new WebhookEvent($event, $deliveryId, $payload, $rawBody);
    }

    /*
    |--------------------------------------------------------------------------
    | HTTP Transport Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Perform HTTP GET request.
     */
    protected function get(string $endpoint, array $query = []): array
    {
        return $this->sendRequest('GET', $endpoint, ['query' => $query]);
    }

    /**
     * Perform HTTP POST request.
     */
    protected function post(string $endpoint, array $data = []): array
    {
        return $this->sendRequest('POST', $endpoint, ['json' => $data]);
    }

    /**
     * Execute HTTP request with token authentication, headers, and error handling.
     */
    protected function sendRequest(string $method, string $endpoint, array $options = []): array
    {
        $url = $this->apiUrl . '/' . ltrim($endpoint, '/');

        $http = Http::timeout($this->timeout)
            ->acceptJson()
            ->withToken($this->apiKey)
            ->withHeaders([
                'User-Agent' => 'PingoLine-Laravel-SDK/1.0',
                'X-PingoLine-SDK' => 'laravel',
            ]);

        if (! empty($this->retry['times']) && $this->retry['times'] > 0) {
            $http->retry($this->retry['times'], $this->retry['sleep'] ?? 200, throw: false);
        }

        /** @var Response $response */
        $response = match (strtoupper($method)) {
            'GET' => $http->get($url, $options['query'] ?? []),
            'POST' => $http->post($url, $options['json'] ?? []),
            default => $http->send($method, $url, $options),
        };

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $this->handleRequestError($response);

        return [];
    }

    /**
     * Handle non-2xx responses and map them to specialized exceptions.
     *
     * @param Response $response
     * @throws PingoLineException
     */
    protected function handleRequestError(Response $response): void
    {
        $status = $response->status();
        $json = $response->json();
        $error = $json['error'] ?? [];

        $message = is_array($error) ? ($error['message'] ?? 'Unknown PingoLine API error.') : ($error ?: 'HTTP request failed.');
        $errorCode = is_array($error) ? ($error['code'] ?? null) : null;

        if ($status === 401) {
            throw new AuthenticationException($message, 401, $errorCode, $json);
        }

        if ($status === 422) {
            throw new ValidationException($message, 422, $errorCode, $json);
        }

        if ($status === 429) {
            throw new QuotaExceededException($message, 429, $errorCode, $json);
        }

        throw new PingoLineException($message, $status, $errorCode, $json);
    }
}
