<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'nama' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'tanggal_lahir' => $this->faker->date(),
            'jenis_kelamin' => $this->faker->randomElement(['Laki-Laki', 'Perempuan']),
            'kelas' => $this->faker->randomElement(['Sepuluh (10)', 'Sebelas (11)', 'Dua Belas (12)']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
