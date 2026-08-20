<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Enums\Governorate;
use App\Enums\SystemRole;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DashboardUsersSeeder extends Seeder
{
    private const string USER_EMAIL_DOMAIN = 'gmail.com';
    private const string SHARED_PASSWORD = 'Password@123';

    public function run(): void
    {
        DB::transaction(function (): void {
            $now = CarbonImmutable::now();

            $this->ensureDashboardRoles($now);

            $roleIds = DB::table('roles')
                ->whereIn('name', [
                    SystemRole::Supervisor->value,
                    SystemRole::Owner->value,
                ])
                ->pluck('id', 'name');

            if (! $roleIds->has(SystemRole::Supervisor->value) || ! $roleIds->has(SystemRole::Owner->value)) {
                throw new RuntimeException('تعذر العثور على أدوار supervisor و owner.');
            }

            $users = $this->dashboardUsers();
            $passwordHash = Hash::make(self::SHARED_PASSWORD);

            DB::table('users')->upsert(
                array_map(fn(array $user): array => [
                    'role_id' => (int) $roleIds[$user['role']->value],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => $passwordHash,
                    'email_verified_at' => $now,
                    'onboarding_completed_at' => null,
                    'last_login_at' => null,
                    'gender' => $user['gender']->value,
                    'is_academically_verified' => false,
                    'academically_verified_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $users),
                ['email'],
                [
                    'role_id',
                    'name',
                    'password',
                    'email_verified_at',
                    'onboarding_completed_at',
                    'last_login_at',
                    'gender',
                    'is_academically_verified',
                    'academically_verified_at',
                    'updated_at',
                ],
            );

            $usersByEmail = DB::table('users')
                ->select(['id', 'email'])
                ->whereIn('email', array_column($users, 'email'))
                ->get()
                ->keyBy('email');

            $profileRows = [];

            foreach ($users as $user) {
                $persistedUser = $usersByEmail->get($user['email']);

                if ($persistedUser === null) {
                    throw new RuntimeException("تعذر العثور على المستخدم {$user['email']} بعد إنشائه.");
                }

                $profileRows[] = [
                    'user_id' => (int) $persistedUser->id,
                    'phone' => $user['phone'],
                    'birth_date' => null,
                    'avatar_disk' => null,
                    'avatar_path' => null,
                    'cover_disk' => null,
                    'cover_path' => null,
                    'profile_slug' => null,
                    'governorate' => $user['governorate']->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('user_profile')->upsert(
                $profileRows,
                ['user_id'],
                [
                    'phone',
                    'birth_date',
                    'avatar_disk',
                    'avatar_path',
                    'cover_disk',
                    'cover_path',
                    'profile_slug',
                    'governorate',
                    'updated_at',
                ],
            );
        });
    }

    private function ensureDashboardRoles(CarbonImmutable $now): void
    {
        DB::table('roles')->upsert(
            [
                [
                    'name' => SystemRole::Supervisor->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => SystemRole::Owner->value,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            ['name'],
            ['updated_at'],
        );
    }

    private function dashboardUsers(): array
    {
        return [
            [
                'role' => SystemRole::Supervisor,
                'name' => 'عبيد الله الرفاعي',
                'email' => 'obaidallahalrifaie@' . self::USER_EMAIL_DOMAIN,
                'gender' => Gender::Male,
                'phone' => '0959196261',
                'governorate' => Governorate::Damascus,
//                'profile_slug' => 'dashboard-supervisor-01',
            ],
            [
                'role' => SystemRole::Owner,
                'name' => 'عبد الهادي بغدادي',
                'email' => 'obadawork912@gmail.com',
                'gender' => Gender::Male,
                'phone' => '0922000001',
                'governorate' => Governorate::Damascus,
//                'profile_slug' => 'dashboard-owner-01',
            ],
        ];
    }
}
