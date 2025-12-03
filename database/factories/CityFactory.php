<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'state_id' => State::factory(),
            'name' => $this->faker->city(),
        ];
    }
}

