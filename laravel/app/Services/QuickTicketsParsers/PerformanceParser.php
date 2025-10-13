<?php

namespace App\Services\QuickTicketsParsers;

use simple_html_dom\simple_html_dom_node as SimpleHtmlDomNode;

class PerformanceParser
{
    public static function getPerformances(SimpleHtmlDomNode $dom): array
    {
        return $dom->find('.body #elems-list .elem');
    }

    public static function getPerformanceName(SimpleHtmlDomNode $performance): ?string
    {
        return $performance->find('.c h3 a .underline', 0)?->innertext;
    }

    public static function getPerformanceImage(SimpleHtmlDomNode $performance): ?string
    {
        return $performance->find('a img.polaroid', 0)?->src;
    }
}
