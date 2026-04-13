<?php

namespace Database\Seeders;

use App\Models\Node;
use App\Models\Plan;
use App\Models\Server;
use App\Models\Setting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ]
        );

        // 2. Create Plans
        $plans = [
            ['name' => 'Starter', 'max_server' => 1],
            ['name' => 'Basic', 'max_server' => 3],
            ['name' => 'Pro', 'max_server' => 10],
            ['name' => 'Enterprise', 'max_server' => 50],
        ];

        foreach ($plans as $planData) {
            Plan::updateOrCreate(['name' => $planData['name']], $planData);
        }

        // 3. Create Nodes
        $nodes = collect([
            ['name' => 'sg-01', 'label' => 'Singapore (DigitalOcean)', 'description' => 'Primary SE Asia node'],
            ['name' => 'us-east-01', 'label' => 'New York (Vultr)', 'description' => 'East US node'],
            ['name' => 'eu-west-01', 'label' => 'London (Linode)', 'description' => 'EMEA node'],
            ['name' => 'id-01', 'label' => 'Jakarta (Biznet)', 'description' => 'Local Indonesia node'],
        ])->map(function ($nodeData) {
            return Node::updateOrCreate(
                ['name' => $nodeData['name']],
                array_merge($nodeData, [
                    'api_url' => 'https://api.'.$nodeData['name'].'.raznar.hosting',
                    'api_token' => str()->random(32),
                ])
            );
        });

        // 4. Create Users with Subscriptions and Servers
        User::factory(10)->create()->each(function ($user) use ($nodes) {
            // Create 1-2 subscriptions for each user
            $subs = Subscription::factory(rand(1, 2))->create([
                'user_id' => $user->id,
                'status' => 'active',
            ]);

            foreach ($subs as $sub) {
                // Create random servers for each subscription
                $serverCount = rand(2, min($sub->max_server, 5));
                Server::factory($serverCount)->create([
                    'user_id' => $user->id,
                    'subscription_id' => $sub->id,
                    'node_id' => $nodes->random()->id,
                ]);
            }
        });

        // 5. Default Settings (using the helper method)
        Setting::set('sso_ttl', 120);
        Setting::set('cloudflare', [
            'domain' => 'proxy.raznar.id',
            'api_key' => 'fake_cf_key_123456789',
            'email' => 'tech@raznar.id',
            'zone_id' => 'fake_zone_id_abcdef',
        ]);

        $this->command->info('Seeding completed successfully!');
        $this->command->info('Admin login: admin@admin.com / password');
    }
}
