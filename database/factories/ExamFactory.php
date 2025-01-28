<?php

namespace Database\Factories;

use App\Models\Exam;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Exam>
 */
class ExamFactory extends Factory
{
    protected $model = Exam::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now');

        return [
            'type' => 'PRACTICAL',
            'result' => $this->faker->randomElement(['PASS', 'FAIL', 'PARTIAL PASS']),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
