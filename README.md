# PS Decompose Word

WordPress plugin that improves text wrapping for long compound words in languages like German, Norwegian, Swedish, and Dutch.

## Features

- **CSS hyphenation** — Enables native browser hyphenation on frontend content.
- **Soft hyphen exceptions** — Insert explicit break points via an exception dictionary at render time.
- **TeX pattern fallback** — Automatic hyphenation using `org_heigl/hyphenator` dictionaries for 30+ locales (German, Norwegian (Bokmål/Nynorsk), Danish, Dutch, Swedish, Icelandic, English, Spanish, French, Italian, Portuguese, Polish, Czech, Slovak, Slovenian, Hungarian, and many more).
- **Locale-aware** — Prefix exceptions with a locale (e.g. `nb_NO:digitaliserings-organisasjon`).
- **Case-preserving** — Matches exceptions case-insensitively but renders with original casing.
- **Non-destructive** — Processes at render time; saved post content is never modified.
- **Block-aware** — Filters Gutenberg blocks and classic `the_content` output.

## Requirements

- PHP 8.3+
- WordPress 6.8+

## Installation

**Composer:**

```bash
composer require soderlind/ps-decompose-word
```

**Manual:**

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **PS Decompose Word**.
3. Configure under **Settings → PS Decompose Word**.

## Exception Dictionary

Add entries in **Settings → PS Decompose Word → Exception dictionary**.

| Format | Example |
|--------|--------|
| Explicit | `Donaudampfschifffahrtsgesellschaft=Donau-dampf-schiff-fahrts-gesellschaft` |
| Shorthand | `digitaliserings-organisasjon` |
| Locale-prefixed | `nb_NO:menneske-rettighets-organisasjon` |

Hyphens in the replacement mark soft hyphen positions.

## Automatic Updates

This plugin supports automatic updates from GitHub releases via [soderlind/wordpress-github-updater](https://github.com/soderlind/wordpress-plugin-gitHub-updater).

When a new release is published on GitHub, WordPress will detect and offer the update through the standard plugin update mechanism.

## Development

```bash
composer install
composer test
```

Tests use [Pest](https://pestphp.com/) with [Brain Monkey](https://brain-wp.github.io/BrainMonkey/) for WordPress function mocks.

### Releasing

1. Update version in `ps-decompose-word.php` and `readme.txt`.
2. Update `CHANGELOG.md`.
3. Create a GitHub release with a tag (e.g., `0.1.9`).
4. The workflow automatically builds and attaches `ps-decompose-word.zip`.

## License

GPL-2.0-or-later