<?php

namespace Tests\Unit;

use App\Jobs\ProcessTestAiEvaluationJob;
use App\Models\TestAiEvaluationRequest;
use App\Repositories\Admin\TestAiEvaluationRepository;
use App\Services\Admin\TestAiEvaluation\TestAiEvaluationProviderOrchestrator;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ProcessTestAiEvaluationJobQueueTest extends TestCase
{
    public function test_it_uses_the_configured_test_ai_evaluation_queue(): void
    {
        Config::set('test_ai_evaluation.queue_name', 'custom-evaluation-queue');

        $job = new ProcessTestAiEvaluationJob(1);

        $this->assertSame('custom-evaluation-queue', $job->queue);
    }

    public function test_heavy_supervisor_consumes_legacy_default_queue_jobs(): void
    {
        $this->assertSame(
            ['heavy', 'default'],
            Config::get('horizon.defaults.supervisor-heavy.queue')
        );
    }

    public function test_it_does_not_evaluate_a_request_that_another_job_already_claimed(): void
    {
        Config::set('test_ai_evaluation.queue_name', 'heavy');

        $evaluationRequest = new TestAiEvaluationRequest;
        $evaluationRequest->forceFill([
            'id' => 1,
            'status' => TestAiEvaluationRepository::STATUS_PENDING,
        ]);

        $repository = $this->createMock(TestAiEvaluationRepository::class);
        $repository->expects($this->once())
            ->method('findById')
            ->with(1)
            ->willReturn($evaluationRequest);
        $repository->expects($this->once())
            ->method('markAsProcessing')
            ->with(
                $evaluationRequest,
                $this->isInstanceOf(CarbonInterface::class)
            )
            ->willReturn(false);

        $providerOrchestrator = $this->createMock(TestAiEvaluationProviderOrchestrator::class);
        $providerOrchestrator->expects($this->never())
            ->method('evaluate');

        $job = new ProcessTestAiEvaluationJob(1);

        $job->handle($repository, $providerOrchestrator);
    }
}
