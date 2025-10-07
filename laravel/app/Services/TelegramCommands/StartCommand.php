<?php

namespace App\Services\TelegramCommands;

use App\Models\TelegramUser;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class HelpCommand.
 */
final class StartCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = '/start';

    /**
     * @var array Command Aliases
     */
    protected array $aliases = ['listcommands'];

    /**
     * @var string Command Description
     */
    protected string $description = 'Get a list of available commands';

    /**
     * {@inheritdoc}
     */
    public function handle(): void
    {
        $chatId = $this->argument('chat_id');
        $username = $this->argument('username');

        TelegramUser::firstOrCreate(
            ['chat_id' => $chatId],
            ['username' => $username]
        );

        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true) // подгоняет по размеру
            ->setoneTimeKeyboard(true) // клавиатура остаётся
            ->setSelective(true)
            ->row([
                Keyboard::button('➕ Добавить заведение'),
                Keyboard::button('➖ Удалить заведение')
            ]);

        // Отправляем сообщение с клавиатурой
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => 'Выберите действие:',
            'reply_markup' => $keyboard
        ]);
    }
}
