<?php

namespace PingoLine\Laravel\Webhooks;

class WebhookSignature
{
    /**
     * Verify whether the HMAC-SHA256 signature matches the raw payload.
     *
     * @param string $payload Raw request body string
     * @param string $signature Signature header (e.g. 'sha256=...' or raw hex)
     * @param string $secret Webhook secret key
     * @return bool
     */
    public static function verify(string $payload, string $signature, string $secret): bool
    {
        if (empty($secret) || empty($signature)) {
            return false;
        }

        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $expected = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
