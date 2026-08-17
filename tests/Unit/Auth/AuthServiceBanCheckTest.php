<?php

namespace Tests\Unit\Auth;

use App\Enums\BanType;
use App\Exceptions\Api\AuthenticationException;
use App\Models\User;
use App\Models\UserBan;
use App\Repositories\Auth\AuthRepository;
use App\Services\Auth\AuthService;
use App\Services\Notifications\FcmTokenService;
use Mockery;
use Tests\TestCase;

class AuthServiceBanCheckTest extends TestCase
{
    public function test_it_allows_a_user_without_an_active_ban(): void
    {
        $user = $this->user();
        $repository = Mockery::mock(AuthRepository::class);
        $repository->shouldReceive('getActiveBanForUser')
            ->once()
            ->with(123)
            ->andReturnNull();

        $service = new AuthService(
            $repository,
            Mockery::mock(FcmTokenService::class)
        );

        $service->assertUserIsNotBanned($user);

        $this->assertTrue(true);
    }

    public function test_it_rejects_a_user_with_an_active_permanent_ban(): void
    {
        $user = $this->user();
        $ban = new UserBan;
        $ban->forceFill([
            'ban_type' => BanType::Permanent,
            'reason' => 'Permanent security ban.',
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ]);

        $repository = Mockery::mock(AuthRepository::class);
        $repository->shouldReceive('getActiveBanForUser')
            ->once()
            ->with(123)
            ->andReturn($ban);

        $service = new AuthService(
            $repository,
            Mockery::mock(FcmTokenService::class)
        );

        try {
            $service->assertUserIsNotBanned($user);
            $this->fail('A permanently banned user was allowed to continue.');
        } catch (AuthenticationException $exception) {
            $this->assertSame(403, $exception->getStatus());
            $this->assertSame('Permanent security ban.', $exception->getContext()['reason']);
            $this->assertTrue($exception->getContext()['is_permanent']);
        }
    }

    public function test_it_rejects_a_user_with_an_active_temporary_ban(): void
    {
        $user = $this->user();
        $ban = new UserBan;
        $ban->forceFill([
            'ban_type' => BanType::Temporary,
            'reason' => 'Temporary security ban.',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);

        $repository = Mockery::mock(AuthRepository::class);
        $repository->shouldReceive('getActiveBanForUser')
            ->once()
            ->with(123)
            ->andReturn($ban);

        $service = new AuthService(
            $repository,
            Mockery::mock(FcmTokenService::class)
        );

        try {
            $service->assertUserIsNotBanned($user);
            $this->fail('A temporarily banned user was allowed to continue.');
        } catch (AuthenticationException $exception) {
            $this->assertSame(403, $exception->getStatus());
            $this->assertSame('Temporary security ban.', $exception->getContext()['reason']);
            $this->assertFalse($exception->getContext()['is_permanent']);
            $this->assertNotEmpty($exception->getContext()['ends_at']);
        }
    }

    private function user(): User
    {
        $user = new User;
        $user->forceFill([
            'id' => 123,
            'email' => 'blocked-user@example.com',
        ]);

        return $user;
    }
}
