<?php

use Leash\Rules\InlineCodeRule;
use Leash\Rules\SkipRule;

function inlineCodeSkips(string $content, string $needle): bool
{
    return (new InlineCodeRule())->skips($content, strpos($content, $needle), strlen($needle));
}

it('is a skip rule', function () {
    expect(new InlineCodeRule())->toBeInstanceOf(SkipRule::class);
});

it('skips text inside backticks', function () {
    expect(inlineCodeSkips('Run `phpunit` now', 'phpunit'))->toBeTrue();
});

it('allows text after a closed code span', function () {
    expect(inlineCodeSkips('Run `composer` then phpunit', 'phpunit'))->toBeFalse();
});

it('allows text with no backticks at all', function () {
    expect(inlineCodeSkips('Plain phpunit text', 'phpunit'))->toBeFalse();
});

it('counts backticks across lines', function () {
    expect(inlineCodeSkips("Open ` here\nand phpunit", 'phpunit'))->toBeTrue();
});

it('treats double backtick spans as outside code (heuristic parity)', function () {
    expect(inlineCodeSkips('Use ``phpunit`` here', 'phpunit'))->toBeFalse();
});
