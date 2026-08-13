<?php

namespace Tests\Feature\Admin;

use App\Exceptions\Api\DashboardUserException;
use App\Repositories\Admin\UserDashboardRepository;
use App\Support\DashboardUsersCursor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserDashboardRepositoryTest extends TestCase
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

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->string('gender');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('user_profile', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('phone')->nullable();
            $table->string('avatar_disk')->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('governorate')->nullable();
        });

        Schema::create('user_bans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('lifted_at')->nullable();
        });

        DB::table('roles')->insert([
            'id' => 1,
            'name' => 'mobile_user',
        ]);

        $this->insertMobileUser(1, 'لديه محافظة');
        $this->insertMobileUser(2, 'لا يملك محافظة أول');
        $this->insertMobileUser(3, 'لا يملك محافظة ثان');

        DB::table('user_profile')->insert([
            'user_id' => 1,
            'governorate' => 'حمص',
        ]);

        $this->repository = app(UserDashboardRepository::class);
    }

    public function test_governorate_cursor_pagination_keeps_unknown_governorates_last(): void
    {
        $firstPage = $this->repository->paginateUsersForDashboard(
            type: 'mobile_users',
            sortBy: 'governorate',
            perPage: 1,
        );

        $this->assertSame(1, $firstPage->items()[0]->id);

        $secondPage = $this->repository->paginateUsersForDashboard(
            type: 'mobile_users',
            sortBy: 'governorate',
            perPage: 1,
            cursor: $firstPage->nextCursor(),
        );

        $this->assertSame(2, $secondPage->items()[0]->id);
    }

    public function test_dashboard_users_cursor_cannot_be_reused_with_another_filter_or_sort(): void
    {
        $page = $this->repository->paginateUsersForDashboard(
            type: 'mobile_users',
            sortBy: 'governorate',
            perPage: 1,
        );

        $encodedCursor = DashboardUsersCursor::encode(
            $page->nextCursor(),
            'mobile_users',
            'governorate',
        );

        $decodedCursor = DashboardUsersCursor::decode(
            $encodedCursor,
            'mobile_users',
            'governorate',
        );

        $this->assertSame($page->nextCursor()?->toArray(), $decodedCursor?->toArray());

        try {
            DashboardUsersCursor::decode($encodedCursor, 'supervisors', 'governorate');
            $this->fail('The cursor must not be valid for another user type.');
        } catch (DashboardUserException $exception) {
            $this->assertSame(422, $exception->getStatus());
        }

        try {
            DashboardUsersCursor::decode($encodedCursor, 'mobile_users', 'name');
            $this->fail('The cursor must not be valid for another sort option.');
        } catch (DashboardUserException $exception) {
            $this->assertSame(422, $exception->getStatus());
        }
    }

    private function insertMobileUser(int $id, string $name): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'role_id' => 1,
            'name' => $name,
            'email' => "user{$id}@example.test",
            'password' => 'password',
            'gender' => 'ذكر',
            'email_verified_at' => '2026-01-01 00:00:00',
            'onboarding_completed_at' => '2026-01-01 00:00:00',
            'created_at' => '2026-01-01 00:00:00',
            'updated_at' => '2026-01-01 00:00:00',
        ]);
    }
}
