# Installing Your Test Tools

Install everything with Composer:

```bash
composer require --dev pestphp/pest
# Pest inside a fenced code block stays plain
```

Inline code like `vendor/bin/pest` is left alone too, and so is
<code>Docker</code> inside an HTML code tag.

Once Pest is installed, add it to your continuous integration pipeline,
and maybe run the whole thing in Docker.
