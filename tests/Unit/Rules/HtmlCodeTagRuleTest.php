<?php

use Leash\Rules\HtmlCodeTagRule;
use Leash\Rules\SkipRule;

function htmlCodeSkips(string $content, string $needle): bool
{
    return (new HtmlCodeTagRule())->skips($content, strrpos($content, $needle), strlen($needle));
}

it('is a skip rule', function () {
    expect(new HtmlCodeTagRule())->toBeInstanceOf(SkipRule::class);
});

it('skips text inside a code tag', function () {
    expect(htmlCodeSkips('Run <code>phpunit</code>', 'phpunit'))->toBeTrue();
});

it('skips text inside a code tag with attributes', function () {
    expect(htmlCodeSkips('Run <code class="x">phpunit</code>', 'phpunit'))->toBeTrue();
});

it('skips text inside an unclosed code tag', function () {
    expect(htmlCodeSkips('Run <code>phpunit', 'phpunit'))->toBeTrue();
});

it('allows text after a closed code tag', function () {
    expect(htmlCodeSkips('<code>x</code> phpunit', 'phpunit'))->toBeFalse();
});

it('allows text with no code tags', function () {
    expect(htmlCodeSkips('plain phpunit', 'phpunit'))->toBeFalse();
});

it('allows text inside a new tag opened after a closed code tag only if it is not code', function () {
    expect(htmlCodeSkips('<code>a</code> <em>phpunit</em>', 'phpunit'))->toBeFalse()
        ->and(htmlCodeSkips('<code>a</code> <code>phpunit', 'phpunit'))->toBeTrue();
});
