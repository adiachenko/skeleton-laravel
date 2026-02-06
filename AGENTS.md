# AI Guide

**Local overrides**: Check for `AGENTS.local.md` in the root. If it exists, read it. Its contents strictly supersede this file.

## Project Purpose

This repository is a Laravel package skeleton intended to be copied and configured by end users. The skeleton must be easy to rebrand (Composer name, namespaces, service provider, and config file key) and should work out of the box with the example test.

## Key Conventions

- Default placeholder package name: `vendor-slug/package-slug`.
- Default placeholder namespace: `VendorName\PackageName`.
- The service provider class should follow the package name (e.g., `PackageNameServiceProvider`).
- The example test must run immediately after configuration.
- By default, the skeleton should target the two latest PHP versions and the two latest Laravel versions.
- Use `$var` instead of `{$var}` in interpolated strings unless required for disambiguation.

## Configuration Script

- `configure.php` is the primary entry point for renaming (interactive-only, no CLI flags).
- Prompts for vendor, package, namespace, description, author name, author email, copyright holder, and license.
- Defaults are derived from: GitHub username (`gh` CLI), folder name, `git config`, and current `composer.json` values.
- `--namespace` is derived from vendor/package if not overridden.
- Updates `composer.json` including `authors` array (if author name/email provided), `LICENSE.md`, and renames provider/config files.

## Testing

- Uses Pest + Orchestra Testbench. Keep the test namespace consistent with Composer autoload-dev.
- The example test should always be discoverable after running configuration and autoload refresh.

## Workflow

- After any change in this repository, run `composer format`.

## Non-Goals

- CI setup is intentionally out of scope for now.

---

@AGENTS.local.md
