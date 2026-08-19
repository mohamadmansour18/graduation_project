<?php

namespace Tests\Feature\Admin;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\Status;
use App\Exceptions\Api\DashboardUserException;
use App\Repositories\Admin\UserDashboardRepository;
use App\Services\Admin\UserDashboardService;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicVerificationReviewTest extends TestCase
{
    private UserDashboardRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->boolean('is_academically_verified')->default(false);
            $table->timestamp('academically_verified_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_academic_verification_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status');
            $table->unsignedBigInteger('reviewer_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Storage::fake('public');

        $this->repository = app(UserDashboardRepository::class);
    }

    public function test_owner_can_approve_pending_academic_verification_request(): void
    {
        $this->insertUser();
        $this->insertVerificationRequest(Status::PENDING);

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter->expects($this->once())
            ->method('sendToMobile')
            ->with(
                10,
                $this->callback(
                    fn (NotificationPayload $payload): bool => $payload->metadata['type'] === 'academic_verification_approved'
                        && $payload->metadata['params']['verification_request_id'] === 20
                )
            );

        $this->service($notificationCenter)->approveAcademicVerificationRequest(
            ownerId: 1,
            verificationRequestId: 20,
        );

        $request = DB::table('user_academic_verification_requests')->find(20);
        $user = DB::table('users')->find(10);

        $this->assertSame(Status::APPROVED->value, $request->status);
        $this->assertSame(1, $request->reviewer_user_id);
        $this->assertNotNull($request->reviewed_at);
        $this->assertNull($request->rejection_reason);
        $this->assertSame(1, $user->is_academically_verified);
        $this->assertNotNull($user->academically_verified_at);
    }

    public function test_owner_can_reject_pending_request_and_reason_is_stored(): void
    {
        $this->insertUser(isVerified: true, verifiedAt: '2026-01-01 10:00:00');
        $this->insertVerificationRequest(Status::PENDING);

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter->expects($this->once())
            ->method('sendToMobile')
            ->with(
                10,
                $this->callback(
                    fn (NotificationPayload $payload): bool => $payload->metadata['type'] === 'academic_verification_rejected'
                        && $payload->metadata['params']['verification_request_id'] === 20
                )
            );

        $this->service($notificationCenter)->rejectAcademicVerificationRequest(
            ownerId: 1,
            verificationRequestId: 20,
            rejectionReason: '  الوثيقة المرفقة غير واضحة  ',
        );

        $request = DB::table('user_academic_verification_requests')->find(20);
        $user = DB::table('users')->find(10);

        $this->assertSame(Status::REJECTED->value, $request->status);
        $this->assertSame(1, $request->reviewer_user_id);
        $this->assertNotNull($request->reviewed_at);
        $this->assertSame('الوثيقة المرفقة غير واضحة', $request->rejection_reason);
        $this->assertSame(0, $user->is_academically_verified);
        $this->assertNull($user->academically_verified_at);
    }

    #[DataProvider('terminalRequestStates')]
    public function test_terminal_request_cannot_be_reviewed_again(
        string $operation,
        Status $status,
        string $expectedMessage,
    ): void {
        $this->insertUser();
        $this->insertVerificationRequest($status);

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter->expects($this->never())->method('sendToMobile');

        $service = $this->service($notificationCenter);

        $this->expectException(DashboardUserException::class);
        $this->expectExceptionMessage($expectedMessage);

        if ($operation === 'approve') {
            $service->approveAcademicVerificationRequest(1, 20);

            return;
        }

        $service->rejectAcademicVerificationRequest(1, 20, 'سبب رفض صالح');
    }

    public static function terminalRequestStates(): array
    {
        return [
            'approved request cannot be approved' => [
                'approve',
                Status::APPROVED,
                'تمت الموافقة على طلب توثيق المستوى الأكاديمي مسبقاً',
            ],
            'rejected request cannot be approved' => [
                'approve',
                Status::REJECTED,
                'تم رفض طلب توثيق المستوى الأكاديمي مسبقاً',
            ],
            'approved request cannot be rejected' => [
                'reject',
                Status::APPROVED,
                'تمت الموافقة على طلب توثيق المستوى الأكاديمي مسبقاً',
            ],
            'rejected request cannot be rejected' => [
                'reject',
                Status::REJECTED,
                'تم رفض طلب توثيق المستوى الأكاديمي مسبقاً',
            ],
        ];
    }

    private function service(NotificationCenter $notificationCenter): UserDashboardService
    {
        return new UserDashboardService($this->repository, $notificationCenter);
    }

    private function insertUser(bool $isVerified = false, ?string $verifiedAt = null): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'is_academically_verified' => $isVerified,
            'academically_verified_at' => $verifiedAt,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }

    private function insertVerificationRequest(Status $status): void
    {
        DB::table('user_academic_verification_requests')->insert([
            'id' => 20,
            'user_id' => 10,
            'status' => $status->value,
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }
}
