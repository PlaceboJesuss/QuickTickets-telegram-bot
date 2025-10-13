<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyTelegramSecret
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $secret = config('app.telegram_secret', env('TELEGRAM_BOT_SECRET_TOKEN'));
        $incoming = $request->header('X-Telegram-Bot-Api-Secret-Token');

        if (!$incoming || $incoming !== $secret) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
