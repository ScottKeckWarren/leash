<?php

declare(strict_types=1);

namespace Leash;

use Leash\Markdown\Frontmatter;

/**
 * Links every keyword in a markdown document's body, leaving frontmatter alone.
 *
 * Keywords are applied one after another, so each keyword sees the links
 * inserted by the keywords before it.
 */
final class DocumentLinker
{
    public function __construct(private readonly KeywordLinker $linker)
    {
    }

    /**
     * @param list<Keyword> $keywords
     */
    public function link(string $content, array $keywords): LinkedDocument
    {
        $document = Frontmatter::split($content);
        $body = $document->body;
        $links = [];

        foreach ($keywords as $keyword) {
            $replacement = $this->linker->linkFirst($body, $keyword->keyword, $keyword->url);

            if ($replacement === null) {
                continue;
            }

            $body = $replacement->content;
            $links[] = new AppliedLink(
                $keyword->keyword,
                $keyword->url,
                $replacement->original,
                $replacement->replacement,
            );
        }

        return new LinkedDocument($document->withBody($body)->join(), $links);
    }
}
