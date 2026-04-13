<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => 'sub_'.str()->random(12),
            'plan_name' => $this->faker->randomElement(['Basic', 'Pro', 'Enterprise']),
            'max_server' => $this->faker->numberBetween(5, 50),
            'status' => $this->faker->randomElement(['active', 'active', 'suspended', 'terminated']),
            'user_id' => User::factory(),
        ];
    }
}
