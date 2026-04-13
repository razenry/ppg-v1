<?php

namespace Database\Factories;

use App\Models\Node;
use App\Models\Server;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Server>
 */
class ServerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => 'Server '.$this->faker->words(2, true),
            'identifier' => $this->faker->unique()->slug(2),
            'src_ip' => $this->faker->ipv4(),
            'src_port' => $this->faker->numberBetween(1024, 65535),
            'dest_ip' => $this->faker->optional(0.7)->ipv4(),
            'dest_port' => $this->faker->numberBetween(1024, 65535),
            'status' => $this->faker->randomElement(['pending', 'active', 'active', 'failed']),
            'user_id' => User::factory(),
            'node_id' => Node::factory(),
            'subscription_id' => Subscription::factory(),
        ];
    }
}
