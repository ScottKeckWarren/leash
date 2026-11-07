<?php

use Leash\Rules\FencedCodeBlockRule;
use Leash\Rules\SkipRule;

function fencedSkips(string $content, string $needle): bool
{
    return (new FencedCodeBlockRule())->skips($content, strrpos($content, $needle), strlen($needle));
}

it('is a skip rule', function () {
    expect(new FencedCodeBlockRule())->toBeInstanceOf(SkipRule::class);
});

it('skips text inside a backtick fence', function () {
    expect(fencedSkips("Intro\n```php\nphpunit\n```\n", 'phpunit'))->toBeTrue();
});

it('skips text inside a tilde fence', function () {
    expect(fencedSkips("Intro\n~~~\nphpunit\n~~~\n", 'phpunit'))->toBeTrue();
});

it('allows text after a closed fence', function () {
    expect(fencedSkips("```\ncode\n```\nthen phpunit", 'phpunit'))->toBeFalse();
});

it('allows text when there are no fences', function () {
    expect(fencedSkips('just phpunit', 'phpunit'))->toBeFalse();
});

it('skips text inside an unclosed fence', function () {
    expect(fencedSkips("```\nphpunit forever", 'phpunit'))->toBeTrue();
});

it('ignores indented fences', function () {
    expect(fencedSkips("  ```\nphpunit", 'phpunit'))->toBeFalse();
});

it('lets backtick and tilde markers close each other (heuristic parity)', function () {
    expect(fencedSkips("```\ncode\n~~~\nphpunit", 'phpunit'))->toBeFalse();
});
