<?php

namespace Database\Factories;

use App\Models\Domain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DomainFactory extends Factory
{
    protected $model = Domain::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => $this->faker->domainName(),
            'status' => Domain::STATUS_ACTIVE,
        ];
    }
}
