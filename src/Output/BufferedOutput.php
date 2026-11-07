<?php

declare(strict_types=1);

namespace Leash\Output;

/**
 * Collects output in memory, mainly for tests.
 */
final class BufferedOutput implements Output
{
    private string $buffer = '';

    public function write(string $text): void
    {
        $this->buffer .= $text;
    }

    public function fetch(): string
    {
        return $this->buffer;
    }
}
