<?php

namespace App\Notifications;

use App\Models\JobApplication;
use App\Models\LeadQualification;
use App\Support\Frontend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HotLeadIdentified extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private JobApplication $application,
        private LeadQualification $qualification,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $seeker   = $this->application->jobSeeker->user;
        $jobTitle = $this->application->job->title;

        $mail = (new MailMessage)
            ->subject("Hot lead ({$this->qualification->score}/100): {$jobTitle}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("**{$seeker->name}** applied to **{$jobTitle}** and scored "
                ."**{$this->qualification->score}/100** against your posting.")
            ->line($this->qualification->summary);

        foreach (array_slice($this->qualification->strengths ?? [], 0, 3) as $strength) {
            $mail->line("• {$strength}");
        }

        return $mail
            ->action('Review Application', Frontend::url("/employer/applications/{$this->application->id}"))
            ->line('Scores are guidance only — review the full application before deciding.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'               => 'hot_lead_identified',
            'application_id'     => $this->application->id,
            'job_title'          => $this->application->job->title,
            'seeker_name'        => $this->application->jobSeeker->user->name,
            'score'              => $this->qualification->score,
            'tier'               => $this->qualification->tier,
            'recommended_action' => $this->qualification->recommended_action,
        ];
    }
}
