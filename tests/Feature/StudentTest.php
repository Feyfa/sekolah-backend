<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class StudentTest extends TestCase
{
    /**
     * test ini digunakan untuk mendapatkan semua data students
     */
    public function testIndex()
    {
        $response = $this->get('/api/students')
                         ->json();
        
        // digunakan untuk mengecek apakah status bernilai 200
        $this->assertEquals(200, $response['status']);
        // digunakna untuk mengecek apakah data itu berupa array
        $this->assertIsArray($response['students']['data']);
    }

    /**
     * test ini digunakan untuk mendapatkan data students, bedasarkan page dan keyword
     */
    public function testIndexParamsPageKeyword()
    {
        $response = $this->get('/api/students?page=1&keyword=i')
                         ->json();

        // digunakan untuk mengecek apakah status bernilai 200
        $this->assertEquals(200, $response['status']);
        // digunakna untuk mengecek apakah data itu berupa array
        $this->assertIsArray($response['students']['data']);

        dd($response);
    }

    /**
     * test ini digunakan untuk menambahkan data student
     */
    public function testStore()
    {
        $student = [
            'nama' => 'Bima Ramadhan',
            'email' => 'bima+contoh@gmail.com',
            'tanggal_lahir' => '2005-06-20',
            'jenis_kelamin' => 'Laki-Laki',
            'kelas' => 'Empat'
        ];

        $this->post('/api/students', $student)
             ->assertStatus(200)
             ->assertJson([
                'status' => 200, 
                'message' => 'Student Add Successfully'
             ]);
    }

    /**
     * test ini digunakan untuk mendapatkan student bedasarkan id
     */
    public function testShow()
    {
        $response = $this->get('/api/students/441')
                         ->json();

        // digunakan untuk mengecek apakah status bernilai 200
        $this->assertEquals(200, $response['status']);
        // digunakna untuk mengecek apakah data itu berupa array
        $this->assertIsArray($response['student']);
    }

    /**
     * test ini digunakan untuk mendapatkan student bedasarkan id yang tidak ada di database
     */
    public function testShowIdNotFound()
    {
        $this->get('/api/students/1111')
             ->assertStatus(404)
             ->assertJson([
                "status" => 404,
                "message" => "Student Not Fond"
             ]);
    }

    /**
     * test ini digunakan untuk update student
     */
    public function testUpdate()
    {
        $student = [
            'nama' => 'Grandis Tenar',
            'email' => 'grandis@gmail.com',
            'tanggal_lahir' => '2005-11-15',
            'jenis_kelamin' => 'Perempuan',
            'kelas' => 'Enam'
        ];

        $this->put('/api/students/439', $student)
             ->assertStatus(200)
             ->assertJson([
                "status" => 200,
                "message" => "Student Update Successfully"
             ]);
    }

    /**
     * test ini digunakan untuk update student, tetapi id nya tidak terdaftar di database
     */
    public function testUpdateIdNotFound()
    {
        $student = [
            'nama' => 'Grandis Tenar',
            'email' => 'grandis@gmail.com',
            'tanggal_lahir' => '2005-11-15',
            'jenis_kelamin' => 'Perempuan',
            'kelas' => 'Enam'
        ];

        $this->put('/api/students/1111', $student)
             ->assertStatus(404)
             ->assertJson([
                "status" => 404,
                "message" => "Student Not Fond"
             ]);
    }

    /**
     * test ini digunakan untuk update murid, tetapi saat melakukan perubahan nama, namanya kosong
     */
    public function testUpdateNameNotRequired()
    {
        $student = [
            'email' => 'grandis@gmail.com',
            'tanggal_lahir' => '2005-11-15',
            'jenis_kelamin' => 'Perempuan',
            'kelas' => 'Enam'
        ];

        $this->put('/api/students/1', $student)
             ->assertStatus(404);
    }

    /**
     * test ini digunakan untuk menghapus student, bedasarkan id nya
     */
    public function testDelete()
    {
        $this->delete('/api/students/441')
             ->assertStatus(200)
             ->assertJson([
                "status" => 200,
                "message" => "Student Delete Successfully"
             ]);
    }

    /**
     * test ini digunakan untuk menghapus student, tetapi id nya tidak terdaftar di database
     */
    public function testDeleteIdNotFound()
    {
        $this->delete('/api/students/1111')
        ->assertStatus(404)
        ->assertJson([
           "status" => 404,
           "message" => "Student Not Fond",
        ]);
    }
}
