<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Nama Lengkap');
        $response->assertSee('Email');
        $response->assertSee('Password');
        $response->assertSee('Konfirmasi Password');
    }

    public function test_user_can_register_with_full_name(): void
    {
        $response = $this->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.pending', absolute: false));

        $this->assertDatabaseHas('users', [
            'name' => 'Siti Aminah',
            'email' => 'test@example.com',
        ]);
    }

    public function test_new_user_is_pending_approval(): void
    {
        $this->post('/register', [
            'name' => 'Siti Aminah',
            'email' => 'pending@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'pending@example.com',
            'status' => User::STATUS_PENDING_APPROVAL,
        ]);
    }
}
