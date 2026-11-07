# Leash

Keep your keywords on a leash.

Leash scans markdown files and turns the first mention of each keyword into a link. You keep a single list of keywords and URLs, and Leash links your posts, scripts and docs to your existing content so you don't have to add those links by hand.

## Status

Early extraction. Leash started as `scripts/create-links.php` in [scott.keck-warren.com](https://scott.keck-warren.com/), a Jigsaw blog. Scott is pulling it into its own project so he can share it across his sites.

The script has been rebuilt as namespaced classes under `src/` with a `bin/leash` entry point. Its behavior matches the original exactly, quirks and blog-specific defaults included. The original script lives unchanged at `tests/Parity/create-links.php`, and the parity test suite runs both versions on the same fixtures and checks that they produce identical output and files.

## What It Does

- Reads a `keywords.txt` file of `keyword: url` pairs
- Walks markdown files in the directories you choose, or processes the single files you pass in
- Replaces the **first** occurrence of each keyword per file with `[keyword](url)`, keeping the original casing
- Matches whole words only, ignoring case
- Leaves YAML frontmatter alone
- Skips inline code, fenced code blocks, `<code>` tags and existing markdown links
- Shows a before/after preview of every change, with a `--dry-run` mode that writes nothing

## Keywords File

```text
# Lines starting with # are comments
Code Review: https://example.com/blog/code-review-habits/
continuous integration: https://example.com/ci-cd/
PHPUnit: https://example.com/tdd-with-phpunit/
```

Leash splits on the first colon, so URLs with colons work fine.

## Current Usage

```bash
# Preview changes across the default directories
bin/leash --dry-run

# Apply changes to a single file
bin/leash path/to/post.md

# Preview changes for a directory
bin/leash --dry-run content/posts
```

The script reads `keywords.txt` from the current working directory. With no paths given, it falls back to `source/_scripts` and `source/_postsbyyear`, the directories the blog uses.

See [`examples/`](examples/) for a ready-made `keywords.txt` and sample posts to try it on.

## Development

```bash
composer install
composer test             # Pest: unit, feature and parity suites
composer test:coverage    # fails under 90% line coverage (needs pcov or Xdebug)
```

## Roadmap

To turn the script into a reusable package:

1. **Composer package**: publish to Packagist and ship a `vendor/bin/leash` binary.
2. **Configuration**: add a `--keywords` option for the keywords file path and a config file (such as `leash.json`) for default paths, so the blog-specific directories can go.
3. ~~**Project layout**: split the single script into `src/` classes under a namespace, with a thin CLI entry point.~~ Done.
4. ~~**Tests**: cover the skip rules (code blocks, inline code, existing links, frontmatter) with PHPUnit or Pest.~~ Done (Pest).
5. **Known issues**:
   - Duplicate keywords that differ only in case (`phpunit` and `PHPUnit`) both get linked, because matching ignores case.
   - Detection of inline code and existing links uses heuristics and can misfire on unusual markdown.
6. **Nice to have**: exit codes for CI (fail when unlinked keywords exist), and a per-keyword limit instead of the fixed "first occurrence only" rule.

## License

MIT. See [LICENSE](LICENSE).
