<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            'Feasibility',
            'Development',
            'Procurement',
            'Construction',
            'Commissioning',
            'Completed'
        ];

        foreach ($stages as $stage) {
            DB::table('project_stages')->insert([
                'name' => $stage,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
    }
}
