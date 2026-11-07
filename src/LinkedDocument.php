<?php

declare(strict_types=1);

namespace Leash;

final class LinkedDocument
{
    /**
     * @param list<AppliedLink> $links
     */
    public function __construct(
        public readonly string $content,
        public readonly array $links,
    ) {
    }

    public function changed(): bool
    {
        return $this->links !== [];
    }
}
