<?php

namespace App\Services\QuickTicketsParsers;

use Carbon\Carbon;
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

        if (preg_match('/(\d{1,2}) (\p{L}+) (\d{2}:\d{2})/u', $dateString, $matches)) {
            $day = (int)$matches[1];
            $monthName = $matches[2];
            $time = $matches[3];

            $month = $months[$monthName] ?? null;
            if (!$month) {
                throw new \Exception("Неизвестный месяц: $monthName");
            }

            // Текущий год
            $year = (int)date('Y');

            // Создаём объект Carbon
            $date = Carbon::createFromFormat('Y-n-d H:i', "$year-$month-$day $time");

            // Если дата уже прошла — прибавляем год
            if ($date->lt(now())) {
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
