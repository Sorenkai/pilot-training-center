<?php

namespace Database\Factories;

use App\Models\PilotTraining;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PilotTrainingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PilotTraining::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $status = $this->faker->numberBetween(-1, 3);
        $started_at = null;
        $closed_at = null;

        if ($status > 1) {
            $started_at = $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '-1 months');
        } elseif ($status == -1) {
            $started_at = $this->faker->dateTimeBetween($startDate = '-1 years', $endDate = '-1 months');
            $closed_at = $this->faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now');
        }

        return [
            'user_id' => User::inRandomOrder()->first()->id,
            'status' => $status,
            'english_only_training' => false,
            'experience' => $this->faker->numberBetween(1, 3),
            'comment' => $this->faker->paragraph(15, false),
            'created_by' => User::inRandomOrder()->first()->id,
            'created_at' => $this->faker->dateTimeBetween($startDate = '-1 year', $endDate = 'now'),
            'updated_at' => \Carbon\Carbon::now(),
            'started_at' => $started_at,
            'closed_at' => $closed_at,
        ];
    }
}
