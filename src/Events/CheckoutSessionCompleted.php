<?php

namespace Asvri\Laravel\Events;

/**
 * A checkout session was paid: the gateway confirmed the full amount. Mark the order paid here.
 * $session is the checkout session (id, status, amount, currency, reference, metadata, payment…).
 */
class CheckoutSessionCompleted
{
    /** @var array<string, mixed> */
    public array $session;

    public function __construct(public WebhookReceived $event)
    {
        $this->session = (array) ($event->data['checkout_session'] ?? []);
    }
}
