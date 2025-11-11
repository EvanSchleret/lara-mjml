# LaraMjml

[![Latest Version on Packagist](https://img.shields.io/packagist/v/evanschleret/lara-mjml.svg?style=flat-square)](https://packagist.org/packages/evanschleret/lara-mjml)

Laravel MJML integration made simple.

## 🚀 Installation

You can install the package via composer. Also install the MJML npm package as a dependency.

```bash
composer require evanschleret/lara-mjml
npm i mjml
```

**Publish the configuration (optional):**

Publish the config file to customize the package configuration and add additional options to the MJML binary.

```bash
php artisan vendor:publish --provider="EvanSchleret\LaraMjml\Providers\LaraMjmlServiceProvider"
```

**Environment Variables and Configuration**

You can set the path to the MJML binary in your `.env` file.

```env
MJML_NODE_PATH=null
LARA_MJML_BEAUTIFY=false
LARA_MJML_MINIFY=true
LARA_MJML_KEEP_COMMENTS=false
```

## 🧩 How It Works

LaraMJML hooks into Laravel’s view system and lets you write MJML templates using Blade syntax. It compiles the MJML to HTML before sending the email.

Just add the `.mjml` extension to your Blade template files and use MJML tags as you normally would.

**Blade hierarchy**

The layout should contain the <mjml> and <mj-body> tags and includes `.mjml` extension before `.blade.php` extension.

The views extending the layout should **not** contain the <mjml> and <mj-body> tags nor the `.mjml` extension before `.blade.php` extension.

**✅ Correct**

```
resources/views/layouts/base.mjml.blade.php
resources/views/emails/welcome.blade.php
```

**❌ Incorrect**

```
resources/views/layouts/base.mjml.blade.php
resources/views/emails/welcome.mjml.blade.php
```

### ✉️ Usage with Mailable

```php
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $user;

    /**
     * Create a new message instance.
     */
    public function __construct(string $user)
    {
        $this->user = $user;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome',
            with: [
                'user' => $this->user,
            ]
        );
    }
}
```

Then send it:
```php
Mail::to($user->email)->send(
    new WelcomeMail('Welcome !', ['user' => $user])
);
```

// usage with notification
### 🔔 Usage with Notification

```php
<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly User $user,
    )
    { }
    
    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return new MailMessage()
            ->subject('Welcome !')
            ->view('emails.welcome', [
                'user' => $this->user,
            ]);
    }
}
```

Then call it:

```php
$user->notify(new WelcomeNotification($user));
```

## ⚙️ Configuration
The `config/laramjml.php` file controls:
- MJML binary path
- whether to beautify or minify
- comment preservation
- custom MJML configuration options

## 🧪 Testing

```bash
composer test
```

## 🧰 Troubleshooting
- **Empty or broken HTML** → ensure only the layout contains <mjml> and <mj-body>.
- **MJML binary missing** → ensure npx mjml runs successfully from your project root.
- **Spatie\Mjml\Exceptions\CouldNotConvertMjml Error: Malformed MJML. Check that your structure is correct and enclosed in <mjml> tags** → avoid .mjml suffix in child filenames.

## 🧑‍💻 Contributing

Pull requests welcome!

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
