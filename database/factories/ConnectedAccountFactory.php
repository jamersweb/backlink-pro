<?php

namespace Database\Factories;

use App\Models\ConnectedAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

class ConnectedAccountFactory extends Factory
{
    protected $model = ConnectedAccount::class;

    public function definition(): array
    {
        $rawToken = 'test_token_' . $this->faker->uuid();
        $rawRefresh = 'test_refresh_' . $this->faker->uuid();
        
        return [
            'user_id' => User::factory(),
            'provider' => 'gmail',
            'email' => $this->faker->email(),
            'provider_user_id' => $this->faker->uuid(),
            // Set raw values - the mutator will encrypt them automatically
            'access_token' => $rawToken,
            'refresh_token' => $rawRefresh,
            'expires_at' => now()->addHours(1),
            'status' => ConnectedAccount::STATUS_ACTIVE,
        ];
    }
    
    /**
     * Create account with empty token for testing
     */
    public function withEmptyToken(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'access_token' => '',
                'refresh_token' => '',
            ];
        });
    }
    
    /**
     * Configure the factory to bypass encryption for testing
     */
    public function withoutEncryption(): static
    {
        return $this->state(function (array $attributes) {
            $rawToken = $attributes['access_token'] ?? 'test_token';
            $rawRefresh = $attributes['refresh_token'] ?? 'test_refresh';
            
            return [
                'access_token' => Crypt::encryptString($rawToken),
                'refresh_token' => Crypt::encryptString($rawRefresh),
            ];
        });
    }
}
