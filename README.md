# Laravel Package Skeleton

A starting point for building a Laravel package.

## Configure the skeleton

```bash
# Run the interactive configuration script to rebrand the skeleton into your own package
composer configure

# After configuring, rename this README and remove the "Configure the skeleton" section.
# Reinitialize the repository to start with a clean Git history:
rm -rf .git
git init
git add -A
git commit -m "Initial commit"
```

The `configure` script prompts for:

- **Vendor** (required) - defaults to your GitHub username via `gh` CLI if available
- **Package** (required) - defaults to the current folder name
- **Namespace** - defaults to `StudlyVendor\StudlyPackage`
- **Description** - defaults to the current `composer.json` description
- **Author Name** - defaults to `git config user.name`
- **Author Email** - defaults to `git config user.email`
- **Copyright Holder** - defaults to `git config user.name`
- **License** - selector for MIT or proprietary

The script updates `composer.json` (including the `authors` field if provided), renames files, and scaffolds `LICENSE.md` based on the selected license.

## Install

```bash
composer require vendor-slug/package-slug
```

## Development

### Composer scripts

> Note that `composer format` and `composer analyse` are automatically run by Git hooks (see below).

| Script                   | Runs                                         | Use when                                                             |
| ------------------------ | -------------------------------------------- | -------------------------------------------------------------------- |
| `composer test`          | `pest --compact`                             | Run the test suite.                                                  |
| `composer format`        | `pint --parallel` + `npm run format`         | Format PHP and non-PHP files (requires Node dependencies installed). |
| `composer analyse`       | `phpstan`                                    | Run static analysis checks.                                          |
| `composer refactor`      | `rector`                                     | Apply automated refactors.                                           |
| `composer coverage`      | `pest --coverage`                            | Generate a local coverage report (requires Xdebug/PCOV).             |
| `composer coverage:herd` | `herd coverage ./vendor/bin/pest --coverage` | Generate coverage using Laravel Herd tooling.                        |

### Git hooks

Install local Git hooks:

```bash
sh install-git-hooks.sh
```

The installer creates these hooks in `.git/hooks`:

- `pre-commit` -> runs `composer format` (Pint + Prettier)
- `pre-push` -> runs `composer analyse` (PHPStan)

If you use Fork git client and encounter issues with the hooks, see [this issue](https://github.com/fork-dev/Tracker/issues/996).

### PhpStorm formatting

For the best formatting experience, configure Laravel Pint and Prettier:

**Settings | PHP | Quality Tools | Laravel Pint:**

Choose _Ruleset: defined in pint.json_.

**Settings | PHP | Quality Tools:**

Choose Laravel Pint as _External Formatter_.

**Settings | Tools | Actions on Save:**

Enable _Reformat code_ option for all file types.

**Settings | Languages & Frameworks | JavaScript | Prettier:**

Choose _Automatic Prettier Configuration_.

Enable _Run on save_ and _Prefer Prettier configuration over IDE code style_.

You may have to add `md` to the default list of file extensions if it's missing, e.g.

```bash
**/*.{js,ts,jsx,tsx,cjs,cts,mjs,mts,json,vue,astro,md}
```

### Testing lower dependency versions

You can test the package against Laravel 11 without modifying `composer.json` by running:

```shell script
composer update illuminate/contracts:^11.0 orchestra/testbench:^9.0 pestphp/pest:^4.0 pestphp/pest-plugin-laravel:^4.0 -W
```

## Config

Publish the config file:

```bash
php artisan vendor:publish --tag=skeleton-laravel-config
```

The config file is located at `config/skeleton-laravel.php`.
