<?php

namespace App\Services\TelegramCommands;

use App\Models\TelegramUser;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class HelpCommand.
 */
final class RemovePlaceCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = '/remove_place';

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
        $placeId = $this->argument('place_id');

        $user = TelegramUser::firstOrCreate(
            ['chat_id' => $chatId],
            ['username' => $username]
        );

        $place = $user->places()->where('places.id', $placeId)->first();

        if ($place) {
            $place->delete();
        }

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
