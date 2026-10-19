<?php

namespace Asvri\Laravel\Events;

/**
 * A checkout session expired unpaid (its time ran out, or you expired it).
 * $session is the checkout session (id, status, amount, currency, reference, metadata, payment…).
 */
class CheckoutSessionExpired
{
    /** @var array<string, mixed> */
    public array $session;

    public function __construct(public WebhookReceived $event)
    {
        $this->session = (array) ($event->data['checkout_session'] ?? []);
    }
}
