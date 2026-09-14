<?php

namespace App\Notifications;

use App\Models\LocalizationLead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LocalizationLeadNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private LocalizationLead $lead) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $languages = collect($this->lead->target_languages)
            ->map(fn (string $lang) => ucfirst($lang))
            ->implode(', ');

        $mail = (new MailMessage)
            ->subject("New localization audit request: {$this->lead->name}")
            ->greeting('New lead from the SaaS Localization page')
            ->replyTo($this->lead->email, $this->lead->name)
            ->line("**{$this->lead->name}** ({$this->lead->email}) requested a localization audit.")
            ->line("App: {$this->lead->app_url}")
            ->line("Target languages: {$languages}");

        if ($this->lead->message) {
            $mail->line("Message: \"{$this->lead->message}\"");
        }

        return $mail;
    }
}
