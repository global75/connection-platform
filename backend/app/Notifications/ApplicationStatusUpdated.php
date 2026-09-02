<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Support\Frontend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApplicationStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    private const STATUS_LABELS = [
        'viewed'               => 'Your application has been viewed',
        'shortlisted'          => 'Great news — you\'ve been shortlisted!',
        'interview_scheduled'  => 'You\'ve been invited for an interview',
        'offer_extended'       => 'An offer has been extended to you!',
        'rejected'             => 'Your application was not selected',
        'hired'                => 'Congratulations! You\'ve been hired!',
    ];

    public function __construct(private JobApplication $application) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label    = self::STATUS_LABELS[$this->application->status] ?? 'Your application status has changed';
        $jobTitle = $this->application->job->title;
        $company  = $this->application->job->employer->company_name;

        return (new MailMessage)
            ->subject("Application Update: {$jobTitle} at {$company}")
            ->greeting("Hello {$notifiable->name}!")
            ->line($label)
            ->line("Position: **{$jobTitle}** at **{$company}**")
            ->action('View Application', Frontend::url("/job-seeker/applications/{$this->application->id}"))
            ->line('Log in to see more details.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'           => 'application_status_updated',
            'application_id' => $this->application->id,
            'status'         => $this->application->status,
            'job_title'      => $this->application->job->title,
            'company_name'   => $this->application->job->employer->company_name,
        ];
    }
}
