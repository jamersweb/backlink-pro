<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Domain;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'domain_id' => Domain::factory(),
            'name' => $this->faker->words(3, true) . ' Campaign',
            'web_name' => $this->faker->company(),
            'web_url' => $this->faker->url(),
            'web_keyword' => $this->faker->word(),
            'web_about' => $this->faker->paragraph(),
            'web_target' => json_encode([$this->faker->url(), $this->faker->url()]),
            'company_name' => $this->faker->company(),
            'company_email_address' => $this->faker->email(),
            'company_logo' => $this->faker->imageUrl(),
            'company_address' => $this->faker->address(),
            'company_country' => $this->faker->countryCode(),
            'company_state' => $this->faker->state(),
            'company_city' => $this->faker->city(),
            'company_number' => $this->faker->phoneNumber(),
            'gmail' => $this->faker->email(),
            'password' => $this->faker->password(),
            'status' => Campaign::STATUS_ACTIVE,
            'settings' => json_encode([
                'backlink_types' => ['comment', 'profile'],
                'daily_limit' => 10,
                'total_limit' => 100,
            ]),
        ];
    }
}
