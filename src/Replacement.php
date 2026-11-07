<?php

declare(strict_types=1);

namespace Leash;

/**
 * The result of linking one keyword in a document body.
 */
final class Replacement
{
    public function __construct(
        /** The full body after the link was inserted. */
        public readonly string $content,
        /** Short context around the match before the change, for previews. */
        public readonly string $original,
        /** The same context after the change, for previews. */
        public readonly string $replacement,
    ) {
    }
}
