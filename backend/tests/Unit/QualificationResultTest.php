<?php

namespace Tests\Unit;

use App\Services\LeadQualification\QualificationResult;
use Tests\TestCase;

/**
 * A verdict may arrive from a model response, so every field is treated as
 * untrusted until it has been through fromArray().
 */
class QualificationResultTest extends TestCase
{
    public function test_it_clamps_out_of_range_scores(): void
    {
        $this->assertSame(100, QualificationResult::fromArray(['score' => 180], 'claude')->score);
        $this->assertSame(0, QualificationResult::fromArray(['score' => -20], 'claude')->score);
        $this->assertSame(73, QualificationResult::fromArray(['score' => 72.6], 'claude')->score);
    }

    public function test_it_derives_the_tier_from_the_score(): void
    {
        config(['ai.lead_qualification.tiers' => ['hot' => 75, 'warm' => 50]]);

        $this->assertSame('hot', QualificationResult::fromArray(['score' => 75], 'claude')->tier);
        $this->assertSame('warm', QualificationResult::fromArray(['score' => 50], 'claude')->tier);
        $this->assertSame('cold', QualificationResult::fromArray(['score' => 49], 'claude')->tier);
    }

    public function test_it_replaces_an_unknown_recommended_action_with_one_derived_from_the_score(): void
    {
        config(['ai.lead_qualification.tiers' => ['hot' => 75, 'warm' => 50]]);

        $this->assertSame(
            'shortlist',
            QualificationResult::fromArray(['score' => 90, 'recommended_action' => 'hire_immediately'], 'claude')->recommendedAction
        );

        $this->assertSame(
            'reject',
            QualificationResult::fromArray(['score' => 10, 'recommended_action' => null], 'claude')->recommendedAction
        );
    }

    public function test_it_accepts_a_valid_recommended_action_regardless_of_case(): void
    {
        $this->assertSame(
            'review',
            QualificationResult::fromArray(['score' => 90, 'recommended_action' => 'Review'], 'claude')->recommendedAction
        );
    }

    public function test_it_drops_non_string_and_duplicate_list_entries(): void
    {
        $result = QualificationResult::fromArray([
            'score'     => 60,
            'strengths' => ['Strong PHP', 'Strong PHP', ['nested'], null, '  Ships fast  '],
        ], 'claude');

        $this->assertSame(['Strong PHP', 'Ships fast'], $result->strengths);
    }

    public function test_it_caps_lists_at_five_entries(): void
    {
        $result = QualificationResult::fromArray([
            'score'    => 60,
            'concerns' => ['a', 'b', 'c', 'd', 'e', 'f', 'g'],
        ], 'claude');

        $this->assertCount(5, $result->concerns);
    }

    public function test_it_keeps_only_known_numeric_criteria(): void
    {
        $result = QualificationResult::fromArray([
            'score'    => 60,
            'criteria' => [
                'skills'      => 88,
                'experience'  => '70',
                'compensation'=> 'unknown',
                'vibes'       => 100,
            ],
        ], 'claude');

        $this->assertSame(['skills' => 88, 'experience' => 70], $result->criteria);
    }

    public function test_it_tolerates_a_response_with_nothing_usable(): void
    {
        $result = QualificationResult::fromArray([], 'claude', 'claude-opus-5');

        $this->assertSame(0, $result->score);
        $this->assertSame('cold', $result->tier);
        $this->assertSame('', $result->summary);
        $this->assertSame([], $result->strengths);
        $this->assertSame('claude-opus-5', $result->model);
        $this->assertSame('completed', $result->toAttributes()['status']);
    }
}
