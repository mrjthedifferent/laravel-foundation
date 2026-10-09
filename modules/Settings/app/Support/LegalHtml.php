<?php

declare(strict_types=1);

namespace Modules\Settings\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans the privacy policy / terms HTML (written in the admin's rich text editor) before it is
 * shown on a public page. Only formatting tags survive; scripts, styles, frames, forms and every
 * attribute except a safe link `href` (http, https, mailto, tel, or a relative/anchor link) and
 * Quill's `ql-*` classes are removed. Text is kept.
 */
final class LegalHtml
{
    private const array ALLOWED = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'hr', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup',
        'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'a', 'span', 'div', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
    ];

    /** Elements dropped together with everything inside them. */
    private const array DROP = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'template', 'noscript', 'link', 'meta', 'base'];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="legal-root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('legal-root');
        if ($root === null) {
            return '';
        }

        self::cleanChildren($root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $document->saveHTML($child);
        }

        return $out;
    }

    private static function cleanChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::DROP, true)) {
                $node->removeChild($child);

                continue;
            }

            self::cleanChildren($child);

            if (! in_array($tag, self::ALLOWED, true)) {
                // Unknown wrapper: keep what it holds, lose the element.
                while ($child->firstChild !== null) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);
            $value = trim($attribute->value);

            $keep = match (true) {
                $name === 'href' && $tag === 'a' => self::safeHref($value),
                $name === 'class' => preg_match('/^(ql-[a-z0-9-]+\s*)+$/', $value) === 1,
                default => false,
            };

            if (! $keep) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href') && preg_match('#^https?://#i', $element->getAttribute('href')) === 1) {
            $element->setAttribute('rel', 'noopener noreferrer nofollow');
            $element->setAttribute('target', '_blank');
        }
    }

    private static function safeHref(string $href): bool
    {
        // Control characters and whitespace inside a scheme ("java\tscript:") are a classic bypass.
        $compact = strtolower((string) preg_replace('/[\x00-\x20]+/', '', $href));

        return preg_match('#^(https?:|mailto:|tel:|/|\#)#', $compact) === 1
            || preg_match('#^[a-z][a-z0-9+.-]*:#', $compact) === 0;
    }
}
