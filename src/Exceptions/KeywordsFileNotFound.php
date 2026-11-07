<?php

declare(strict_types=1);

namespace Leash\Exceptions;

use RuntimeException;

final class KeywordsFileNotFound extends RuntimeException
{
    public static function at(string $path): self
    {
        return new self("Keywords file not found: {$path}");
    }
}
