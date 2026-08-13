<?php

namespace Tests\Feature\Settings;

use App\Enums\PaymentStatus;
use App\Repositories\Auth\UserSalesRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserSalesRepositoryTest extends TestCase
{
    private UserSalesRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('test', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('difficulty_level');
            $table->decimal('average_rating', 4, 2)->default(0);
            $table->decimal('price', 12, 2)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('question_count')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('test_purchases', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('buyer_user_id');
            $table->string('payment_status');
            $table->timestamp('purchased_at')->nullable();
        });

        Schema::create('test_interset_selections', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('test_id');
            $table->unsignedBigInteger('interest_id');
            $table->unsignedInteger('slot_no');
        });

        Schema::create('interests', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        $this->insertTest(1, 'اختبار اليوم');
        $this->insertTest(2, 'اختبار الشهر');
        $this->insertTest(3, 'اختبار قديم');
        $this->insertTest(4, 'اختبار مستخدم آخر');
        $this->insertTest(5, 'اختبار دفع فاشل');

        $this->insertPurchase(1, 1, PaymentStatus::Paid->value, now()->startOfDay());
        $this->insertPurchase(2, 1, PaymentStatus::Paid->value, now()->subWeeks(2));
        $this->insertPurchase(3, 1, PaymentStatus::Paid->value, now()->subMonths(2));
        $this->insertPurchase(4, 2, PaymentStatus::Paid->value, now()->subMinutes(30));
        $this->insertPurchase(5, 1, PaymentStatus::Failed->value, now()->subMinutes(15));

        $this->repository = app(UserSalesRepository::class);
    }

    public function test_returns_only_the_authenticated_users_paid_purchases_in_the_requested_tab(): void
    {
        $today = $this->repository->getPurchasedTests(1, 'today');
        $month = $this->repository->getPurchasedTests(1, 'month');
        $older = $this->repository->getPurchasedTests(1, 'older');

        $this->assertSame([1], $today->pluck('id')->all());
        $this->assertSame([1, 2], $month->pluck('id')->all());
        $this->assertSame([1, 2, 3], $older->pluck('id')->all());
        $this->assertNotNull($today->first()->purchased_at);
    }

    private function insertTest(int $id, string $title): void
    {
        DB::table('test')->insert([
            'id' => $id,
            'title' => $title,
            'description' => 'وصف الاختبار',
            'difficulty_level' => 'سهل',
            'average_rating' => 4.5,
            'price' => 10,
            'published_at' => now()->subDays(3),
            'question_count' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertPurchase(int $testId, int $buyerUserId, string $status, \DateTimeInterface $purchasedAt): void
    {
        DB::table('test_purchases')->insert([
            'test_id' => $testId,
            'buyer_user_id' => $buyerUserId,
            'payment_status' => $status,
            'purchased_at' => $purchasedAt,
        ]);
    }
}
