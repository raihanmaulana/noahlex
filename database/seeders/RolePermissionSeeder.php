<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('role_permission')->insert([
            [
                'role_id' => 1,
                'permission_id' => 1, // view_only
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'permission_id' => 2, // upload_edit
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'permission_id' => 3, // approve
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'permission_id' => 4, // manage_users
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_id' => 1,
                'permission_id' => 5, // setting_access
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
