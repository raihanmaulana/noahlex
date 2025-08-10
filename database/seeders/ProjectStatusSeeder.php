<?php

namespace Database\Seeders;

use App\Models\ProjectStatus;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProjectStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = ['Accepted', 'Rejected', 'Under Review', 'Expired'];

        foreach ($statuses as $status) {
            ProjectStatus::create([
                'name' => $status,
                'userId' => 1
            ]);
        }
    }
}
