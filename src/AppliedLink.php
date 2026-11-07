<?php

declare(strict_types=1);

namespace Leash;

/**
 * One keyword that was linked in a document, with preview context.
 */
final class AppliedLink
{
    public function __construct(
        public readonly string $keyword,
        public readonly string $url,
        public readonly string $original,
        public readonly string $replacement,
    ) {
    }
}
