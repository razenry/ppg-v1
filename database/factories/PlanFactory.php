<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->randomElement(['Starter', 'Basic', 'Standard', 'Pro', 'Enterprise', 'Ultimate']),
            'max_server' => $this->faker->numberBetween(1, 25),
        ];
    }
}
