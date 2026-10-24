<p align="center">
  <img src=".github/assets/banner.png" alt="LaraMJML banner" width="100%" />
</p>

<h1 align="center">LaraMJML</h1>

<p align="center">
  MJML rendering for Laravel Blade emails with a dedicated view engine, configurable MJML options,
  and predictable output for Mailables and Notifications.
</p>

<p align="center">
  <a href="https://packagist.org/packages/evanschleret/lara-mjml"><img src="https://img.shields.io/packagist/v/evanschleret/lara-mjml?label=packagist" alt="Packagist Version" /></a>
  <a href="https://packagist.org/packages/evanschleret/lara-mjml"><img src="https://img.shields.io/packagist/dt/evanschleret/lara-mjml" alt="Packagist Downloads" /></a>
  <a href="https://packagist.org/packages/evanschleret/lara-mjml"><img src="https://img.shields.io/packagist/l/evanschleret/lara-mjml" alt="License" /></a>
  <a href="https://github.com/EvanSchleret/lara-mjml/actions/workflows/run-tests.yml"><img src="https://github.com/EvanSchleret/lara-mjml/actions/workflows/run-tests.yml/badge.svg" alt="Tests" /></a>
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.2-777BB4" alt="PHP >= 8.2" />
  <img src="https://img.shields.io/badge/Laravel-12.x%20%7C%2013.x-FF2D20" alt="Laravel 12.x | 13.x" />
</p>

## Why LaraMJML

LaraMJML gives you a focused way to render MJML in Laravel without changing your email workflow:

- keeps Laravel Blade as the template layer
- compiles `.mjml.blade.php` layouts through an MJML view engine
- supports MJML runtime options from Laravel config
- works with Mailables and Notifications
- keeps rendering behavior deterministic across environments

## Requirements

- PHP `>=8.2`
- Laravel `12.x` or `13.x`
- Node.js runtime with the `mjml` package installed

## Installation

Install the package:

```bash
composer require evanschleret/lara-mjml
```

Install MJML in your Laravel app:

```bash
npm install mjml
```

Publish the package config (optional):

```bash
php artisan vendor:publish --provider="EvanSchleret\LaraMjml\Providers\LaraMjmlServiceProvider"
```

Or install the configuration, layout and example Mailable in one command:

```bash
php artisan laramjml:install
```

Use `--without-examples` to generate only the configuration and layout, or `--force` to replace existing generated files.

The installer creates:

```text
config/laramjml.php
resources/views/layouts/mjml.blade.php
app/Mail/ExampleMail.php
resources/views/emails/example.mjml.blade.php
```

## Quick start

Create an MJML layout:

```blade
<mjml>
  <mj-body>
    <mj-section>
      <mj-column>
        @yield('content')
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
```

Save it as:

```text
resources/views/layouts/base.mjml.blade.php
```

Create your email view with regular Blade inheritance:

```blade
@extends('layouts.base')

@section('content')
  <mj-text>Hello {{ $userName }}</mj-text>
@endsection
```

Save it as:

```text
resources/views/emails/welcome.blade.php
```

## Blade file rules

LaraMJML supports two view conventions. In both cases, the layout contains `<mjml>` and `<mj-body>`, and its filename ends in `.mjml.blade.php`.

### Blade views inside an MJML layout

Use a regular `.blade.php` view when the view extends an MJML layout:

```text
resources/views/layouts/base.mjml.blade.php
resources/views/emails/welcome.blade.php
```

The child view uses Blade inheritance and contains MJML components inside its sections, as shown in the Quick start example.

### Direct MJML Blade views

Use `.mjml.blade.php` when the view itself should use the MJML view engine. The generators create this type of view and extend the generated `layouts.mjml` layout:

```text
resources/views/layouts/mjml.blade.php
resources/views/emails/example.mjml.blade.php
```

Pass the view name without its file extension to a Mailable or Notification, for example `emails.example`. Both conventions are supported; choose the one that matches how the view is structured.

## Generate a Mailable

Generate a Mailable, its MJML view and the shared layout:

```bash
php artisan laramjml:make:mail WelcomeMail
```

Customize the view name with `--view` or replace existing files with `--force`:

```bash
php artisan laramjml:make:mail WelcomeMail --view=emails.welcome --force
```

## Generate a Notification

Generate a Notification, its MJML view and the shared layout:

```bash
php artisan laramjml:make:notification InvoicePaid
```

The generated notification uses the mail channel and is immediately usable with `Notifiable` models.

## Preview a view locally

Render a view, convert its MJML to HTML and open the result in the default browser:

```bash
php artisan laramjml:preview emails.example --data='{"name":"John"}'
```

Save the preview at a specific path:

```bash
php artisan laramjml:preview emails.example \
  --data='{"name":"John"}' \
  --output=storage/app/mjml-preview.html \
  --no-open
```

Use `--no-open` to skip browser opening. Without `--output`, the command writes the HTML to a temporary file.

## Use with Mailable

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $userName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'userName' => $this->userName,
            ],
        );
    }
}
```

Send it:

```php
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Mail;

Mail::to($user->email)->send(new WelcomeMail($user->name));
```

## Use with Notification

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $user,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Welcome')
            ->view('emails.welcome', [
                'userName' => $this->user->name,
            ]);
    }
}
```

Dispatch it:

```php
$user->notify(new WelcomeNotification($user));
```

## Configuration

The `config/laramjml.php` file controls:

- `binary_path`: path to the Node binary for MJML execution
- `beautify`: format generated HTML
- `minify`: minify generated HTML
- `keep_comments`: preserve MJML comments in output
- `options`: extra options passed to MJML

Environment variables:

```env
MJML_NODE_PATH=null
LARA_MJML_BEAUTIFY=false
LARA_MJML_MINIFY=true
LARA_MJML_KEEP_COMMENTS=false
```

## Rendering events

LaraMJML dispatches lifecycle events when a MJML Blade view is rendered:

- `EvanSchleret\LaraMjml\Events\MjmlRendering` before rendering starts
- `EvanSchleret\LaraMjml\Events\MjmlRendered` after a successful conversion
- `EvanSchleret\LaraMjml\Events\MjmlRenderingFailed` when Blade or MJML throws an exception

The events expose the view path, the detected MJML version when available, the validation level, and the rendering duration in milliseconds on completed or failed renders. Successful renders also expose structured MJML validation errors. Failed renders expose the original exception and the failing phase (`blade` or `mjml`). Template data and generated HTML are never included in the event payload.

The existing exception behavior is preserved: failed renders continue to throw the original exception after dispatching `MjmlRenderingFailed`.

```php
use EvanSchleret\LaraMjml\Events\MjmlRendered;
use Illuminate\Support\Facades\Event;

Event::listen(MjmlRendered::class, function (MjmlRendered $event): void {
    logger()->info('MJML view rendered', [
        'path' => $event->path,
        'duration_ms' => $event->durationMs,
        'mjml_version' => $event->mjmlVersion,
    ]);
});
```

## Troubleshooting

- Empty or broken HTML: ensure only the layout contains `<mjml>` and `<mj-body>`
- MJML binary error: install `mjml` in the project and verify Node.js is available
- Malformed MJML exceptions: remove `.mjml` suffix from child views and keep it only on the layout

## Testing

Run tests:

```bash
composer test
```

## Validate MJML templates

Validate all MJML Blade layouts:

```bash
php artisan laramjml:validate
```

Validate specific paths:

```bash
php artisan laramjml:validate --path=resources/views/emails
php artisan laramjml:validate --path=resources/views/layouts/base.mjml.blade.php
```

Use a different validation level:

```bash
php artisan laramjml:validate --validation=soft
```

The command returns a non-zero exit code when at least one template fails validation, so it is ready for CI workflows.

### Validation output formats

The default table output remains unchanged. Use JSON output when another tool needs structured validation results:

```bash
php artisan laramjml:validate --format=json
```

Use GitHub workflow annotations to display MJML errors directly on changed files in GitHub Actions:

```bash
php artisan laramjml:validate --format=github
```

JSON results include the template path, validation status, formatted errors, and structured issues with their line and MJML tag.

### Render Blade before validation

Use `--render` to render Blade directives and expressions before passing the result to MJML. The option is useful for templates that contain Blade syntax:

```bash
php artisan laramjml:validate \
  --path=resources/views/emails \
  --render \
  --data='{"userName":"Ada"}'
```

The `--data` option accepts a JSON object and defaults to an empty object. Without `--render`, the command keeps validating the original template source as in previous versions.

## Roadmap

- [ ] Add an optional Artisan install command (`laramjml:install`) to publish config in one step
- [ ] Add optional plain HTML fallback rendering when MJML conversion fails
- [x] Add built-in MJML lint/validate command for CI workflows
- [ ] Add a stub generator for MJML layout + starter email view
- [ ] Add snapshot tests for common MJML output patterns
- [ ] Add first-party examples for dark mode email patterns
- [ ] Add docs for queue-first email pipelines with MJML pre-rendering
- [ ] Add benchmarking docs and performance tips for high-volume mailing

## Other packages

If you want to explore more of my packages:

- [evanschleret/formforge](https://github.com/EvanSchleret/formforge)
- [evanschleret/formformclient (FormForge Client)](https://github.com/EvanSchleret/formforgeclient)
- [evanschleret/laravel-user-presence](https://github.com/EvanSchleret/laravel-user-presence)
- [evanschleret/laravel-typebridge](https://github.com/EvanSchleret/laravel-typebridge)

## Open source

- Contributing guide: [CONTRIBUTING.md](CONTRIBUTING.md)
- Security policy: [SECURITY.md](SECURITY.md)
- Code of conduct: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- License: [LICENSE](LICENSE)
