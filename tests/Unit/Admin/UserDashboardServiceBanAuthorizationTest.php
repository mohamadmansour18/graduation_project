<?php

namespace Tests\Unit\Admin;

use App\DTOs\Notifications\NotificationPayload;
use App\Enums\SystemRole;
use App\Exceptions\Api\DashboardUserException;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBan;
use App\Repositories\Admin\UserDashboardRepository;
use App\Services\Admin\UserDashboardService;
use App\Services\Notifications\NotificationCenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserDashboardServiceBanAuthorizationTest extends TestCase
{
    #[DataProvider('forbiddenBanRolePairs')]
    public function test_it_rejects_forbidden_ban_role_pairs(
        SystemRole $actorRole,
        SystemRole $targetRole,
        string $expectedMessage,
    ): void {
        $actor = $this->userWithRole(1, $actorRole);
        $target = $this->userWithRole(2, $targetRole);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static fn (callable $callback) => $callback());

        $repository = $this->createMock(UserDashboardRepository::class);
        $repository->expects($this->once())
            ->method('findUserForBanWithLock')
            ->with($target->id)
            ->willReturn($target);
        $repository->expects($this->never())
            ->method('hasActiveBanForUserWithLock');
        $repository->expects($this->never())
            ->method('createUserBan');

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter->expects($this->never())
            ->method('sendToUser');

        $service = new UserDashboardService($repository, $notificationCenter);

        try {
            $service->banUser($actor, $target->id, $this->permanentBanData());
            $this->fail('The forbidden ban operation must be rejected.');
        } catch (DashboardUserException $exception) {
            $this->assertSame(422, $exception->getStatus());
            $this->assertSame('! خطأ تحقق', $exception->getTitle());
            $this->assertSame($expectedMessage, $exception->getMessage());
        }
    }

    #[DataProvider('allowedBanRolePairs')]
    public function test_it_allows_supported_ban_role_pairs(
        SystemRole $actorRole,
        SystemRole $targetRole,
    ): void {
        Storage::fake('public');

        $actor = $this->userWithRole(1, $actorRole);
        $target = $this->userWithRole(2, $targetRole);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(static fn (callable $callback) => $callback());

        $repository = $this->createMock(UserDashboardRepository::class);
        $repository->expects($this->once())
            ->method('findUserForBanWithLock')
            ->with($target->id)
            ->willReturn($target);
        $repository->expects($this->once())
            ->method('hasActiveBanForUserWithLock')
            ->with($target->id)
            ->willReturn(false);
        $repository->expects($this->once())
            ->method('createUserBan')
            ->with($this->callback(
                fn (array $payload): bool => $payload['user_id'] === $target->id
                    && $payload['imposed_by_user_id'] === $actor->id
            ))
            ->willReturn($this->createdBan());

        Log::shouldReceive('channel')
            ->once()
            ->with('audit')
            ->andReturnSelf();
        Log::shouldReceive('info')
            ->once()
            ->with('User banned from dashboard', $this->isType('array'));

        $notificationCenter = $this->createMock(NotificationCenter::class);
        $notificationCenter->expects($this->once())
            ->method('sendToUser')
            ->with(
                $target->id,
                $this->isInstanceOf(NotificationPayload::class)
            );

        $service = new UserDashboardService($repository, $notificationCenter);

        $service->banUser($actor, $target->id, $this->permanentBanData());
    }

    public static function forbiddenBanRolePairs(): array
    {
        return [
            'supervisor cannot ban owner' => [
                SystemRole::Supervisor,
                SystemRole::Owner,
                'لا يمكن حظر مالك التطبيق',
            ],
            'owner cannot ban owner' => [
                SystemRole::Owner,
                SystemRole::Owner,
                'لا يمكن حظر مالك التطبيق',
            ],
            'supervisor cannot ban supervisor' => [
                SystemRole::Supervisor,
                SystemRole::Supervisor,
                'المشرف يستطيع حظر مستخدمي الموبايل فقط، ولا يمكنه حظر مشرف آخر',
            ],
        ];
    }

    public static function allowedBanRolePairs(): array
    {
        return [
            'supervisor can ban mobile user' => [
                SystemRole::Supervisor,
                SystemRole::Mobile_User,
            ],
            'owner can ban mobile user' => [
                SystemRole::Owner,
                SystemRole::Mobile_User,
            ],
            'owner can ban supervisor' => [
                SystemRole::Owner,
                SystemRole::Supervisor,
            ],
        ];
    }

    private function userWithRole(int $userId, SystemRole $systemRole): User
    {
        $role = new Role;
        $role->forceFill([
            'id' => $systemRole === SystemRole::Owner ? 1 : ($systemRole === SystemRole::Supervisor ? 2 : 3),
            'name' => $systemRole,
        ]);

        $user = new User;
        $user->forceFill([
            'id' => $userId,
            'role_id' => $role->id,
        ]);
        $user->setRelation('role', $role);

        return $user;
    }

    private function permanentBanData(): array
    {
        return [
            'is_permanent' => 1,
            'reason' => 'سبب واضح لاختبار صلاحيات الحظر',
        ];
    }

    private function createdBan(): UserBan
    {
        $ban = new UserBan;
        $ban->forceFill(['id' => 1]);

        return $ban;
    }
}
