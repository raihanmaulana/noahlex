<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class UserPreviewImport implements ToCollection, WithHeadingRow
{
    public $validRows = [];
    public $invalidRows = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $data = $row->toArray();

            $validator = Validator::make($data, [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'company_name' => 'nullable|string|max:255',
                'role_id' => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                $this->invalidRows[] = [
                    'row' => $index + 2, // +2 untuk header & human-readable index
                    'data' => $data,
                    'errors' => $validator->errors()->all()
                ];
            } else {
                $this->validRows[] = $data;
            }
        }
    }
}
