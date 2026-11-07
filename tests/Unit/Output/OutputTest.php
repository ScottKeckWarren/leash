<?php

use Leash\Output\BufferedOutput;
use Leash\Output\Output;
use Leash\Output\StreamOutput;

it('buffers written text', function () {
    $output = new BufferedOutput();
    $output->write('Hello ');
    $output->write("world\n");

    expect($output)->toBeInstanceOf(Output::class)
        ->and($output->fetch())->toBe("Hello world\n");
});

it('writes text to a stream', function () {
    $stream = fopen('php://memory', 'w+');
    $output = new StreamOutput($stream);
    $output->write("Hello\n");

    rewind($stream);

    expect($output)->toBeInstanceOf(Output::class)
        ->and(stream_get_contents($stream))->toBe("Hello\n");
});
