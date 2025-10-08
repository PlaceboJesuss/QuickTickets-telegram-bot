<?php

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;

Route::post('/tg_webhook', [TelegramWebhookController::class, 'handle']);