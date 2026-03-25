# Changelog

All notable changes to `lara-mjml` will be documented in this file.

## v1.0.0 - 2026-03-25

### LaraMJML v1.0.0

#### Highlights

- Added a new `laramjml:validate` Artisan command to validate MJML Blade templates with CI-friendly exit codes
- Added Laravel 13 support and removed Laravel 11 support
- Refreshed README documentation

#### Added

- `php artisan laramjml:validate`
- New validation support classes:
  - `MjmlTemplateValidator`
  - `MjmlTemplateValidationResult`
  
- New tests for:
  - `MJMLEngine` behavior
  - MJML validation service behavior
  

#### Breaking Changes

- Dropped support for Laravel 11

#### Upgrade Notes

- If your app is still on Laravel 11, upgrade to Laravel 12+ before using this release
- You can now add MJML checks to CI with:
  - `php artisan laramjml:validate`
  

## v0.3 - 2025-11-11

### What's Changed

* Fix mailable classes usage by @EvanSchleret in https://github.com/EvanSchleret/lara-mjml/pull/15
* Changed README.md to make it clearer by @EvanSchleret

**Full Changelog**: https://github.com/EvanSchleret/lara-mjml/compare/v0.2.2...v0.3

## v0.2.2 - 2025-04-04

### What's Changed

* doc: add a new mjml installation step in the documentation by @EvanSchleret in https://github.com/EvanSchleret/lara-mjml/pull/13

**Full Changelog**: https://github.com/EvanSchleret/lara-mjml/compare/v0.2.1...v0.2.2

## v0.2.1 - 2025-03-14

### What's Changed

* chore(#11): update package dependencies to support Laravel 12.x by @EvanSchleret in https://github.com/EvanSchleret/lara-mjml/pull/12

**Full Changelog**: https://github.com/EvanSchleret/lara-mjml/compare/v0.2...v0.2.1

## v0.2 - 2025-01-05

### What's Changed

* feat(#9)!: post process blade views with mjml by @EvanSchleret in https://github.com/EvanSchleret/lara-mjml/pull/10

**Full Changelog**: https://github.com/EvanSchleret/lara-mjml/compare/v0.1.2...v0.2

## v0.1.2 - 2024-07-23

**Full Changelog**: https://github.com/EvanSchleret/lara-mjml/compare/v0.1.1...v0.1.2

## 0.1.0 - 2024-07-23

Add initial release
