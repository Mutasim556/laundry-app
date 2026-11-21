<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class CategoryDescription
{
    public static function safeHtml(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $render = function (DOMNode $node) use (&$render): string {
            if ($node->nodeType === XML_TEXT_NODE) {
                return e($node->nodeValue);
            }
            if (! $node instanceof DOMElement) {
                return '';
            }
            $tag = strtolower($node->tagName);
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'svg', 'math'], true)) {
                return '';
            }
            $children = '';
            foreach ($node->childNodes as $child) {
                $children .= $render($child);
            }
            if (! in_array($tag, ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li'], true)) {
                return $children;
            }
            return $tag === 'br' ? '<br>' : '<'.$tag.'>'.$children.'</'.$tag.'>';
        };

        return $document->documentElement ? $render($document->documentElement) : '';
    }
}
