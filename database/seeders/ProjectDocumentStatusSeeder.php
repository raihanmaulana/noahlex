<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ProjectDocumentStatus;

class ProjectDocumentStatusSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['name' => 'Under Review', 'color_hex' => '#FFA500', 'description' => 'Document is awaiting approval'],
            ['name' => 'Approved', 'color_hex' => '#28a745', 'description' => 'Document has been approved'],
            ['name' => 'Rejected', 'color_hex' => '#dc3545', 'description' => 'Document has been rejected'],
        ];

        foreach ($statuses as $status) {
            ProjectDocumentStatus::create($status);
        }
    }
}

