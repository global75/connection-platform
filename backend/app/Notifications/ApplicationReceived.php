<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Support\Frontend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private JobApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $seeker  = $this->application->jobSeeker->user;
        $jobTitle = $this->application->job->title;

        return (new MailMessage)
            ->subject("New Application: {$jobTitle}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$seeker->name} has applied to your job posting: **{$jobTitle}**.")
            ->action('View Application', Frontend::url("/employer/applications/{$this->application->id}"))
            ->line('Log in to review their profile and cover letter.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'application_received',
            'application_id' => $this->application->id,
            'job_title'      => $this->application->job->title,
            'seeker_name'    => $this->application->jobSeeker->user->name,
        ];
    }
}
