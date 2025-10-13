<?php

namespace App\Services\QuickTicketsParsers;

use Exception;
use KubAT\PhpSimple\HtmlDomParser;
use simple_html_dom\simple_html_dom_node as SimpleHtmlDomNode;

class DomParser
{
    public static function parse(string $html): SimpleHtmlDomNode
    {
        $dom = HtmlDomParser::str_get_html($html);

        if (!$dom) {
            throw new Exception("Не удалось распарсить HTML");
        }

        return $dom->find('body', 0);
    }
}
