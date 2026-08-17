<?php

namespace Tests\Feature\Auth;

use App\Enums\BanType;
use App\Repositories\Auth\AuthRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthRepositoryBanCheckTest extends TestCase
{
    private AuthRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');

        Schema::create('user_bans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ban_type');
            $table->text('reason');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('lifted_by_user_id')->nullable();
            $table->timestamp('lifted_at')->nullable();
            $table->timestamps();
        });

        $this->repository = app(AuthRepository::class);
    }

    public function test_it_finds_an_active_permanent_ban(): void
    {
        $banId = $this->insertBan([
            'ban_type' => BanType::Permanent->value,
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ]);

        $activeBan = $this->repository->getActiveBanForUser(123);

        $this->assertNotNull($activeBan);
        $this->assertSame($banId, $activeBan->id);
    }

    public function test_it_finds_an_active_temporary_ban(): void
    {
        $banId = $this->insertBan([
            'ban_type' => BanType::Temporary->value,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addDay(),
        ]);

        $activeBan = $this->repository->getActiveBanForUser(123);

        $this->assertNotNull($activeBan);
        $this->assertSame($banId, $activeBan->id);
    }

    public function test_it_ignores_a_ban_scheduled_for_the_future(): void
    {
        $this->insertBan([
            'ban_type' => BanType::Temporary->value,
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDays(2),
        ]);

        $this->assertNull($this->repository->getActiveBanForUser(123));
    }

    public function test_it_ignores_expired_and_lifted_bans(): void
    {
        $this->insertBan([
            'ban_type' => BanType::Temporary->value,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->subDay(),
        ]);

        $this->insertBan([
            'ban_type' => BanType::Permanent->value,
            'starts_at' => now()->subDay(),
            'ends_at' => null,
            'lifted_at' => now()->subMinute(),
        ]);

        $this->assertNull($this->repository->getActiveBanForUser(123));
    }

    private function insertBan(array $attributes): int
    {
        return DB::table('user_bans')->insertGetId(array_merge([
            'user_id' => 123,
            'reason' => 'Security policy violation.',
            'lifted_by_user_id' => null,
            'lifted_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
