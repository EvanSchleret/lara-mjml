# Changelog

All notable changes to `lara-mjml` will be documented in this file.

## v1.1.0 - 2026-11-10

### v1.1.0

#### ✨ Highlights

##### 🩺 Diagnose your MJML setup

Run `php artisan laramjml:doctor` to check that Node.js and MJML are available and that a sample conversion succeeds. The command reports actionable errors when something is missing.

##### 🛠️ Generate and preview email templates

Install the LaraMJML config, layout and example Mailable with one command, generate Mailables or Notifications, and preview rendered emails in your browser or save them as HTML.

##### 📊 Get more from validation and rendering

Validate rendered Blade templates, emit JSON results or GitHub Actions annotations, and listen for events before and after MJML rendering—including when a render fails.

#### 🚀 Features

- **`laramjml:doctor`**: Add a command to check the Node.js and MJML setup. ([#16](https://github.com/EvanSchleret/lara-mjml/pull/16)) [(8594a4f)](https://github.com/EvanSchleret/lara-mjml/commit/8594a4f)
- **Validation**: Add Blade rendering with `--render` and `--data`, JSON output with `--format=json`, and GitHub Actions annotations with `--format=github`. ([#16](https://github.com/EvanSchleret/lara-mjml/pull/16)) [(2b0107f)](https://github.com/EvanSchleret/lara-mjml/commit/2b0107f) [(af81d82)](https://github.com/EvanSchleret/lara-mjml/commit/af81d82)
- **Rendering events**: Dispatch events before rendering, after successful conversion, and when rendering fails. Expose duration, MJML version, validation details, and failure context without including template data or generated HTML. ([#16](https://github.com/EvanSchleret/lara-mjml/pull/16)) [(0c3b85a)](https://github.com/EvanSchleret/lara-mjml/commit/0c3b85a) [(e4ff85a)](https://github.com/EvanSchleret/lara-mjml/commit/e4ff85a)
- **Scaffolding and previews**: Add `laramjml:install`, `laramjml:make:mail`, `laramjml:make:notification`, and `laramjml:preview`. Clarify the supported MJML Blade view conventions. ([#16](https://github.com/EvanSchleret/lara-mjml/pull/16)) [(499b35d)](https://github.com/EvanSchleret/lara-mjml/commit/499b35d) [(e556971)](https://github.com/EvanSchleret/lara-mjml/commit/e556971)

#### 🐛 Bug Fixes

- **Dependencies**: Update `spatie/mjml-php` to `^1.3.0` and the development MJML toolchain to `^5.4.0`; address known transitive dependency advisories, including `brace-expansion`. This release requires Illuminate `^12.60` or `^13.10`. ([#16](https://github.com/EvanSchleret/lara-mjml/pull/16)) [(20977f5)](https://github.com/EvanSchleret/lara-mjml/commit/20977f5) [(7c72a3d)](https://github.com/EvanSchleret/lara-mjml/commit/7c72a3d)
- **`laramjml:doctor`**: Resolve the application path from the command’s Laravel application, with a working-directory fallback when no Laravel base path is available. ([#16](https://github.com/EvanSchleret/lara-mjml/pull/16)) [(2ac34c4)](https://github.com/EvanSchleret/lara-mjml/commit/2ac34c4)

#### ❤️ Contributors

- @EvanSchleret

Full Changelog: [v1.0.0...v1.1.0](https://github.com/EvanSchleret/lara-mjml/compare/v1.0.0...v1.1.0)

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
