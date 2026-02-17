<?php

namespace Tests\Feature;

use App\User;
use App\Models\Client;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class EmployeeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed('RolesAndPermissionsTablesSeeder');
    }

    /**
     * Show all employees.
     *
     * @return void
     */
    public function testShowEmployees()
    {
        $employees = factory(Employee::class, 50)->create();

        $user = $employees->first()->user->assignRole('admin');

        $response = $this->actingAs($user, 'api')->getJson(route('employees.index'));

        $response
            ->assertJsonStructure([
                'data',
                'meta',
                'roles',
                'can',
            ])
            ->assertSuccessful();
    }

    /**
     * Show all employees using an unauthorized user.
     *
     * @return void
     */
    public function testShowEmployeesUsingUnauthorizedUser()
    {
        $employees = factory(Employee::class, 50)->create();

        $user = $employees->first()->user;

        $response = $this->actingAs($user, 'api')->getJson(route('employees.index'));

        $response->assertForbidden();
    }

    /**
     * Create a new employee.
     *
     * @return void
     */
    public function testCreateEmployee()
    {
        $user = factory(User::class)->states('employee')->create()->givePermissionTo('create employees');

        $response = $this->actingAs($user, 'api')->postJson(route('employees.store'), [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'j.doe',
            'email' => 'j.doe@example.com',
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'firstName' => 'John',
                'lastName' => 'Doe',
                'username' => 'j.doe',
                'email' => 'j.doe@example.com',
            ]);

        $this->assertDatabaseHas('employees', [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ])->assertDatabaseHas('users', [
            'username' => 'j.doe',
            'email' => 'j.doe@example.com',
        ]);
    }

    /**
     * Create a new employee using an unauthorized user.
     *
     * @return void
     */
    public function testCreateEmployeeUsingUnauthorizedUser()
    {
        $user = factory(User::class)->states('employee')->create();

        $response = $this->actingAs($user, 'api')->postJson(route('employees.store'), [
            'firstName' => 'John',
            'lastName' => 'Doe',
            'username' => 'j.doe',
            'email' => 'j.doe@example.com',

        ]);

        $response->assertForbidden();
    }

    /**
     * Create a new employee with invalid data.
     *
     * @return void
     */
    public function testCreateEmployeeValidation()
    {
        $user = factory(User::class)->states('employee')->create()->givePermissionTo('create employees');

        $response = $this->actingAs($user, 'api')->postJson(route('employees.store'), [
            'firstName' => 'John',
            'lastName' => '',
            'username' => 'j.doe',
            'email' => 'j.doeexample.com',
        ]);

        $response->assertJsonValidationErrors([
            'lastName',
            'email',
        ]);
    }

    /**
     * Show a specific employee.
     *
     * @return void
     */
    public function testShowEmployee()
    {
        $employees = factory(Employee::class, 50)->create();

        $user = $employees->get(10)->user->assignRole('admin');

        $response = $this->actingAs($user, 'api')->getJson(route('employees.show', $employees->get(25)));

        $response->assertOk();
    }

    /**
     * Show a specific employee using an unauthorized user.
     *
     * @return void
     */
    public function testShowEmployeeUsingUnauthorizedUser()
    {
        $employees = factory(Employee::class, 50)->create();

        $user = $employees->get(10)->user;

        $response = $this->actingAs($user, 'api')->getJson(route('employees.show', $employees->get(25)));

        $response->assertForbidden();
    }

    /**
     * Delete a employee.
     *
     * @return void
     */
    public function testDeleteEmployee()
    {
        $user = factory(User::class)->states('employee')->create()->givePermissionTo('delete employees');

        $employee = factory(Employee::class)->create();

        $response = $this->actingAs($user, 'api')->deleteJson(route('employees.destroy', $employee));

        $response->assertNoContent();
    }

    /**
     * Delete a employee using an unauthorized user.
     *
     * @return void
     */
    public function testDeleteEmployeeUsingUnauthorizedUser()
    {
        $user = factory(User::class)->states('employee')->create();

        $employee = factory(Employee::class)->create();

        $response = $this->actingAs($user, 'api')->deleteJson(route('employees.destroy', $employee));

        $response->assertForbidden();
    }
}
