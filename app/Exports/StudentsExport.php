<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentsExport implements FromCollection, WithHeadings
{
    public function __construct(
        public $user_id
    ) 
    {

    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Student::where('user_id', $this->user_id)
                      ->get();
    }

    public function headings(): array
    {
        return [
            'id',
            'user_id',
            'nama',
            'email',
            'tanggal_lahir',
            'jenis_kelamin',
            'kelas',
            'created_at',
            'updated_at',
        ];
    }
}
