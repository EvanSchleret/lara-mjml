<?php

namespace LaraMjmlGeneratedNamespace;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LaraMjmlGeneratedClass extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('LaraMjmlGeneratedSubject')
            ->view('lara_mjml_generated_view');
    }
}
