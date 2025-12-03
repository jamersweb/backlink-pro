<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_registration_page()
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
    }

    public function test_user_can_register()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
        
        $this->assertAuthenticated();
    }

    public function test_user_cannot_register_with_invalid_email()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'invalid-email',
        ]);
    }

    public function test_user_cannot_register_with_weak_password()
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $this->assertGuest();
    }
}

