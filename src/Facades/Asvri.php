<?php

namespace Asvri\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Asvri\Client client()
 * @method static \Asvri\Service\CheckoutSessions checkoutSessions()
 * @method static \Asvri\Service\Refunds refunds()
 * @method static \Asvri\Service\WebhookEndpoints webhookEndpoints()
 * @method static string mode()
 * @method static \Illuminate\Http\RedirectResponse checkout(array $params, ?string $idempotencyKey = null)
 * @method static bool isPaid(string $sessionId)
 *
 * @see \Asvri\Laravel\AsvriManager
 */
class Asvri extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'asvri';
    }
}
