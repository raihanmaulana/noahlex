<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectAssignmentType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProjectAssignmentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $userId = 1; 

        $data = [
            ['name' => 'Users to Projects', 'description' => 'Assign users with roles to projects'],
            ['name' => 'Documents to Projects', 'description' => 'Assign documents into project folders'],
            ['name' => 'Roles to Projects', 'description' => 'Assign role definitions across projects']
        ];

        foreach ($data as $item) {
            ProjectAssignmentType::create([
                'name' => $item['name'],
                'description' => $item['description'],
                'userId' => $userId
            ]);
        }
    }
}
