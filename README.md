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

Use this hierarchy to avoid malformed MJML:

- the layout contains `<mjml>` and `<mj-body>`
- the layout filename includes `.mjml.blade.php`
- child views extend the MJML layout
- child views do not include `.mjml` in the filename

Correct:

```text
resources/views/layouts/base.mjml.blade.php
resources/views/emails/welcome.blade.php
```

Incorrect:

```text
resources/views/layouts/base.mjml.blade.php
resources/views/emails/welcome.mjml.blade.php
```

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
