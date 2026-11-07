<?php

declare(strict_types=1);

namespace Leash;

final class Keyword
{
    public function __construct(
        public readonly string $keyword,
        public readonly string $url,
    ) {
    }
}
