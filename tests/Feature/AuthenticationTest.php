<?php

namespace Tests\Feature;

use App\User;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Show login form.
     *
     * @return void
     */
    public function testGuestCanSeeLoginForm()
    {
        $response = $this->get(route('login'));

        $response
            ->assertSuccessful()
            ->assertViewIs('auth.login');
    }

    /**
     * Authenticated user must not see login form.
     *
     * @return void
     */
    public function testUserCannotSeeLoginForm()
    {
        $user = factory(User::class)->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirect(route('account', ['uri' => '/']));
    }

    /**
     * Login a client.
     *
     * @return void
     */
    public function testLoginRedirectClient()
    {
        $client = factory(Client::class)->create();

        $response = $this->actingAs($client->user)->get(route('login'));

        $response->assertRedirect(route('account', ['uri' => '/']));

        $this->assertAuthenticatedAs($client->user);
    }

    /**
     * Login an employee.
     *
     * @return void
     */
    public function testLoginRedirectEmployee()
    {
        $employee = factory(Employee::class)->create();

        $response = $this->actingAs($employee->user)->get(route('login'));

        $response->assertRedirect(route('dashboard', ['uri' => '/']));
    }
}
