<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = array_map(
            fn(SystemRole $role) => [
                'name' => $role->value,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            SystemRole::cases()
        );

        Role::query()->upsert($roles , ['name'] , ['updated_at']);
    }
}
