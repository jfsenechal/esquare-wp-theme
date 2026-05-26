# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Repository layout

This is a self-hosted WordPress installation (the repo root is `WP_HOME` / `ABSPATH`). Most of what you see at the top level is **untracked vendored WordPress core** — see `.gitignore`. Only the project-specific pieces are version-controlled:

- `composer.json` / `composer.lock` — project dependencies (Symfony 8 + Twig 3 stack, see below).
- `wp-content/themes/esquare/` — the custom block theme (FSE) this repo exists to build. Structure:
  - `style.css`, `theme.json`, `functions.php` (loads `vendor/autoload.php` + `.env` via Symfony Dotenv, registers theme supports).
  - `templates/` and `parts/` — **FSE HTML block templates** (`.html`), not PHP and not Twig.
  - `twig-templates/` — Twig templates, exposed as the `Esquare` Twig namespace (also registered in `ide-twig.json`).
  - `src/` — PHP source, PSR-4 root for `Esquare\Theme\` (e.g. `src/Twig/TwigFactory.php` → `Esquare\Theme\Twig\TwigFactory`).
- `wp-content/plugins/` — only `akismet` and `hello.php` ship; no custom plugins yet.
- `git.sh` — `git pull && sh clean.sh` (note: `clean.sh` is not in the repo; ask before running).

## Composer / PHP setup

- **PHP 8.5+** required (`composer.json`), with `ext-intl` and `ext-pdo`.
- PSR-4 autoload: `Esquare\Theme\` → `wp-content/themes/esquare/src/`. Any class added under that namespace must live below that path.
- The dependency set is unusually heavy for a WP theme:
  - Symfony 8 components (`http-foundation`, `http-client`, `console`, `cache`, `mailer`, `dotenv`, `error-handler`).
  - Twig 3 (`twig/twig`, `intl-extra`, `string-extra`) — `ide-twig.json` registers the `Esquare` Twig namespace at `wp-content/themes/esquare/templates`.
  - `nesbot/carbon` for dates.
  - `wordpress/wp-ai-client` — WordPress AI client SDK.
- Symfony Flex `extra.symfony.require` pins to `8.0.*`; keep new Symfony deps within that range.
- `composer.lock` is committed (despite a `composer.lock` line under the `# Composer` section in `.gitignore`, the file is tracked — leave it tracked).
- Common commands: `composer install`, `composer update <pkg>`, `composer dump-autoload`.

## WordPress configuration

- `wp-config.php` is **not** in the repo (gitignored). Copy from `wp-config-sample.php` and fill in DB creds locally; do not commit.
- WordPress core (`wp-admin/`, `wp-includes/`, all top-level `wp-*.php`) is gitignored — do not edit core files; they will be wiped/overwritten by `git pull`/`clean.sh` workflows.
- `.gitignore` allow-lists a `marchebe` theme path that no longer exists; the active custom theme path is `esquare`. If you add a new tracked theme, add a matching `!/wp-content/themes/<name>/` exception.

## Agent skills available

Because this is a WordPress project, the WP-specific skills installed on this machine (`wp-block-development`, `wp-block-themes`, `wp-plugin-development`, `wp-rest-api`, `wp-interactivity-api`, `wp-performance`, `wp-wpcli-and-ops`, `wp-phpstan`, `wp-playground`, `wpds`, `wp-abilities-api`, `wp-project-triage`, `wordpress-router`) are the right entry points for WP-side work. Use the `wordpress-router` / `wp-project-triage` skills first when classifying a new task before diving into block, theme, or plugin work.

## Things this repo does **not** have (yet)

- No test runner, lint config, or build pipeline at the project root — no `package.json`, no PHPUnit config, no PHPStan config. If the user asks to add one, ask which tool first rather than guessing.
- No custom plugin or custom theme code yet — the `esquare` theme is greenfield. When scaffolding, follow the `wp-block-themes` / `wp-block-development` skill guidance, and respect the existing PSR-4 + Twig wiring rather than reinventing the loader.
