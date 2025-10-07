<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class QuickTicketsService
{
    public function getDOMbyUrl(string $url): string
    {
        $response = Http::get($url);

        if (!$response->ok()) {
            throw new Exception("Не удалось получить страницу $url, HTTP статус: " . $response->status());
        }

        return $response->body();
    }
}
