<?php

namespace Database\Seeders;

use App\Models\FolderTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FolderTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $main = FolderTemplate::create([
            'name' => 'General',
            'path' => 'General',
            'userId' => 1,
        ]);

        FolderTemplate::create([
            'name' => 'Technical',
            'path' => 'General/Technical',
            'parent_id' => $main->id,
            'userId' => 1,
        ]);

        FolderTemplate::create([
            'name' => 'Finance',
            'path' => 'Finance',
            'userId' => 1,
        ]);

        FolderTemplate::create([
            'name' => 'Legal',
            'path' => 'Legal',
            'userId' => 1,
        ]);
    }
}
