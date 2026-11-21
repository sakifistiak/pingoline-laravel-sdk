<?php

namespace PingoLine\Laravel\Tests;

use Illuminate\Http\Request;
use PingoLine\Laravel\Exceptions\WebhookVerificationException;
use PingoLine\Laravel\Facades\PingoLine;
use PingoLine\Laravel\Webhooks\WebhookSignature;

class WebhookTest extends TestCase
{
    public function test_signature_verification_succeeds_with_correct_secret(): void
    {
        $secret = 'whsec_secret_123';
        $payload = json_encode(['order_id' => '1001', 'status' => 'confirmed']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $this->assertTrue(WebhookSignature::verify($payload, $signature, $secret));
        $this->assertTrue(PingoLine::verifyWebhookSignature($payload, $signature, $secret));
    }

    public function test_signature_verification_fails_with_tampered_payload(): void
    {
        $secret = 'whsec_secret_123';
        $payload = json_encode(['order_id' => '1001', 'status' => 'confirmed']);
        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $tamperedPayload = json_encode(['order_id' => '1001', 'status' => 'cancelled']);

        $this->assertFalse(WebhookSignature::verify($tamperedPayload, $signature, $secret));
    }

    public function test_construct_event_from_request(): void
    {
        $secret = 'whsec_test_secret_abc123';
        $data = [
            'order_id' => '9988',
            'phone' => '01800000000',
            'status' => 'confirmed',
            'dtmf_result' => 'confirmed',
            'duration_seconds' => 14,
        ];
        $body = json_encode($data);
        $signature = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $request = Request::create(
            '/api/webhooks/pingoline',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_PINGOLINE_EVENT' => 'order_call.completed',
                'HTTP_X_PINGOLINE_SIGNATURE' => $signature,
                'HTTP_X_PINGOLINE_DELIVERY' => 'del_12345',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        $event = PingoLine::constructEventFromRequest($request);

        $this->assertEquals('order_call.completed', $event->event);
        $this->assertEquals('del_12345', $event->deliveryId);
        $this->assertEquals('9988', $event->getOrderId());
        $this->assertEquals('01800000000', $event->getPhone());
        $this->assertTrue($event->isConfirmed());
        $this->assertFalse($event->isCancelled());
        $this->assertEquals(14, $event->getDuration());
    }

    public function test_construct_event_throws_on_invalid_signature(): void
    {
        $this->expectException(WebhookVerificationException::class);

        $body = json_encode(['order_id' => '9988']);
        $request = Request::create(
            '/api/webhooks/pingoline',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_X_PINGOLINE_EVENT' => 'order_call.completed',
                'HTTP_X_PINGOLINE_SIGNATURE' => 'sha256=invalid_signature_hash',
                'CONTENT_TYPE' => 'application/json',
            ],
            $body
        );

        PingoLine::constructEventFromRequest($request);
    }
}
