<?php

use Tests\Support\Cli;

beforeEach(fn () => $this->dir = makeTempDir());
afterEach(fn () => removeDir($this->dir));

const LEASH_BIN = __DIR__ . '/../../bin/leash';

it('is executable', function () {
    expect(is_executable(LEASH_BIN))->toBeTrue();
});

it('runs the application and writes to stdout', function () {
    file_put_contents($this->dir . '/keywords.txt', "phpunit: u\n");
    file_put_contents($this->dir . '/post.md', "Use phpunit\n");

    $result = Cli::run(LEASH_BIN, ['post.md'], $this->dir);

    expect($result['exit'])->toBe(0)
        ->and($result['stderr'])->toBe('')
        ->and($result['stdout'])->toContain('Files modified: 1')
        ->and(file_get_contents($this->dir . '/post.md'))->toBe("Use [phpunit](u)\n");
});

it('exits with status 1 on errors', function () {
    $result = Cli::run(LEASH_BIN, [], $this->dir);

    expect($result['exit'])->toBe(1)
        ->and($result['stdout'])->toContain('Error: Keywords file not found: keywords.txt');
});
