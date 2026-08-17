<?php

namespace Tests\Feature\Tests;

use App\Enums\RevisionType;
use App\Models\Test;
use App\Repositories\Tests\TestRepository;
use App\Services\Notifications\NotificationCenter;
use App\Services\Payments\CheckoutMinimumAmountService;
use App\Services\Tests\ScientificChangeDetector;
use App\Services\Tests\UpdateTestService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class UpdateTestServiceChangeLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('test_question', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('test_id');
        });

        Schema::create('test_question_options', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('test_question_id');
        });
    }

    public function test_change_logs_keep_existing_targets_and_clear_deleted_targets(): void
    {
        DB::table('test_question')->insert([
            'id' => 41873,
            'test_id' => 801,
        ]);

        DB::table('test_question_options')->insert([
            'id' => 900,
            'test_question_id' => 41873,
        ]);

        $repository = Mockery::mock(TestRepository::class);

        $repository->shouldReceive('createRevisionChangeLog')
            ->once()
            ->ordered()
            ->with(
                602,
                801,
                null,
                RevisionType::QuestionText->value,
                41873,
                null,
                'ما هو المفتاح الأساسي؟',
                'ما تعريف المفتاح الأساسي؟',
                828
            );

        $repository->shouldReceive('createRevisionChangeLog')
            ->once()
            ->ordered()
            ->with(
                602,
                801,
                null,
                RevisionType::AnswerText->value,
                41873,
                null,
                'إجابة محذوفة',
                null,
                828
            );

        $repository->shouldReceive('createRevisionChangeLog')
            ->once()
            ->ordered()
            ->with(
                602,
                801,
                null,
                RevisionType::AnswerText->value,
                41873,
                900,
                'إجابة قديمة',
                'إجابة معدلة',
                828
            );

        $repository->shouldReceive('createRevisionChangeLog')
            ->once()
            ->ordered()
            ->with(
                602,
                801,
                null,
                RevisionType::QuestionText->value,
                null,
                null,
                'سؤال محذوف',
                null,
                828
            );

        $service = new UpdateTestService(
            testRepository: $repository,
            changeDetector: new ScientificChangeDetector,
            notificationCenter: Mockery::mock(NotificationCenter::class),
            checkoutMinimumAmountService: Mockery::mock(CheckoutMinimumAmountService::class),
        );

        $test = new Test;
        $test->forceFill(['id' => 801]);

        $changes = [
            [
                'type' => RevisionType::QuestionText->value,
                'target_question_id' => 41873,
                'target_option_id' => null,
                'before' => 'ما هو المفتاح الأساسي؟',
                'after' => 'ما تعريف المفتاح الأساسي؟',
            ],
            [
                'type' => RevisionType::AnswerText->value,
                'target_question_id' => 41873,
                'target_option_id' => 500,
                'before' => 'إجابة محذوفة',
                'after' => null,
            ],
            [
                'type' => RevisionType::AnswerText->value,
                'target_question_id' => 41873,
                'target_option_id' => 900,
                'before' => 'إجابة قديمة',
                'after' => 'إجابة معدلة',
            ],
            [
                'type' => RevisionType::QuestionText->value,
                'target_question_id' => 77777,
                'target_option_id' => null,
                'before' => 'سؤال محذوف',
                'after' => null,
            ],
        ];

        $method = new ReflectionMethod($service, 'createScientificChangeLogs');
        $method->setAccessible(true);
        $method->invoke($service, 602, $test, $changes, 828);
    }
}
