<?php

namespace App\Services\TelegramCommands;

use App\Models\TelegramUser;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class HelpCommand.
 */
final class RemovePlacesCommand extends Command
{
    /**
     * @var string Command Name
     */
    protected string $name = '/remove_places';

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

        $user = TelegramUser::firstOrCreate(
            ['chat_id' => $chatId],
            ['username' => $username]
        );

        $places = $user->places;

        if ($places->isEmpty()) {
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
                'text'    => 'У вас нет добавленных заведений. Выберите действие:',
                'reply_markup' => $keyboard
            ]);
            return;
        }

        $keyboard = Keyboard::make()->inline();
        foreach($places->toArray() as $place){
            $keyboard->row([Keyboard::inlineButton(["text" => $place['name'], "callback_data" => "remove_place_" . $place["id"]])]);
        }

        // Отправляем сообщение с клавиатурой
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => 'Выберите заведение которое больше не хотите отслеживать',
            'reply_markup' => $keyboard
        ]);
    }
}
