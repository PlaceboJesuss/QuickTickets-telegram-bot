<?php

namespace App\Clients;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Telegram\Bot\HttpClients\HttpClientInterface;

class TelegramProxyClient implements HttpClientInterface
{
    protected Client $client;
    protected int $timeout = 20;
    protected int $connectTimeout = 10;

    public function __construct()
    {
        $options = [
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ];

        if ($proxy = env('TELEGRAM_PROXY')) {
            Log::info("Используем прокси");
            $options['proxy'] = $proxy;
        }

        $this->client = new Client($options);
    }

    public function getTimeOut(): int
    {
        return $this->timeout;
    }

    public function setTimeOut(int $timeOut): static
    {
        $this->timeout = $timeOut;
        return $this;
    }

    public function getConnectTimeOut(): int
    {
        return $this->connectTimeout;
    }

    public function setConnectTimeOut(int $connectTimeOut): static
    {
        $this->connectTimeout = $connectTimeOut;
        return $this;
    }

    public function send(
        string $url,
        string $method,
        array $headers = [],
        array $options = [],
        bool $isAsyncRequest = false
    ): ResponseInterface|PromiseInterface|null {

        $guzzleOptions = [
            'headers' => $headers,
            'timeout' => $this->timeout,
            'connect_timeout' => $this->connectTimeout,
        ];

        if ($proxy = env('TELEGRAM_PROXY')) {
            Log::info("Используем прокси");
            $guzzleOptions['proxy'] = $proxy;
        }

        // если есть дополнительные опции (body, query и т.д.)
        $guzzleOptions = array_merge($guzzleOptions, $options);

        if ($isAsyncRequest) {
            return $this->client->requestAsync($method, $url, $guzzleOptions);
        }

        return $this->client->request($method, $url, $guzzleOptions);
    }
}
