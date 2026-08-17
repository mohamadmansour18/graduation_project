<?php

namespace Tests\Feature\Auth;

use App\Exceptions\Api\AuthenticationException;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class BannedUserJwtAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['force.json', 'request.id', 'jwt.auth.api'])
            ->get('/api/_tests/jwt-protected', static fn () => response()->json(['allowed' => true]));
    }

    public function test_jwt_middleware_allows_a_user_without_an_active_ban(): void
    {
        $user = $this->user();
        $authService = Mockery::mock(AuthService::class);
        $authService->shouldReceive('assertUserIsNotBanned')
            ->once()
            ->with($user);
        $this->app->instance(AuthService::class, $authService);

        JWTAuth::shouldReceive('setToken')
            ->once()
            ->with('valid-token')
            ->andReturnSelf();
        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturn($user);

        $this->withToken('valid-token')
            ->getJson('/api/_tests/jwt-protected')
            ->assertOk()
            ->assertJson(['allowed' => true]);
    }

    public function test_jwt_middleware_rejects_a_user_with_an_active_ban(): void
    {
        $user = $this->user();
        $authService = Mockery::mock(AuthService::class);
        $authService->shouldReceive('assertUserIsNotBanned')
            ->once()
            ->with($user)
            ->andThrow($this->bannedException());
        $this->app->instance(AuthService::class, $authService);

        JWTAuth::shouldReceive('setToken')
            ->once()
            ->with('blocked-token')
            ->andReturnSelf();
        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturn($user);

        $this->withToken('blocked-token')
            ->getJson('/api/_tests/jwt-protected')
            ->assertForbidden()
            ->assertJsonPath('title', '! الحساب محظور')
            ->assertJsonPath('meta.reason', 'Security policy violation.')
            ->assertJsonPath('meta.is_permanent', true);
    }

    public function test_refresh_rejects_a_user_with_an_active_ban(): void
    {
        $user = $this->user();
        $authService = Mockery::mock(AuthService::class);
        $authService->shouldReceive('assertUserIsNotBanned')
            ->once()
            ->with($user)
            ->andThrow($this->bannedException());
        $this->app->instance(AuthService::class, $authService);

        JWTAuth::shouldReceive('setToken')
            ->once()
            ->with('refresh-token')
            ->andReturnSelf();
        JWTAuth::shouldReceive('refresh')
            ->once()
            ->andReturn('new-access-token');
        JWTAuth::shouldReceive('setToken')
            ->once()
            ->with('new-access-token')
            ->andReturnSelf();
        JWTAuth::shouldReceive('authenticate')
            ->once()
            ->andReturn($user);

        $this->withToken('refresh-token')
            ->postJson('/api/v1/refresh')
            ->assertForbidden()
            ->assertJsonPath('title', '! الحساب محظور')
            ->assertJsonPath('meta.reason', 'Security policy violation.')
            ->assertJsonPath('meta.is_permanent', true);
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

    private function bannedException(): AuthenticationException
    {
        return AuthenticationException::accountBanned(
            reason: 'Security policy violation.',
            startsAt: now()->subMinute()->toDateTimeString(),
            endsAt: null,
            isPermanent: true
        );
    }
}
