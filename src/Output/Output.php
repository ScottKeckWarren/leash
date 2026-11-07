<?php

declare(strict_types=1);

namespace Leash\Output;

interface Output
{
    public function write(string $text): void;
}
