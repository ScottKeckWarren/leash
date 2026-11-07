<?php

declare(strict_types=1);

namespace Leash\Markdown;

/**
 * A markdown document split into its YAML frontmatter and body.
 *
 * Only the body is ever searched for keywords; the frontmatter is
 * re-attached untouched (apart from delimiter normalization) on write.
 */
final class Frontmatter
{
    private const PATTERN = '/^---\s*\n(.*?\n)---\s*\n(.*)/s';

    private function __construct(
        public readonly string $frontmatter,
        public readonly string $body,
    ) {
    }

    public static function split(string $content): self
    {
        if (preg_match(self::PATTERN, $content, $matches)) {
            return new self("---\n{$matches[1]}---\n", $matches[2]);
        }

        return new self('', $content);
    }

    public function withBody(string $body): self
    {
        return new self($this->frontmatter, $body);
    }

    public function join(): string
    {
        return $this->frontmatter . $this->body;
    }
}
