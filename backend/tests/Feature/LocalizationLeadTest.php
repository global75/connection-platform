<?php

namespace Tests\Feature;

use App\Models\LocalizationLead;
use App\Notifications\LocalizationLeadNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LocalizationLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_submission_is_stored_and_notified(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/services/localization', [
            'name'             => 'Ada Lovelace',
            'email'            => 'ada@example.com',
            'app_url'          => 'https://app.example.com',
            'target_languages' => ['french', 'arabic'],
            'message'          => 'Need RTL support before Q4 launch.',
        ]);

        $response->assertCreated();
        $response->assertJson(['message' => 'Your audit request has been received!']);

        $this->assertDatabaseHas('localization_leads', [
            'name'    => 'Ada Lovelace',
            'email'   => 'ada@example.com',
            'app_url' => 'https://app.example.com',
            'status'  => 'new',
        ]);

        $lead = LocalizationLead::sole();
        $this->assertSame(['french', 'arabic'], $lead->target_languages);

        Notification::assertSentOnDemand(
            LocalizationLeadNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === config('leads.notify_email')
        );
    }

    public function test_target_languages_must_be_a_known_language(): void
    {
        $response = $this->postJson('/api/services/localization', [
            'name'             => 'Ada Lovelace',
            'email'            => 'ada@example.com',
            'app_url'          => 'https://app.example.com',
            'target_languages' => ['klingon'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['target_languages.0']);
        $this->assertSame(0, LocalizationLead::count());
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $response = $this->postJson('/api/services/localization', [
            'email'             => 'not-an-email',
            'target_languages'  => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'app_url', 'target_languages']);
    }
}
