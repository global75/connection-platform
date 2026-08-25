<?php

namespace Tests\Unit;

use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Messages\Message;
use Anthropic\Messages\TextBlock;
use Anthropic\Messages\ThinkingBlock;
use Anthropic\ServiceContracts\MessagesContract;
use App\Services\LeadQualification\ClaudeLeadQualifier;
use App\Services\LeadQualification\LeadProfile;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use Tests\TestCase;

class ClaudeLeadQualifierTest extends TestCase
{
    private MessagesContract&MockObject $messages;

    private ClaudeLeadQualifier $qualifier;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.lead_qualification.tiers' => ['hot' => 75, 'warm' => 50]]);

        $this->messages  = $this->createMock(MessagesContract::class);
        $this->qualifier = new ClaudeLeadQualifier($this->messages);
    }

    public function test_it_reads_the_verdict_out_of_the_response(): void
    {
        $this->messages->method('create')->willReturn($this->response([
            'score'              => 82,
            'summary'            => 'Strong Laravel background.',
            'strengths'          => ['Ten years of PHP.'],
            'concerns'           => ['No Vue experience shown.'],
            'recommended_action' => 'shortlist',
            'criteria'           => ['skills' => 90, 'experience' => 85],
        ]));

        $result = $this->qualifier->qualify($this->lead());

        $this->assertSame(82, $result->score);
        $this->assertSame('hot', $result->tier);
        $this->assertSame('shortlist', $result->recommendedAction);
        $this->assertSame(['skills' => 90, 'experience' => 85], $result->criteria);
        $this->assertSame('claude', $result->provider);
        $this->assertSame('claude-opus-5', $result->model);
    }

    public function test_it_skips_leading_thinking_blocks(): void
    {
        $message = $this->response(['score' => 40], withThinking: true);
        $this->messages->method('create')->willReturn($message);

        $this->assertSame(40, $this->qualifier->qualify($this->lead())->score);
    }

    public function test_it_rejects_a_response_with_no_text_block(): void
    {
        $this->messages->method('create')->willReturn(
            Message::with(
                id: 'msg_1',
                container: null,
                content: [],
                model: 'claude-opus-5',
                stopDetails: null,
                stopReason: 'end_turn',
                stopSequence: null,
                usage: ['inputTokens' => 1, 'outputTokens' => 1],
            )
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no text block');

        $this->qualifier->qualify($this->lead());
    }

    public function test_it_rejects_a_non_json_verdict(): void
    {
        $this->messages->method('create')->willReturn($this->rawResponse('Sure! Here is my analysis…'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('non-JSON verdict');

        $this->qualifier->qualify($this->lead());
    }

    public function test_it_reports_a_refusal_rather_than_scoring_zero(): void
    {
        $this->messages->method('create')->willReturn(
            $this->rawResponse('{}', stopReason: 'refusal')
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('declined to qualify');

        $this->qualifier->qualify($this->lead());
    }

    public function test_it_wraps_api_errors(): void
    {
        $this->messages->method('create')->willThrowException(
            $this->createStub(APIStatusException::class)
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Claude rejected the qualification request');

        $this->qualifier->qualify($this->lead());
    }

    private function response(array $verdict, bool $withThinking = false): Message
    {
        return $this->rawResponse(json_encode($verdict), withThinking: $withThinking);
    }

    private function rawResponse(string $text, string $stopReason = 'end_turn', bool $withThinking = false): Message
    {
        $content = $withThinking
            ? [ThinkingBlock::with(signature: 'sig', thinking: 'Weighing the skills…'), TextBlock::with(null, $text)]
            : [TextBlock::with(null, $text)];

        return Message::with(
            id: 'msg_1',
            container: null,
            content: $content,
            model: 'claude-opus-5',
            stopDetails: null,
            stopReason: $stopReason,
            stopSequence: null,
            usage: ['inputTokens' => 100, 'outputTokens' => 50],
        );
    }

    private function lead(): LeadProfile
    {
        return new LeadProfile(
            applicationId: 1,
            job: ['title' => 'Senior Laravel Developer', 'required_skills' => ['PHP']],
            candidate: ['headline' => 'Backend Engineer', 'skills' => ['PHP']],
        );
    }
}
