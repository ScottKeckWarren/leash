<?php

declare(strict_types=1);

namespace Leash\Output;

final class StreamOutput implements Output
{
    /**
     * @param resource $stream
     */
    public function __construct(private readonly mixed $stream)
    {
    }

    public function write(string $text): void
    {
        fwrite($this->stream, $text);
    }
}
