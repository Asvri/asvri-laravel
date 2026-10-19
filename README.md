# ASVRI for Laravel

Take payments in Laravel through every gateway you've connected to ASVRI (cards, bank transfer, USSD, mobile money). One redirect to ASVRI's checkout, a verified webhook route, and Laravel events for your listeners.

Laravel 12 or 13, PHP 8.2+. Built on the [ASVRI PHP SDK](https://github.com/asvri/asvri-php).

## Install

```bash
composer require asvri/asvri-laravel
```

Until the packages are listed on Packagist, add both repositories to your `composer.json` first:

```json
"repositories": [
    { "type": "vcs", "url": "https://github.com/asvri/asvri-php" },
    { "type": "vcs", "url": "https://github.com/asvri/asvri-laravel" }
]
```

Add your secret key from ASVRI → Settings → API access to `.env` (`sk_test_…` while you build):

```dotenv
ASVRI_SECRET_KEY=sk_test_...
```

Register the webhook route with ASVRI. It prints the signing secret to add to `.env`:

```bash
php artisan asvri:webhook https://yourshop.com/asvri/webhook
```

```dotenv
ASVRI_WEBHOOK_SECRET=whsec_...
```

Optionally publish the config: `php artisan vendor:publish --tag=asvri-config`.

## Take a payment

```php
use Asvri\Laravel\Facades\Asvri;

Route::post('/orders/{order}/pay', function (Order $order) {
    return Asvri::checkout([
        'amount' => $order->total,          // major units; or 'line_items' => [['name' => …, 'quantity' => 1, 'amount' => …]]
        'currency' => 'USD',
        'reference' => (string) $order->id,
        'customer' => ['name' => $order->name, 'email' => $order->email],
        'success_url' => route('orders.thanks', $order),   // ASVRI adds ?session_id=cs_…
        'cancel_url' => route('cart'),
        'metadata' => ['order_id' => (string) $order->id],
    ], "order-{$order->id}");                // idempotency key: one session per order
});
```

`Asvri::checkout()` creates the session and returns a 303 redirect to it. On the way back, **ask ASVRI**. Never trust the redirect alone:

```php
if (Asvri::isPaid(request('session_id'))) { … }
```

## Handle the webhook

The package's route (`POST /asvri/webhook`) checks the signature, ignores repeat deliveries of the same event, and fires Laravel events:

```php
use Asvri\Laravel\Events\CheckoutSessionCompleted;
use Asvri\Laravel\Events\WebhookReceived;

Event::listen(function (CheckoutSessionCompleted $event) {
    $order = Order::find($event->session['metadata']['order_id']);
    $order->markPaid($event->session['payment']['gateway_reference']);
});

Event::listen(function (WebhookReceived $event) {
    // every event type: $event->type, $event->data, $event->id, $event->test
});
```

| Event class | When |
| --- | --- |
| `CheckoutSessionCompleted` | a session was paid (`$event->session`) |
| `CheckoutSessionExpired` | a session expired unpaid |
| `WebhookReceived` | every verified event, any type |

- **Bad signature, stale or replayed event:** the route answers 400.
- **A listener throws:** it answers 500, ASVRI retries later, and that retry is handled again. Make listeners safe to run twice; `WebhookReceived` listeners may see the retry.
- **Test mode:** `$event->test` is `true` for test-mode events.

The route has no session or CSRF middleware; the signature is the proof. To use your own route instead, set `asvri.webhook.path` to `null` and call `\Asvri\Webhook::constructEvent()` yourself.

## Everything else

The facade exposes the PHP SDK's services:

```php
Asvri::checkoutSessions()->retrieve($id);
Asvri::checkoutSessions()->all(['status' => 'paid', 'limit' => 20]);
Asvri::checkoutSessions()->expire($id);
Asvri::refunds()->create(['session' => $id, 'amount' => 1000, 'reason' => 'Damaged item']);
Asvri::webhookEndpoints()->all();
Asvri::client();   // the \Asvri\Client itself
```

Errors are the SDK's exceptions (`\Asvri\Exception\InvalidRequestException`, `AuthenticationException`, `ApiConnectionException`…). See the [PHP SDK README](https://github.com/asvri/asvri-php).

## Configuration

| `.env` | Default | |
| --- | --- | --- |
| `ASVRI_SECRET_KEY` | | your `sk_test_` / `sk_live_` key |
| `ASVRI_WEBHOOK_SECRET` | | the endpoint's `whsec_` secret |
| `ASVRI_WEBHOOK_PATH` | `asvri/webhook` | the route's path |
| `ASVRI_WEBHOOK_TOLERANCE` | `300` | seconds an event may be old |
| `ASVRI_WEBHOOK_DEDUPE_FOR` | `86400` | seconds to remember handled event ids (cache); `0` turns it off |
| `ASVRI_TIMEOUT` / `ASVRI_MAX_RETRIES` | `30` / `2` | |

## Links

- API reference: https://www.asvri.com/developers/docs
- API playground: https://www.asvri.com/api-playground

MIT licence.
