<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TelegramUser;
use App\Models\Place;
use App\Services\TelegramService;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Keyboard\Keyboard;

class TelegramPoll extends Command
{
    protected $signature = 'app:telegram:poll';
    protected $description = 'Poll Telegram for updates';

    public function handle(TelegramService $telegramService)
    {
        $offset = cache('telegram_offset', 0);

        $updates = Telegram::getUpdates([
            'offset' => $offset,
            'timeout' => 30, // long polling
            'allowed_updates' => ['message', 'callback_query'],
        ]);

        foreach ($updates as $update) {
            // обновляем offset
            cache(['telegram_offset' => $update->updateId + 1]);

            if ($update->message && isset($update->message->text)) {
                $chatId = $update->message->chat->id;
                $text = $update?->callback_query?->data ?? trim($update->message->text);
                $username = $update->message->chat->username ?? null;

                $telegramService->handleMessage($chatId, $username, $text);
            } elseif ($update?->callback_query?->data){
                $chatId = $update->callback_query->message->chat->id;
                $text = $update->callback_query->data;
                $username = $update->callback_query->message->chat->username ?? null;

                $telegramService->handleMessage($chatId, $username, $text);
            }
        }
    }
}
