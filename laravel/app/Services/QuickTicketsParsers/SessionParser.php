<?php

namespace App\Services\QuickTicketsParsers;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Exception;
use simple_html_dom\simple_html_dom_node as SimpleHtmlDomNode;


class SessionParser
{
    public static function getPerformanceSessions(SimpleHtmlDomNode $performance): array
    {
        return $performance->find('.c .sessions .session-column');
    }

    public static function getSessionSoldOut(SimpleHtmlDomNode $session): bool
    {
        return $session->find('span', 1)?->innertext == '(мест нет)';
    }

    public static function getSessionTimestamp(SimpleHtmlDomNode $session): int
    {
        $dateString = $session->find('a.notUnderline .underline', 0)?->innertext;

        if (!$dateString) {
            throw new \Exception("Не удалось распарсить дату: $session");
        }

        $months = [
            'января'   => 1,
            'февраля'  => 2,
            'марта'    => 3,
            'апреля'   => 4,
            'мая'      => 5,
            'июня'     => 6,
            'июля'     => 7,
            'августа'  => 8,
            'сентября' => 9,
            'октября'  => 10,
            'ноября'   => 11,
            'декабря'  => 12,
        ];

        $tz = new CarbonTimeZone('Europe/Moscow');
        $now = Carbon::now($tz);

        if (preg_match(
            '/(\d{1,2})\s+(\p{L}+)(?:\s+(\d{4}))?\s+(\d{2}:\d{2})/u',
            $dateString,
            $matches
        )) {
            $day       = (int)$matches[1];
            $monthName = $matches[2];
            $year      = !empty($matches[3]) ? (int)$matches[3] : $now->year;
            $time      = $matches[4];

            $month = $months[$monthName] ?? null;
            if (!$month) {
                throw new \Exception("Неизвестный месяц: $monthName");
            }

            $date = Carbon::createFromFormat(
                'Y-n-j H:i',
                "$year-$month-$day $time",
                $tz
            );

            /**
             * Если год не указан явно и дата уже прошла
             * → переносим на следующий год
             */
            if (!isset($matches[3]) && $date->lt($now)) {
                $date->addYear();
            }

            return $date->timestamp;
        }

        throw new \Exception("Не удалось распарсить дату: $dateString");
    }

    public static function getSessionLink(SimpleHtmlDomNode $session): ?string
    {
        return $session->find('a.notUnderline', 0)?->href;
    }
}
