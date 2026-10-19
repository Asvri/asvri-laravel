<?php

namespace Asvri\Laravel;

use Asvri\Client;
use Asvri\Service\CheckoutSessions;
use Asvri\Service\Refunds;
use Asvri\Service\WebhookEndpoints;

/**
 * What the Asvri facade calls: the PHP SDK's services, plus helpers for the usual Laravel checkout.
 *
 *   return Asvri::checkout(['amount' => 25, 'currency' => 'USD', 'reference' => $order->id], "order-{$order->id}");
 *   $session = Asvri::checkoutSessions()->retrieve($request->query('session_id'));
 */
class AsvriManager
{
    /** @var \Closure(): Client */
    private $resolve;

    private ?Client $client = null;

    public function __construct(\Closure $resolve)
    {
        $this->resolve = $resolve;
    }

    /** The PHP SDK client (asvri/asvri-php), for anything not wrapped here. */
    public function client(): Client
    {
        return $this->client ??= ($this->resolve)();
    }

    public function checkoutSessions(): CheckoutSessions
    {
        return $this->client()->checkoutSessions;
    }

    public function refunds(): Refunds
    {
        return $this->client()->refunds;
    }

    public function webhookEndpoints(): WebhookEndpoints
    {
        return $this->client()->webhookEndpoints;
    }

    /** "test" or "live", from the key. */
    public function mode(): string
    {
        return $this->client()->mode();
    }

    /**
     * Create a checkout session and send the customer to it (a 303 redirect). success_url and cancel_url default to
     * the current app's url('/') when left out.
     *
     * @param array<string, mixed> $params as for checkoutSessions()->create()
     */
    public function checkout(array $params, ?string $idempotencyKey = null): \Illuminate\Http\RedirectResponse
    {
        $params += ['success_url' => url('/'), 'cancel_url' => url('/')];
        $session = $this->checkoutSessions()->create($params, $idempotencyKey);
        return redirect()->away($session['url'], 303);
    }

    /** True when ASVRI says the session is paid: the check to make when the customer comes back. */
    public function isPaid(string $sessionId): bool
    {
        return ($this->checkoutSessions()->retrieve($sessionId)['status'] ?? null) === 'paid';
    }
}
