<?php

namespace Asvri\Laravel\Events;

/**
 * Every verified event ASVRI sends, once per event id. Listen for this to handle any type:
 *
 *   Event::listen(WebhookReceived::class, function (WebhookReceived $e) {
 *       if ($e->type === 'payment.refunded') { … $e->data['payment'] … }
 *   });
 *
 * The common checkout events also have their own classes (CheckoutSessionCompleted, CheckoutSessionExpired).
 */
class WebhookReceived
{
    public string $id;

    public string $type;

    public bool $test;

    public int $created;

    /** @var array<string, mixed> one object, under the key for its kind (checkout_session, payment, invoice…) */
    public array $data;

    /** @param array{id: string, type: string, created: int, test: bool, data: array<string, mixed>} $payload */
    public function __construct(public array $payload)
    {
        $this->id = (string) $payload['id'];
        $this->type = (string) $payload['type'];
        $this->test = (bool) ($payload['test'] ?? false);
        $this->created = (int) ($payload['created'] ?? 0);
        $this->data = (array) $payload['data'];
    }
}
