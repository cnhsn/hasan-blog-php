<?php

declare(strict_types=1);

namespace App\Security;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

/**
 * Allowlist HTML sanitizer for rich text saved from the admin editor
 * (docs/SECURITY.md "Sanitize allowed rich text renderers and link
 * schemes"). No Composer dependency (e.g. HTMLPurifier) — this project
 * ships with no build step, so sanitization is a small self-contained
 * allowlist walk over DOMDocument rather than a vendored library.
 *
 * Anything not explicitly allowed is dropped; text content of dropped
 * elements is kept (so pasting from Word/Google Docs degrades to plain
 * text instead of vanishing), except for tags whose content is never
 * safe to keep (script, style, iframe, object, embed).
 */
final class Sanitizer
{
    /** @var array<string,list<string>> */
    private const ALLOWED_TAGS = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => [],
        'u' => [],
        's' => [],
        'h2' => [],
        'h3' => [],
        'blockquote' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href', 'target', 'rel'],
        'img' => ['src', 'alt', 'width', 'height'],
        'pre' => ['data-language'],
        'code' => [],
        'span' => ['class'], // Quill code-block token spans only
    ];

    private const DROP_WITH_CONTENT = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'button', 'input', 'link', 'meta'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<?xml encoding="utf-8"?><div>' . $html . '</div>';
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML($wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $dom->getElementsByTagName('div')->item(0);
        if ($root === null) {
            return '';
        }

        self::sanitizeChildren($root, $dom);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function sanitizeChildren(DOMNode $parent, DOMDocument $dom): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMText) {
                continue;
            }

            if (!$node instanceof DOMElement) {
                $parent->removeChild($node);
                continue;
            }

            $tag = strtolower($node->tagName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($node);
                continue;
            }

            if (!array_key_exists($tag, self::ALLOWED_TAGS)) {
                // Unknown/disallowed tag: keep sanitized children, drop the wrapper.
                self::sanitizeChildren($node, $dom);
                while ($node->firstChild !== null) {
                    $parent->insertBefore($node->firstChild, $node);
                }
                $parent->removeChild($node);
                continue;
            }

            self::sanitizeAttributes($node, $tag);
            self::sanitizeChildren($node, $dom);
        }
    }

    private static function sanitizeAttributes(DOMElement $node, string $tag): void
    {
        $allowedAttributes = self::ALLOWED_TAGS[$tag];

        foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);
            if (!in_array($name, $allowedAttributes, true)) {
                $node->removeAttribute($attribute->name);
                continue;
            }

            if ($name === 'href' || $name === 'src') {
                if (!self::isSafeUrl($attribute->value)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        if ($tag === 'a') {
            // Links opening in a new tab must not have window.opener access.
            if ($node->getAttribute('target') === '_blank') {
                $node->setAttribute('rel', 'noopener noreferrer');
            } else {
                $node->removeAttribute('target');
                $node->removeAttribute('rel');
            }
        }
    }

    private static function isSafeUrl(string $url): bool
    {
        $url = trim($url);
        if ($url === '') {
            return false;
        }
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) {
            return true; // internal relative path
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if ($scheme === null) {
            return false; // no scheme and not a recognized relative path — reject
        }

        return in_array(strtolower($scheme), ['http', 'https', 'mailto'], true);
    }

    /** Plain-text extraction for reading time / excerpts (mirrors src/lib/lexical.ts). */
    public static function toPlainText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        return trim(preg_replace('/\s+/', ' ', $text) ?? '');
    }
}
