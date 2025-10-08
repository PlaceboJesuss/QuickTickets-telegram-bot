<?php

namespace App\Http\Controllers;

use App\Services\TelegramService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, TelegramService $telegramService)
    {
        // Логируем полученные данные для отладки
        $update = $request->all();
        Log::info('Telegram Webhook received:', $update);

        // Проверяем, есть ли обычное сообщение
        if (isset($update['message']['text'])) {
            $chatId = $update['message']['chat']['id'];
            $text = trim($update['message']['text']);
            $username = $update['message']['chat']['username'] ?? null;

            $telegramService->handleMessage($chatId, $username, $text);

        // Или обработка callback_query
        } elseif (isset($update['callback_query']['data'])) {
            $chatId = $update['callback_query']['message']['chat']['id'];
            $text = $update['callback_query']['data'];
            $username = $update['callback_query']['message']['chat']['username'] ?? null;

            $telegramService->handleMessage($chatId, $username, $text);
        }

        // Telegram требует вернуть статус 200
        return response()->json(['status' => 'ok']);
    }
}
