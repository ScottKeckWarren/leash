# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Leash is a PHP 8.2+ CLI (`bin/leash`) that turns the first mention of each keyword in markdown files into a link, using a `keywords.txt` list of `keyword: url` pairs. It was extracted from `scripts/create-links.php` in the scott.keck-warren.com Jigsaw blog.

**Parity with the original script is a hard requirement.** The original lives unchanged at `tests/Parity/create-links.php`. Don't edit it: it's the reference implementation. Many odd behaviors are kept on purpose and marked "(parity)" in comments and test names, for example: warning line numbers ignore blank lines, a keyword or URL of `0` counts as empty, `C++` can never match, double-backtick spans aren't treated as code, and relative-path stripping doesn't check for a separator. Don't "fix" these unless the user asks for a behavior change. Fixing one means changing or dropping the parity test for it on purpose.

The README Roadmap lists what's next (`--keywords` option, `leash.json` config, Packagist).

## Commands

```bash
composer test                                   # all suites
composer test:coverage                          # enforces --min=90 (needs pcov or Xdebug)
./vendor/bin/pest --testsuite=Parity            # Unit | Feature | Parity
./vendor/bin/pest tests/Unit/KeywordLinkerTest.php
./vendor/bin/pest --filter="splits on the first colon"
```

Work test-first (red, then green) with Pest. Line coverage is currently 100%.

CI (`.github/workflows/tests.yml`) runs on PHP 8.2 to 8.5 and enforces coverage on 8.4. `composer.lock` pins packages that need PHP 8.4.1+, so the 8.2 and 8.3 jobs run `composer update` instead of installing from the lock.

## Architecture

The flow is `bin/leash` → `Console\Application` → `LinkCreator`, which pulls files from `MarkdownFinder`, links them with `DocumentLinker`, and prints through `Reporter`.

- `Console\Application` parses args (`--dry-run`; other `--*` flags are ignored silently; every other argument is a path), prints the header, and loads `keywords.txt` **relative to the CWD**. With no paths it falls back to `source/_scripts` and `source/_postsbyyear`. On any `Exception` it prints `Error: ...` and exits 1.
- `KeywordLoader` returns `list<Keyword>` in file order. A duplicate keyword keeps its first position and takes the last URL. Numeric keywords are cast back to strings because PHP turns them into integer array keys.
- `DocumentLinker` splits off frontmatter (`Markdown\Frontmatter`) and applies keywords **one after another to the body**. Each keyword sees the links the earlier ones inserted, so the link rule is what stops a later keyword from matching inside an earlier link's URL.
- `KeywordLinker::linkFirst` runs a `\b...\b` case-insensitive match, keeps the original casing, and links the first occurrence that no `Rules\SkipRule` rejects. It also builds the ±20-byte preview context (`Replacement`).
- `Rules\*` are four heuristics computed over the whole body: inline code, fenced code, markdown links and `<code>` tags. To add a rule, implement `SkipRule` and register it in `KeywordLinker::withDefaultRules()`.
- `MarkdownFinder` is a generator, so path warnings appear in the output at the point each path is reached. Changing this would break output ordering parity.
- All output goes through `Output\Output`. Tests use `BufferedOutput`; the binary uses `StreamOutput(STDOUT)`. Output must stay byte-identical to the original script.

## Tests

- `tests/Unit`, `tests/Feature`: in-process. `Feature/ApplicationTest` uses `chdir()` into a temp dir. Helpers `makeTempDir()` and `removeDir()` are in `tests/Pest.php`.
- `tests/Parity`: runs the original script and `bin/leash` as subprocesses (`Tests\Support\Cli`) on copies of `tests/Parity/fixtures/*` and on a seeded random corpus. It asserts identical stdout, stderr, exit code and resulting files. Subprocess runs don't count toward coverage. For any behavior change, add a fixture or argument case here as well as a unit test.
- Avoid naming a global test constant `BINARY`: it's a reserved cast keyword in PHP.
