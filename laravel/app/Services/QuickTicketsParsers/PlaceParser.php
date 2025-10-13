<?php

namespace App\Services\QuickTicketsParsers;

use simple_html_dom\simple_html_dom_node as SimpleHtmlDomNode;

class PlaceParser
{
    public static function getPlaceName(SimpleHtmlDomNode $dom): ?string{
        return $dom->find('#organisation .head .info a.title h2', 0)?->innertext;
    }
}
