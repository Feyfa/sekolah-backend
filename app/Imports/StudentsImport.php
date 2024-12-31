<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithLimit;

class StudentsImport implements ToCollection, WithHeadingRow, WithLimit
{
    public function __construct(
        public $user_id
    )
    {
        
    }

    public function limit(): int
    {
        return 1000;
    }

    public function collection(Collection $rows)
    {
        $students = [];

        /* VALIDASI APAKAH ADA EMAIL YANG SAMA ATAU TIDAK DI EXCEL LEVEL */
        // Membersihkan dan memvalidasi data
        $rows = $rows->filter(function ($row) {
            // Validasi bahwa setiap row memiliki semua kunci yang diperlukan dan nilainya tidak kosong
            return isset($row['nama']) && isset($row['email']) && isset($row['tanggal_lahir']) && isset($row['jenis_kelamin']) && isset($row['kelas']) &&
                   !empty($row['nama']) && !empty($row['email']) && !empty($row['tanggal_lahir']) && !empty($row['jenis_kelamin']) && !empty($row['kelas']);
        })->map(function ($row) {
            // Memetakan ulang data hanya dengan kolom yang diperlukan
            return [
                'nama' => $row['nama'],
                'email' => $row['email'],
                'tanggal_lahir' => $row['tanggal_lahir'],
                'jenis_kelamin' => $row['jenis_kelamin'],
                'kelas' => $row['kelas'],
            ];
        });

        // Menghilangkan duplikat berdasarkan email
        $rows = $rows->unique('email')->values()->all();
        /* VALIDASI APAKAH ADA EMAIL YANG SAMA ATAU TIDAK DI EXCEL LEVEL */

        /* VALIDASI APAKAH ADA EMAIL YANG SAMA ATAU TIDAK DI DATABASE LEVEL */
        foreach($rows as $row)
        {
            // cek apakah email ini sudah ada di database
            $emailDuplicateInDatabase = Student::where('email', $row['email'])
                                               ->exists();

            if(!$emailDuplicateInDatabase) 
                $students[] = [
                    'user_id' => $this->user_id,
                    'nama' => $row['nama'],
                    'email' => $row['email'],
                    'tanggal_lahir' => $row['tanggal_lahir'],
                    'jenis_kelamin' => $row['jenis_kelamin'],
                    'kelas' => $row['kelas'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
        }

        if(count($students) > 0)
        {
            Student::insert($students);
        }
        /* VALIDASI APAKAH ADA EMAIL YANG SAMA ATAU TIDAK DI DATABASE LEVEL */
    }
}
