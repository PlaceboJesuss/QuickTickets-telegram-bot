<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use KubAT\PhpSimple\HtmlDomParser;
use simple_html_dom\simple_html_dom_node;

class QuickTicketsParserService
{
    public function parse(string $html): simple_html_dom_node
    {
        $dom = HtmlDomParser::str_get_html($html);

        if (!$dom) {
            throw new Exception("Не удалось распарсить HTML");
        }

        return $dom->find('body', 0);
    }

    public function getPlaceName(simple_html_dom_node $dom): ?string{
        return $dom->find('#organisation .head .info a.title h2', 0)?->innertext;
    }

    public function getPerformances(simple_html_dom_node $dom): array
    {
        return $dom->find('.body #elems-list .elem');
    }

    public function getPerformanceName(simple_html_dom_node $performance): ?string
    {
        return $performance->find('.c h3 a .underline', 0)?->innertext;
    }

    public function getPerformanceImage(simple_html_dom_node $performance): ?string
    {
        return $performance->find('a img.polaroid', 0)?->src;
    }

    public function getPerformanceSessions(simple_html_dom_node $performance): array
    {
        return $performance->find('.c .sessions .session-column');
    }

    public function getSessionSoldOut(simple_html_dom_node $session): bool
    {
        return $session->find('span', 1)?->innertext == '(мест нет)';
    }

    public function getSessionTimestamp(simple_html_dom_node $session): int
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

    public function getSessionLink(simple_html_dom_node $session): ?string
    {
        return $session->find('a.notUnderline', 0)?->href;
    }
}
