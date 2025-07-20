<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Validator;

class UsersImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $validator = Validator::make($row, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'company_name' => 'nullable|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return null;
        }

        return new User([
            'name' => $row['name'],
            'email' => $row['email'],
            'company_name' => $row['company_name'],
            'role_id' => $row['role_id'],
            'password' => Hash::make('noahlex123'),
            'userId' => auth()->id(),
        ]);
    }
}
