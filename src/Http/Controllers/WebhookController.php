<?php

namespace Asvri\Laravel\Http\Controllers;

use Asvri\Exception\SignatureVerificationException;
use Asvri\Laravel\Events\CheckoutSessionCompleted;
use Asvri\Laravel\Events\CheckoutSessionExpired;
use Asvri\Laravel\Events\WebhookReceived;
use Asvri\Webhook;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * POST /asvri/webhook: checks the Asvri-Signature against the raw body, then fires WebhookReceived (and the specific
 * checkout events) once per event id. Answers 400 to anything that fails the check, so ASVRI's delivery log shows it.
 */
class WebhookController
{
    /** The specific events fired alongside WebhookReceived. */
    public const EVENTS = [
        'checkout.session.completed' => CheckoutSessionCompleted::class,
        'checkout.session.expired' => CheckoutSessionExpired::class,
    ];

    public function __invoke(Request $request, Dispatcher $events, Cache $cache): Response
    {
        $secret = (string) config('asvri.webhook.secret');
        if ($secret === '') {
            report(new \RuntimeException('ASVRI webhook received but ASVRI_WEBHOOK_SECRET is not set (php artisan asvri:webhook prints it).'));
            return new Response('Webhook secret not configured.', 500);
        }

        try {
            $payload = Webhook::constructEvent($request->getContent(), (string) $request->header('Asvri-Signature', ''), $secret, (int) config('asvri.webhook.tolerance', 300));
        } catch (SignatureVerificationException $e) {
            return new Response($e->getMessage(), 400);
        }

        // a retry of an event already handled: acknowledge it, don't handle it twice
        $ttl = (int) config('asvri.webhook.dedupe_for', 86400);
        $key = 'asvri:webhook:' . $payload['id'];
        if ($ttl > 0 && !$cache->add($key, time(), $ttl)) {
            return new Response('Already handled.', 200);
        }

        try {
            $received = new WebhookReceived($payload);
            $events->dispatch($received);
            if (isset(self::EVENTS[$received->type])) {
                $class = self::EVENTS[$received->type];
                $events->dispatch(new $class($received));
            }
        } catch (\Throwable $e) {
            // a listener failed: forget the id so ASVRI's retry gets another go, and answer 500 so it does retry
            if ($ttl > 0) {
                $cache->forget($key);
            }
            report($e);
            return new Response('A listener failed; ASVRI will retry.', 500);
        }

        return new Response('ok', 200);
    }
}
