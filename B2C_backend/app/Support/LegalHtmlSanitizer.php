<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerAction;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class LegalHtmlSanitizer
{
    public function sanitize(?string $html): string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return '';
        }

        $config = (new HtmlSanitizerConfig())
            ->defaultAction(HtmlSanitizerAction::Block)
            ->allowLinkSchemes(['http', 'https', 'mailto', 'tel'])
            ->allowRelativeLinks()
            ->forceAttribute('a', 'rel', 'noopener noreferrer');

        foreach (['p', 'br', 'strong', 'em', 'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote'] as $tag) {
            $config = $config->allowElement($tag);
        }

        $config = $config->allowElement('a', ['href', 'title', 'rel']);

        foreach (['script', 'iframe', 'object', 'embed', 'style'] as $tag) {
            $config = $config->dropElement($tag);
        }

        return (new HtmlSanitizer($config))->sanitize($html);
    }
}
