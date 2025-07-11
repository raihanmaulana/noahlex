<?php

namespace Database\Seeders;

use App\Models\ProjectType;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProjectTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = ['Solar', 'Wind', 'Hydro', 'Geothermal', 'Hybrid', 'Data Center Project'];

        foreach ($types as $type) {
            ProjectType::create([
                'name' => $type,
                'userId' => 1
            ]);
        }
    }
}
