# Leash Examples

A small set of posts and a `keywords.txt` to try Leash on. Leash reads
`keywords.txt` from the current directory, so run it from here:

```bash
cd examples

# Preview the changes (writes nothing)
../bin/leash --dry-run posts

# Preview a single file
../bin/leash --dry-run posts/2026/code-is-skipped.md
```

Running without `--dry-run` rewrites the files in place, so back up or
commit `posts/` first if you want to run it for real.

## What Each Post Shows

| File | Behavior |
| --- | --- |
| `posts/2026/first-mention-only.md` | Only the first mention per file is linked; frontmatter is skipped |
| `posts/2026/code-is-skipped.md` | Fenced code, inline code and `<code>` tags are skipped |
| `posts/2026/existing-links.md` | Existing links are left alone; matching ignores case but keeps your casing |
| `posts/nothing-to-link.md` | Files with no matches are not reported or rewritten |

`keywords.txt` also shows comments and a URL containing a colon (the Docker
entry's port number).
