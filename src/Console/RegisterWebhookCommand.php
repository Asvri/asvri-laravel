<?php

namespace Asvri\Laravel\Console;

use Asvri\Exception\AsvriException;
use Asvri\Laravel\AsvriManager;
use Illuminate\Console\Command;

/**
 * Registers this app's webhook route with ASVRI and prints its signing secret for .env. Safe to run again: the same
 * URL returns the existing endpoint and secret.
 */
class RegisterWebhookCommand extends Command
{
    protected $signature = 'asvri:webhook
        {url? : The public URL ASVRI should post to (default: this app\'s webhook route)}
        {--events=* : Only these event types (default: all)}';

    protected $description = 'Register your ASVRI webhook endpoint and show its signing secret';

    public function handle(AsvriManager $asvri): int
    {
        $url = (string) ($this->argument('url') ?: (config('asvri.webhook.path') ? route('asvri.webhook') : ''));
        if ($url === '') {
            $this->error('No URL: pass one, or set asvri.webhook.path so the package registers its route.');
            return self::FAILURE;
        }
        if (!str_starts_with($url, 'https://')) {
            $this->warn("$url is not HTTPS. ASVRI accepts it only for a local ASVRI; use your public HTTPS address in production.");
        }

        $params = ['url' => $url, 'description' => config('app.name') . ' (Laravel)'];
        if ($events = $this->option('events')) {
            $params['events'] = array_values($events);
        }
        try {
            $endpoint = $asvri->webhookEndpoints()->create($params);
        } catch (AsvriException $e) {
            $this->error('ASVRI refused: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info(($endpoint['created'] ?? true) ? 'Registered: ' . $endpoint['id'] : 'Already registered: ' . $endpoint['id']);
        $this->line('  URL     ' . $endpoint['url']);
        $this->line('  Events  ' . implode(', ', $endpoint['events'] ?? []));
        $this->newLine();
        $this->line('Add this to .env (keep it secret):');
        $this->line('ASVRI_WEBHOOK_SECRET=' . ($endpoint['secret'] ?? '(not returned: roll the secret in ASVRI → Settings → Webhooks)'));
        return self::SUCCESS;
    }
}
