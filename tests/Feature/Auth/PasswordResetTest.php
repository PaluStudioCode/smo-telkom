<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    public function test_reset_password_link_screen_is_disabled(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertNotFound();
    }

    public function test_reset_password_link_request_is_disabled(): void
    {
        $response = $this->post('/forgot-password', ['email' => 'user@example.com']);

        $response->assertNotFound();
    }

    public function test_reset_password_screen_is_disabled(): void
    {
        $response = $this->get('/reset-password/token');

        $response->assertNotFound();
    }

    public function test_password_reset_submission_is_disabled(): void
    {
        $response = $this->post('/reset-password', [
            'token' => 'token',
            'email' => 'user@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertNotFound();
    }
}
