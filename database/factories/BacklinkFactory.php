<?php

namespace Database\Factories;

use App\Models\Backlink;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class BacklinkFactory extends Factory
{
    protected $model = Backlink::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'url' => $this->faker->url(),
            'type' => $this->faker->randomElement(['comment', 'profile', 'forum', 'guest']),
            'status' => Backlink::STATUS_SUBMITTED,
            'keyword' => $this->faker->word(),
        ];
    }
}
