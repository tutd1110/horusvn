<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $date = Carbon::now();
        return [
            'name' => $this->faker->sentence(),
            'code' => $this->faker->sentence(),
            'start_time' => $date,
            'end_time' => $date->addDays(rand(1, 100)),
            'time' => rand(1, 15),
            'description' => $this->faker->text(),
            'priority' => rand(1, 10),
            'sticker_id' => rand(1, 5),
            'department_id' => rand(1, 9),
            'weight' => rand(1, 10),
            'project_id' => rand(1, 10),
            'task_parent' => rand(1, 10),
            'user_id' => rand(1, 40),
            'status' => rand(1, 9),
            'real_start_time' => $date,
            'real_end_time' => $date->addDays(rand(1, 5)),
            'time_pause' => rand(1, 10),
            'real_time' => rand(1, 10),
            'progress' => rand(1, 100),
            'root_parent' => rand(1, 10)
        ];
    }
}
