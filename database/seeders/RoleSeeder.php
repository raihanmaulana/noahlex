<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = [
            'Project Manager',
            'Document Controller',
            'Construction Manager / Site Lead',
            'Engineer (Civ / Electrical / Structural)',
            'EPC Representative',
            'Developer / Owner Representative',
            'Vendor / Subcontractor',
            'Quality or Commissioning Engineer',
            'CRM Team Member (Post-COD)',
            'Viewer',
        ];

        foreach ($roles as $role) {
            Role::create([
                'name' => $role,
                'userId' => 1,
            ]);
        }
    }
}
