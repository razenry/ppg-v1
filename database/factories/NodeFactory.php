<?php

namespace Database\Factories;

use App\Models\Node;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Node>
 */
class NodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'node-'.$this->faker->unique()->numberBetween(1, 99),
            'label' => $this->faker->city().' Cluster',
            'description' => $this->faker->sentence(),
            'api_url' => $this->faker->url(),
            'api_token' => str()->random(32),
        ];
    }
}
