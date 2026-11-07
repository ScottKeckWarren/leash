<?php

use Leash\Rules\MarkdownLinkRule;
use Leash\Rules\SkipRule;

function linkSkips(string $content, string $needle, ?int $position = null): bool
{
    $position ??= strpos($content, $needle);

    return (new MarkdownLinkRule())->skips($content, $position, strlen($needle));
}

it('is a skip rule', function () {
    expect(new MarkdownLinkRule())->toBeInstanceOf(SkipRule::class);
});

it('allows text when there is no opening bracket before it', function () {
    expect(linkSkips('plain phpunit [later](x)', 'phpunit'))->toBeFalse();
});

it('skips text inside link text', function () {
    expect(linkSkips('See [phpunit docs](https://x.test) now', 'phpunit'))->toBeTrue();
});

it('skips link text even with whitespace before the parenthesis', function () {
    expect(linkSkips("See [phpunit]\n (https://x.test)", 'phpunit'))->toBeTrue();
});

it('allows bracketed text that is not a link', function () {
    expect(linkSkips('A [phpunit] note', 'phpunit'))->toBeFalse();
});

it('allows text after a closed bracket pair', function () {
    expect(linkSkips('A [note] about phpunit', 'phpunit'))->toBeFalse();
});

it('allows text after a completed link', function () {
    expect(linkSkips('[docs](https://x.test) phpunit', 'phpunit'))->toBeFalse();
});

it('skips text inside a link URL', function () {
    expect(linkSkips('[docs](https://x.test/phpunit/)', 'phpunit'))->toBeTrue();
});

it('skips text when a nearby link URL contains the same text (heuristic parity)', function () {
    $content = '[docs](https://x.test/phpunit) and phpunit';

    expect(linkSkips($content, 'phpunit', strrpos($content, 'phpunit')))->toBeTrue();
});

it('only looks for URLs within a 200 byte window', function () {
    $content = '[docs](https://x.test/phpunit)' . str_repeat(' ', 150) . 'phpunit';

    expect(linkSkips($content, 'phpunit', strrpos($content, 'phpunit')))->toBeFalse();
});
