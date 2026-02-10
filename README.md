# Laravel Package Skeleton

A starting point for building Laravel packages with modern defaults:

- PHP `8.4` and `8.5`
- Laravel `11` and `12`
- Pest + Orchestra Testbench for package testing
- Pint, PHPStan, and Rector for quality checks

> Version policy: this skeleton tracks the two latest stable PHP and Laravel versions.

## Quick Start

Use this repository as a template for your package, then run the interactive configurator.

```bash
# Replace <package-slug> with your package folder name
git clone https://github.com/adiachenko/skeleton-laravel.git <package-slug>
cd <package-slug>

# Rebrand the skeleton
composer configure
```

After configuration is complete, start your own clean history:

```bash
rm -rf .git
git init
git add -A
git commit -m "Initial commit"
```

## Configure Script: What Gets Updated

The script (`composer configure`) is interactive-only. Each prompt shows a default value in square brackets, and pressing Enter accepts that default.
It asks for:

- Vendor
- Package
- Namespace
- Description
- Author name
- Author email
- Copyright holder
- License (MIT or proprietary)

It then updates the package identity across the skeleton, including:

- `composer.json` (name, namespace, provider, description, authors)
- Service provider class/file naming
- Config file naming and key wiring
- `LICENSE.md`
- `AGENTS.md` initialization for the generated package

`README.md` should be edited manually after reinitializing your repository. Remove template-specific content before [Development Commands](#development-commands) section and replace it with your package's own introduction, installation, and usage sections.

## Development Commands

| Command                  | Purpose                                            |
| ------------------------ | -------------------------------------------------- |
| `composer test`          | Run the test suite (`pest --compact`).             |
| `composer format`        | Run Laravel Pint and Prettier formatting.          |
| `composer analyse`       | Run static analysis (`phpstan`).                   |
| `composer refactor`      | Apply Rector refactors.                            |
| `composer coverage`      | Run tests with local coverage (`pest --coverage`). |
| `composer coverage:herd` | Run coverage via Laravel Herd tooling.             |

## Git Hooks

Install project hooks:

```bash
sh install-git-hooks.sh
```

Installed hooks:

- `pre-commit` runs `composer format`
- `pre-push` runs `composer analyse`

If you use Fork and hooks misbehave, see [this issue](https://github.com/fork-dev/Tracker/issues/996).

## PhpStorm Setup (Optional)

Recommended setup for consistent formatting:

- `Settings | Editor | Code Style`: ensure "Enable EditorConfig support" is checked.
- `Settings | PHP | Quality Tools | Laravel Pint`: use ruleset from `pint.json`
- `Settings | PHP | Quality Tools`: set Laravel Pint as external formatter
- `Settings | Tools | Actions on Save`: enable reformat on save
- `Settings | Languages & Frameworks | JavaScript | Prettier`: use automatic config, enable "Run on save", and prefer Prettier config. Include `md` in Prettier file extensions.

## VSCode/Cursor Setup

All formatting settings for VSCode and Cursor are included under `.vscode/` folder and should be picked up automatiically by the editor as long as you install suggested extensions.

## Testing Lower Dependency Versions

To validate compatibility with Laravel 11 without editing `composer.json`:

```bash
composer update illuminate/contracts:^11.0 orchestra/testbench:^9.0 pestphp/pest:^4.0 pestphp/pest-plugin-laravel:^4.0 -W
```

## Config Publishing Example

The default skeleton publishes its config under:

```bash
php artisan vendor:publish --tag=skeleton-laravel-config
```

Resulting file:

- `config/skeleton-laravel.php`
