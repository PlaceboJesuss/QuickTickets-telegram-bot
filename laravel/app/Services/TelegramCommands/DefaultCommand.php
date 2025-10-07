<?php

namespace App\Services\TelegramCommands;

use App\Models\Performance;
use App\Models\Place;
use App\Models\TelegramUser;
use App\Models\UserPlace;
use App\Services\QuickTicketsParserService;
use App\Services\QuickTicketsService;
use Exception;
use Illuminate\Support\Facades\Http;
use KubAT\PhpSimple\HtmlDomParser;
use Telegram\Bot\Commands\Command;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\Laravel\Facades\Telegram;

/**
 * Class HelpCommand.
 */
final class DefaultCommand extends Command
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
        $message = $this->argument('message');

        $quickTicketsService = new QuickTicketsService();
        $quickTicketsParserService = new QuickTicketsParserService();

        $telegramUser = TelegramUser::firstOrCreate(
            ['chat_id' => $chatId],
            ['username' => $username]
        );

        $keyboard = Keyboard::make()
            ->setResizeKeyboard(true) // подгоняет по размеру
            ->setOneTimeKeyboard(true) // клавиатура остаётся
            ->row([
                Keyboard::button('➕ Добавить заведение'),
                Keyboard::button('➖ Удалить заведение')
            ]);


        if (str_starts_with($message, "https://quicktickets.ru/")) {
            $placeUrl = $this->normalizeQuickticketsUrl($message);
            if ($placeUrl) {
                $html = $quickTicketsService->getDOMbyUrl($placeUrl);
                $dom = $quickTicketsParserService->parse($html);
                $placeName = $quickTicketsParserService->getPlaceName($dom);

                if ($placeName) {
                    $place = Place::firstOrNew(['url' => $placeUrl]);

                    if (!$place->exists) {
                        $place->name = $placeName;
                        $place->save();

                        $performances = $quickTicketsParserService->getPerformances($dom);
                        foreach ($performances as $performance) {
                            $name = $quickTicketsParserService->getPerformanceName($performance);

                            $sessions = $quickTicketsParserService->getPerformanceSessions($performance);
                            foreach ($sessions as $session) {
                                try {
                                    $soldOut = $quickTicketsParserService->getSessionSoldOut($session);
                                    $timestamp = $quickTicketsParserService->getSessionTimestamp($session);
                                } catch (Exception $e) {
                                    continue;
                                }

                                $dbPerformance = new Performance();
                                $dbPerformance->name = $name;
                                $dbPerformance->place_id = $place->id;
                                $dbPerformance->time = date('Y-m-d H:i:s', $timestamp);
                                $dbPerformance->sold_out = $soldOut;
                                $dbPerformance->save();
                            }
                        }
                    }

                    UserPlace::firstOrCreate(
                        ['place_id' => $place->id],
                        ['chat_id' => $telegramUser->chat_id]
                    );

                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => 'Заведение "' . $placeName . '" добавлено.',
                    ]);

                    Telegram::sendMessage([
                        'chat_id' => $chatId,
                        'text'    => 'Выберите действие:',
                        'reply_markup' => $keyboard,
                    ]);
                    return;
                }
            }
        }

        // Отправляем сообщение с клавиатурой
        Telegram::sendMessage([
            'chat_id' => $chatId,
            'text'    => 'Выберите действие:',
            'reply_markup' => $keyboard
        ]);
    }

    private function normalizeQuickticketsUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (!isset($parts['scheme'], $parts['host'], $parts['path'])) {
            return null; // если ссылка битая - null
        }

        // Берём только первые 2 сегмента пути
        $pathParts = explode('/', trim($parts['path'], '/'));
        $normalizedPath = implode('/', array_slice($pathParts, 0, 2));

        return $parts['scheme'] . '://' . $parts['host'] . '/' . $normalizedPath;
    }
}
