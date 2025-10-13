<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Laravel\Facades\Telegram;

class RedirectToBotController extends Controller
{
    public function redirect(): RedirectResponse
    {
        try {
            $bot = Telegram::getMe();
            $username = $bot->getUsername();

            if (!$username) {
                Log::error('Telegram bot username не найден');
                abort(500); // возвращаем 500 без деталей
            }

            return redirect("https://t.me/{$username}");
        } catch (\Exception $e) {
            Log::error('Ошибка при получении информации о боте: ' . $e->getMessage());
            abort(500); // возвращаем 500 без деталей
        }
    }
}
