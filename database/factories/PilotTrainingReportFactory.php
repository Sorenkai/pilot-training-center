<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\PilotTrainingReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PilotTrainingReport>
 */
class PilotTrainingReportFactory extends Factory
{
    protected $model = PilotTrainingReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = 'now');

        return [
            'report_date' => $date->format('Y-M-d'),
            'content' => $this->faker->paragraph(),
            'contentimprove' => $this->faker->paragraph(),
            'lesson_id' => Lesson::inRandomOrder()->first()->id,
            'draft' => $this->faker->numberBetween(0, 1),
            'created_at' => $date,
            'updated_at' => $date,
        ];
    }
}
