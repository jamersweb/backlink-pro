<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name' => $this->faker->state(),
        ];
    }
}

