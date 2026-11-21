<?php

namespace PingoLine\Laravel\Webhooks;

class WebhookEvent
{
    public function __construct(
        public readonly string $event,
        public readonly ?string $deliveryId,
        public readonly array $payload,
        public readonly ?string $rawBody = null
    ) {
    }

    /**
     * Check if event is order call completed.
     */
    public function isOrderCallCompleted(): bool
    {
        return $this->event === 'order_call.completed';
    }

    /**
     * Check if customer confirmed the order (pressed 1).
     */
    public function isConfirmed(): bool
    {
        return ($this->payload['status'] ?? null) === 'confirmed'
            || ($this->payload['dtmf_result'] ?? null) === 'confirmed';
    }

    /**
     * Check if customer cancelled the order (pressed 2).
     */
    public function isCancelled(): bool
    {
        return ($this->payload['status'] ?? null) === 'cancelled'
            || ($this->payload['dtmf_result'] ?? null) === 'cancelled';
    }

    /**
     * Get order ID.
     */
    public function getOrderId(): ?string
    {
        return $this->payload['order_id'] ?? null;
    }

    /**
     * Get customer phone number.
     */
    public function getPhone(): ?string
    {
        return $this->payload['phone'] ?? null;
    }

    /**
     * Get call status (confirmed, cancelled, no_answer, failed, etc.).
     */
    public function getStatus(): ?string
    {
        return $this->payload['status'] ?? null;
    }

    /**
     * Get DTMF pressed digit / result.
     */
    public function getDtmfResult(): ?string
    {
        return $this->payload['dtmf_result'] ?? null;
    }

    /**
     * Get call duration in seconds.
     */
    public function getDuration(): int
    {
        return (int) ($this->payload['duration_seconds'] ?? $this->payload['billsec'] ?? 0);
    }

    /**
     * Return entire payload as array.
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
