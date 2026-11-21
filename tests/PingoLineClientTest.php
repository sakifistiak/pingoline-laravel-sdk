<?php

namespace PingoLine\Laravel\Tests;

use Illuminate\Support\Facades\Http;
use PingoLine\Laravel\Exceptions\AuthenticationException;
use PingoLine\Laravel\Exceptions\ValidationException;
use PingoLine\Laravel\Facades\PingoLine;
use PingoLine\Laravel\PingoLineClient;

class PingoLineClientTest extends TestCase
{
    public function test_facade_resolves_client(): void
    {
        $client = app('pingoline');
        $this->assertInstanceOf(PingoLineClient::class, $client);
    }

    public function test_create_order_call_success(): void
    {
        Http::fake([
            'https://pingoline.com/api/v1/order-calls' => Http::response([
                'data' => [
                    'id' => 'oc_test123',
                    'order_id' => '1001',
                    'phone' => '01700000000',
                    'status' => 'queued',
                ],
            ], 201),
        ]);

        $response = PingoLine::createOrderCall([
            'order_id' => '1001',
            'phone' => '01700000000',
            'amount' => 1250.00,
        ]);

        $this->assertEquals('oc_test123', $response['data']['id']);
        $this->assertEquals('queued', $response['data']['status']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://pingoline.com/api/v1/order-calls'
                && $request->hasHeader('Authorization', 'Bearer test_token_12345')
                && $request['order_id'] === '1001';
        });
    }

    public function test_send_sms_success(): void
    {
        Http::fake([
            'https://pingoline.com/api/v1/sms/send' => Http::response([
                'success' => true,
                'status' => 'success',
                'data' => [
                    'status' => 'sent',
                    'recipient' => '01700000000',
                    'message_id' => 'msg_xyz',
                ],
            ], 200),
        ]);

        $response = PingoLine::sendSms('01700000000', 'Your OTP is 1234');

        $this->assertTrue($response['success']);
        $this->assertEquals('sent', $response['data']['status']);
    }

    public function test_balance_check(): void
    {
        Http::fake([
            'https://pingoline.com/api/v1/balance' => Http::response([
                'data' => [
                    'account' => 'Demo Business',
                    'wallet' => [
                        'balance' => '500.00',
                        'currency' => 'BDT',
                    ],
                ],
            ], 200),
        ]);

        $balance = PingoLine::getBalance();
        $this->assertEquals('500.00', $balance['data']['wallet']['balance']);
    }

    public function test_authentication_error_throws_exception(): void
    {
        $this->expectException(AuthenticationException::class);

        Http::fake([
            'https://pingoline.com/api/v1/ping' => Http::response([
                'error' => [
                    'code' => 'unauthenticated',
                    'message' => 'Invalid or expired API token.',
                ],
            ], 401),
        ]);

        PingoLine::ping();
    }

    public function test_validation_error_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        Http::fake([
            'https://pingoline.com/api/v1/order-calls' => Http::response([
                'error' => [
                    'code' => 'invalid_request',
                    'message' => 'Phone field is required.',
                ],
            ], 422),
        ]);

        PingoLine::createOrderCall(['order_id' => '1001']);
    }
}
