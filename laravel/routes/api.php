<?php

// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Middleware\VerifyTelegramSecret;

Route::post('/tg_webhook', [TelegramWebhookController::class, 'handle'])->middleware(VerifyTelegramSecret::class);