<?php

namespace App\Services;

use App\Services\TelegramCommands\AddPlaceCommand;
use App\Services\TelegramCommands\DefaultCommand;
use App\Services\TelegramCommands\RemovePlaceCommand;
use App\Services\TelegramCommands\RemovePlacesCommand;
use App\Services\TelegramCommands\StartCommand;
use Telegram\Bot\Commands\Command;

class TelegramService
{
    private function getCommandHandler($message): Command
    {
        echo $message;
        if (str_starts_with($message, 'remove_place_')) {
            $id = (int) substr($message, strlen('remove_place_'));

            $handler = new RemovePlaceCommand();
            $handler->setArguments(['place_id' => $id]);
            return $handler;
        }

        return match ($message) {
            "/start" => new StartCommand(),
            "➕ Добавить заведение" => new AddPlaceCommand(),
            "➖ Удалить заведение" => new RemovePlacesCommand(),
            default => new DefaultCommand()
        };
    }

    public function handleMessage(int $chatId, string $username, string $message): void
    {
        $commandHandler = $this->getCommandHandler($message);
        $commandHandler->setArguments([
            "chat_id" => $chatId,
            "username" => $username,
            "message" => $message,
        ]);

        $commandHandler->handle();
    }
}
