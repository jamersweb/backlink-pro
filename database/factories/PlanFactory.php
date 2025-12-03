<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true) . ' Plan',
            'slug' => $this->faker->slug(),
            'price' => $this->faker->randomFloat(2, 10, 100),
            'billing_interval' => 'monthly',
            'max_campaigns' => $this->faker->numberBetween(1, 10),
            'max_domains' => $this->faker->numberBetween(1, 5),
            'daily_backlink_limit' => $this->faker->numberBetween(10, 100),
            'backlink_types' => json_encode(['comment', 'profile']),
            'features' => json_encode([]),
            'is_active' => true,
        ];
    }
}
