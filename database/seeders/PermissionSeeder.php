<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('permissions')->insert([
            ['name' => 'view_only', 'label' => 'View Only'],
            ['name' => 'upload_edit', 'label' => 'Upload/Edit'],
            ['name' => 'approve', 'label' => 'Approve'],
            ['name' => 'manage_users', 'label' => 'Manage Users'],
            ['name' => 'setting_access', 'label' => 'Setting Access'],
        ]);
    }
}
