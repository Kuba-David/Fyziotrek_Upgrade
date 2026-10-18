<?php
declare(strict_types=1);

/**
 * Allowlist HTML sanitizer for rich text written in the admin editor (Quill).
 * Anything not listed is unwrapped (text kept) or dropped (script/style/etc.).
 */
function sanitize_html(string $html, bool $inline = false): string
{
    $html = trim($html);
    if ($html === '' || $html === '<p><br></p>') {
        return '';
    }

    $allowed = $inline
        ? ['strong' => [], 'em' => [], 'u' => [], 's' => [], 'a' => ['href'], 'br' => []]
        : [
            'p' => [], 'br' => [], 'strong' => [], 'em' => [], 'u' => [], 's' => [],
            'a' => ['href'], 'h2' => [], 'h3' => [], 'h4' => [], 'blockquote' => [],
            'ul' => [], 'ol' => [], 'li' => ['data-list'],
        ];
    $aliases = ['b' => 'strong', 'i' => 'em', 'strike' => 's', 'del' => 's', 'h1' => 'h2', 'h5' => 'h4', 'h6' => 'h4', 'div' => 'p'];
    $drop = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'select', 'textarea', 'svg', 'math', 'template', 'noscript', 'head', 'title', 'meta', 'link'];

    $doc = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><div id="__root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);

    $root = $doc->getElementById('__root');
    if (!$root) {
        return '';
    }

    $clean = function (DOMNode $node) use (&$clean, $doc, $allowed, $aliases, $drop): void {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment || $child instanceof DOMProcessingInstruction) {
                $node->removeChild($child);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            // Quill UI helpers carry no content.
            if (in_array($tag, $drop, true) || str_contains((string) $child->getAttribute('class'), 'ql-ui')) {
                $node->removeChild($child);
                continue;
            }
            $tag = $aliases[$tag] ?? $tag;
            if (!isset($allowed[$tag])) {
                $clean($child);
                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);
                continue;
            }

            $replacement = $doc->createElement($tag);
            foreach ($allowed[$tag] as $attr) {
                if (!$child->hasAttribute($attr)) {
                    continue;
                }
                $value = trim($child->getAttribute($attr));
                if ($attr === 'href') {
                    if (!preg_match('~^(https?://|mailto:|tel:|/|#)~i', $value)) {
                        continue;
                    }
                    $replacement->setAttribute('href', $value);
                    if (preg_match('~^https?://~i', $value)) {
                        $replacement->setAttribute('target', '_blank');
                        $replacement->setAttribute('rel', 'noopener');
                    }
                    continue;
                }
                $replacement->setAttribute($attr, $value);
            }
            while ($child->firstChild) {
                $replacement->appendChild($child->firstChild);
            }
            $node->replaceChild($replacement, $child);
            $clean($replacement);
        }
    };
    $clean($root);

    // Quill 2 writes every list as <ol> with li[data-list]; turn bullet lists into real <ul>.
    foreach (iterator_to_array($root->getElementsByTagName('ol')) as $ol) {
        $items = iterator_to_array($ol->getElementsByTagName('li'));
        $bullet = $items && count(array_filter($items, fn ($li) => $li->getAttribute('data-list') === 'bullet')) === count($items);
        if ($bullet) {
            $ul = $doc->createElement('ul');
            while ($ol->firstChild) {
                $ul->appendChild($ol->firstChild);
            }
            $ol->parentNode->replaceChild($ul, $ol);
        }
    }
    foreach (iterator_to_array($root->getElementsByTagName('li')) as $li) {
        $li->removeAttribute('data-list');
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $doc->saveHTML($child);
    }
    $out = preg_replace('~<p>(\s|<br>)*</p>~', '', $out);
    return trim((string) $out);
}

/** Convert stored HTML into the shape Quill expects when the admin editor loads it again. */
function html_for_editor(string $html): string
{
    $html = (string) preg_replace_callback('~<ol>(.*?)</ol>~s', function ($m) {
        return '<ol>' . str_replace('<li>', '<li data-list="ordered">', $m[1]) . '</ol>';
    }, $html);
    return (string) preg_replace_callback('~<ul>(.*?)</ul>~s', function ($m) {
        return '<ol>' . str_replace('<li>', '<li data-list="bullet">', $m[1]) . '</ol>';
    }, $html);
}

/** Presentation tweaks for rich text: highlight "08:30–10:00" style schedule lines. */
function render_rich(string $html): string
{
    return (string) preg_replace(
        '~<p>(\d{1,2}[:.]\d{2}\s*[–-]\s*\d{1,2}[:.]\d{2})\s*~u',
        '<p class="schedule"><span class="schedule__time">$1</span> ',
        $html
    );
}
