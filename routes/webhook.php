<?php

use Asvri\Laravel\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::post(config('asvri.webhook.path'), WebhookController::class)
    ->middleware(config('asvri.webhook.middleware', []))
    ->name('asvri.webhook');
