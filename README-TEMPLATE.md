<p align="center">
  <img src=".github/assets/banner.png" alt="FormForge banner" width="100%" />
</p>

<h1 align="center">FormForge</h1>

<p align="center">
  Deterministic dynamic forms for Laravel with immutable revisions, conditional layouts,
  strict server-side validation, robust HTTP security, and staged file workflows.
</p>

<p align="center">
  <a href="https://packagist.org/packages/evanschleret/formforge"><img src="https://img.shields.io/packagist/v/evanschleret/formforge?label=packagist" alt="Packagist Version" /></a>
  <a href="https://packagist.org/packages/evanschleret/formforge"><img src="https://img.shields.io/packagist/dt/evanschleret/formforge" alt="Packagist Downloads" /></a>
  <a href="https://packagist.org/packages/evanschleret/formforge"><img src="https://img.shields.io/packagist/l/evanschleret/formforge" alt="License" /></a>
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4" alt="PHP >= 8.2" />
  <img src="https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-FF2D20" alt="Laravel 12.x | 13.x" />
</p>

## Why FormForge

FormForge gives you one backend source of truth for:

- form schema with deterministic keys
- pages and conditional visibility rules
- immutable revision history (`create`, `patch`, `publish`, `unpublish`)
- strict payload validation on effective (condition-resolved) schema
- configurable endpoint security (`auth`, `guard`, `middleware`, `ability`)
- file workflows for multipart and JSON-first clients

## Requirements

- PHP `>=8.2`
- Laravel `12.x` or `13.x`
- Note: Laravel `13.x` requires PHP `>=8.3` (Laravel framework requirement)

## Installation

```bash
composer require evanschleret/formforge
```

Publish config + migrations:

```bash
php artisan formforge:install
```

Run migrations:

```bash
php artisan migrate
```

### Upgrade existing installation safely

When FormForge ships new config keys or updated config comments, use:

```bash
php artisan formforge:install:merge
```

What this command does:

- keeps your existing values
- keeps Laravel-style comment blocks from the latest package config
- adds missing keys introduced by newer FormForge versions
- updates changed default comments/structure without resetting your overrides
- optionally publishes missing migrations (unless `--skip-migrations` is used)

Recommended workflow:

```bash
php artisan formforge:install:merge --dry-run
php artisan formforge:install:merge
php artisan migrate
```

Available options:

- `--dry-run`: preview merge changes without writing files
- `--skip-migrations`: do not publish missing migrations during merge
- `--no-backup`: skip backup generation for `config/formforge.php` before rewrite

## Quick start

```php
<?php

declare(strict_types=1);

use EvanSchleret\FormForge\Facades\Form;

Form::define('contact')
    ->title('Contact')
    ->version('1')
    ->category('contact')
    ->text('name')->required()->max(120)
    ->email('email')->required()
    ->textarea('message')->required();

Form::sync();
```

Get schema:

```php
$form = Form::get('contact');
$schema = $form->toArray();
```

Submit:

```php
$submission = $form->submit([
    'name' => 'Evan',
    'email' => 'evan@example.com',
    'message' => 'Hello',
]);
```

## Schema model

Every normalized schema contains:

- `key`, `version`, `title`
- flattened `fields`
- `pages[].fields[]`
- `conditions[]`
- `drafts.enabled`

When only `fields` are provided, FormForge auto-wraps them into a default page.

### Conditional rules

Conditions support:

- `target_type`: `page|field`
- `action`: `show|hide|skip|require|disable`
- `match`: `all|any`
- operators: `eq`, `neq`, `in`, `not_in`, `gt`, `gte`, `lt`, `lte`, `contains`, `not_contains`, `is_empty`, `not_empty`

At submission time, FormForge resolves the effective schema from payload + conditions, then validates against that effective shape.

## HTTP API

Default prefix: `api/formforge/v1`

### API resource customization

You can customize submission serialization with Laravel `JsonResource` classes:

```php
'http' => [
    'resources' => [
        'submission' => null, // optional full submission resource class
        'submitter' => \App\Http\Resources\UserResource::class, // optional submitted_by resource class
    ],
],
```

With `submitter`, submission payloads include a `submitted_by` key serialized through your resource.

### Schema endpoints

- `GET /forms/{key}`
- `GET /forms/{key}/versions`
- `GET /forms/{key}/versions/{version}`

### Submission endpoints

- `POST /forms/{key}/submit`
- `POST /forms/{key}/versions/{version}/submit`

### Resolve endpoints (debug/effective schema)

- `POST /forms/{key}/resolve`
- `POST /forms/{key}/versions/{version}/resolve`

By default, these endpoints are only enabled in `local` and `testing` environments.

### Upload staging endpoints

- `POST /forms/{key}/uploads/stage`
- `POST /forms/{key}/versions/{version}/uploads/stage`

### Draft endpoints (authenticated owner-based state)

- `POST /forms/{key}/drafts`
- `GET /forms/{key}/drafts/current`
- `DELETE /forms/{key}/drafts/current`

Draft behavior:

- user-bound (polymorphic owner)
- last write wins
- one draft per `(form_key, owner)`
- optional TTL cleanup
- rejected when form-level drafts are disabled

### Management endpoints

- `GET /forms` (paginated list of active forms, payload in `data.data`)
- `POST /forms` (create revision 1, UUID key auto-generated)
- `PATCH /forms/{key}` (creates a new draft revision)
- `POST /forms/{key}/publish` (creates a new published revision)
- `POST /forms/{key}/unpublish` (creates a new draft revision)
- `DELETE /forms/{key}` (soft delete all revisions)
- `GET /forms/{key}/revisions` (`include_deleted=1` supported)
- `GET /forms/{key}/diff/{fromVersion}/{toVersion}`
- `GET /forms/{key}/responses` (paginated list of form submissions)
- `GET /forms/{key}/responses/{submissionId}` (single submission detail)
- `DELETE /forms/{key}/responses/{submissionId}` (delete one submission)

Creation rule:

- `POST /forms` requires only `title`
- if both `fields` and `pages` are omitted (or empty), FormForge seeds:
  - one default page
  - one default field (`type: text`, `name: short_text`)

## Publishability

A form can be published only when:

- `title` is non-empty
- at least one page exists
- at least one field exists

## Endpoint security model

Security layers:

1. Global route middleware (`formforge.http.middleware`)
2. Endpoint options (`schema`, `submission`, `upload`, `resolve`, `draft`, `management`):
   - `auth`: `public|optional|required`
   - `guard`
   - `middleware`
   - `ability` (and per-action `abilities` for management)

Example:

```php
'http' => [
    'middleware' => ['api'],
    'draft' => [
        'auth' => 'required',
        'guard' => 'sanctum',
        'middleware' => ['throttle:60,1'],
        'ability' => 'formforge.draft',
    ],
    'management' => [
        'auth' => 'required',
        'guard' => 'sanctum',
        'abilities' => [
            'create' => 'formforge.create',
            'index' => 'formforge.index',
            'update' => 'formforge.update',
            'publish' => 'formforge.publish',
            'unpublish' => 'formforge.unpublish',
            'delete' => 'formforge.delete',
            'revisions' => 'formforge.read-revisions',
            'diff' => 'formforge.read-diff',
            'responses' => 'formforge.read-responses',
            'response' => 'formforge.read-response',
            'response_delete' => 'formforge.delete-response',
        ],
    ],
],
```

## Test mode submissions

`_formforge_test` (or configured header) enables test submissions when:

- `formforge.submissions.testing.enabled = true`
- current environment is in `formforge.submissions.testing.enabled_environments`

By default, allowed environments are `local` and `testing`.

## Validation rules

Validation is Laravel-native:

- automatic deterministic rules by field type
- `rules(...)` appends custom rules
- `replaceRules(...)` fully overrides generated rules
- API payload rules are string/array Laravel rules
- nullish handling is driven by `required`:
  - `required: false` => `null` and empty string are treated as absent
  - `required: true` => `null` and empty string are rejected

Reference: [Laravel validation rules](https://laravel.com/docs/validation#available-validation-rules)

## Upload modes

`formforge.uploads.mode` supports:

- `managed`: multipart submit with file objects
- `direct`: submit payload references already-uploaded files
- `staged`: upload first, then submit JSON with `upload_token`

For JSON-first clients, staged mode is recommended:

1. `POST /forms/{key}/uploads/stage` (multipart)
2. `POST /forms/{key}/submit` with JSON payload containing `upload_token`

## Idempotency

Management mutations support `Idempotency-Key`:

- same key + same payload => replayed response
- same key + different payload => `409 Conflict`

TTL:

```php
'http' => [
    'idempotency' => [
        'ttl_minutes' => 1440,
    ],
],
```

## Generate automation scaffold

Use the generator command as the default entrypoint for submission automations:

```bash
php artisan formforge:make:automation CreateUserFromSubmission --form=user-registration --sync
```

Common variants:

```bash
php artisan formforge:make:automation User/CreateUserFromSubmission --form=user-registration --sync
php artisan formforge:make:automation CreateUserFromSubmission --form=user-registration --queue=formforge
```

Useful options:

- `--form=`: target form key for generated registration snippet
- `--sync`: generate sync registration snippet
- `--queue=` and `--connection=`: generate queued registration snippet
- `--path=` and `--namespace=`: customize destination and namespace
- `--force`: overwrite existing file

## Submission automations (code-first)

FormForge can run custom application code right after a submission is persisted.

Automations are registered in code, not in config mappings.

Example in `AppServiceProvider::boot()`:

```php
<?php

declare(strict_types=1);

use App\FormForge\Automations\CreateUserFromSubmission;
use EvanSchleret\FormForge\Facades\Form;

Form::automation('user-registration')
    ->sync()
    ->handler(CreateUserFromSubmission::class, 'create_user');
```

Queued execution:

```php
Form::automation('user-registration')
    ->queue('formforge')
    ->handler(CreateUserFromSubmission::class);
```

Handler contract:

```php
<?php

declare(strict_types=1);

namespace App\FormForge\Automations;

use EvanSchleret\FormForge\Automations\Contracts\SubmissionAutomation;
use EvanSchleret\FormForge\Models\FormSubmission;

final class CreateUserFromSubmission implements SubmissionAutomation
{
    public function handle(FormSubmission $submission): void
    {
        // business logic using $submission->payload
    }
}
```

Notes:

- run tracking is persisted in `formforge_submission_automation_runs`
- execution is idempotent per `(submission_id, automation_key)`
- infrastructure defaults are configurable under `formforge.automations.*`

## Useful commands

```bash
php artisan formforge:install
php artisan formforge:install:merge
php artisan formforge:sync
php artisan formforge:make:automation CreateUserFromSubmission --form=user-registration --sync
php artisan formforge:list
php artisan formforge:describe contact
php artisan formforge:http:options
php artisan formforge:http:routes
php artisan formforge:http:resolve contact --endpoint=submission --form-version=1
php artisan formforge:uploads:cleanup
php artisan formforge:drafts:cleanup
```

## Laravel Boost AI assets

FormForge ships package-level Laravel Boost assets:

- `resources/boost/guidelines/core.blade.php`
- `resources/boost/skills/formforge-integration/SKILL.md`

Use them to guide AI agents implementing FormForge in Laravel APIs.

## Roadmap

- [ ] Add first-party Laravel MCP integration (resources + tools) for AI-assisted FormForge implementation
- [ ] Add first-class signed upload URL flow for object storage direct upload
- [ ] Add cleanup command for expired idempotency keys
- [ ] Add OpenAPI export command for FormForge HTTP contracts
- [ ] Add policy scaffold command for management/draft abilities
- [ ] Add revision migration tooling (dry-run + apply) for schema evolution
- [ ] Add full FormForge page analytics helpers
- [ ] Add first-party Laravel Boost template pack for FormForge + auth presets
- [ ] Add submissions export command (CSV/JSONL) with filters
- [ ] Add webhook delivery for submission lifecycle events with retry + signature
- [ ] Add retention/anonymization command for old submissions (GDPR-friendly)
- [ ] Add response search/filter API (by date, test/live, version, submitter)
- [ ] Add configurable submission notifications (global and per-form) for channels like Telegram, Discord, Slack, and custom webhooks

## Other packages

If you want to explore more of my packages:

- [evanschleret/lara-mjml](https://github.com/EvanSchleret/lara-mjml)
- [evanschleret/laravel-user-presence](https://github.com/EvanSchleret/laravel-user-presence)
- [evanschleret/laravel-typebridge](https://github.com/EvanSchleret/laravel-typebridge)
- [evanschleret/formformclient (FormForge Client)](https://github.com/EvanSchleret/formforgeclient)

## Open source

- Contributing guide: [CONTRIBUTING.md](CONTRIBUTING.md)
- Security policy: [SECURITY.md](SECURITY.md)
- Code of conduct: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- License: [LICENSE](LICENSE)
