<?php

namespace Tests\Unit\Models;

use App\Models\Employee;
use App\Models\Role;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->store = Store::factory()->create();
        $this->role = Role::factory()->create();
    }

    /** @test */
    public function employee_implements_jwt_subject()
    {
        $employee = Employee::factory()->create();

        $this->assertInstanceOf(\Tymon\JWTAuth\Contracts\JWTSubject::class, $employee);
    }

    /** @test */
    public function employee_returns_correct_jwt_identifier()
    {
        $employee = Employee::factory()->create();

        $this->assertEquals($employee->getKey(), $employee->getJWTIdentifier());
    }

    /** @test */
    public function employee_returns_empty_jwt_custom_claims()
    {
        $employee = Employee::factory()->create();

        $this->assertEquals([], $employee->getJWTCustomClaims());
    }

    /** @test */
    public function employee_can_be_authenticated_with_email_and_password()
    {
        $password = 'password123';
        $employee = Employee::factory()->create([
            'password' => Hash::make($password),
        ]);

        $this->assertTrue(Hash::check($password, $employee->password));

        // Test authentication attempt
        $foundEmployee = Employee::where('email', $employee->email)->first();
        $this->assertNotNull($foundEmployee);
        $this->assertTrue(Hash::check($password, $foundEmployee->password));
    }

    /** @test */
    public function employee_password_is_hidden_in_arrays()
    {
        $employee = Employee::factory()->create();

        $array = $employee->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    /** @test */
    public function employee_remember_token_is_hidden_in_arrays()
    {
        $employee = Employee::factory()->create();

        $array = $employee->toArray();

        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function employee_can_update_last_login_timestamp()
    {
        $employee = Employee::factory()->create();
        $originalLastLogin = $employee->last_login_at;

        $employee->updateLastLogin();

        $this->assertNotEquals($originalLastLogin, $employee->fresh()->last_login_at);
        $this->assertNotNull($employee->fresh()->last_login_at);
    }

    /** @test */
    public function employee_full_name_attribute_returns_name()
    {
        $name = 'John Doe';
        $employee = Employee::factory()->create(['name' => $name]);

        $this->assertEquals($name, $employee->full_name);
    }

    /** @test */
    public function employee_is_manager_attribute_works_correctly()
    {
        // Create manager
        $manager = Employee::factory()->create();

        // Create subordinate
        Employee::factory()->create(['manager_id' => $manager->id]);

        $this->assertTrue($manager->is_manager);

        // Create non-manager employee
        $regularEmployee = Employee::factory()->create();

        $this->assertFalse($regularEmployee->is_manager);
    }

    /** @test */
    public function employee_generate_employee_code_creates_unique_code()
    {
        $code1 = Employee::generateEmployeeCode();
        $code2 = Employee::generateEmployeeCode();

        $this->assertNotEquals($code1, $code2);
        $this->assertStringStartsWith('EMP-', $code1);
        $this->assertStringStartsWith('EMP-', $code2);
    }

    /** @test */
    public function employee_scopes_work_correctly()
    {
        // Create test employees
        $activeEmployee = Employee::factory()->create(['is_active' => true]);
        $inactiveEmployee = Employee::factory()->create(['is_active' => false]);
        $inServiceEmployee = Employee::factory()->create(['is_in_service' => true]);
        $outOfServiceEmployee = Employee::factory()->create(['is_in_service' => false]);

        // Test active scope
        $activeEmployees = Employee::active()->get();
        $this->assertTrue($activeEmployees->contains($activeEmployee));
        $this->assertFalse($activeEmployees->contains($inactiveEmployee));

        // Test in service scope
        $inServiceEmployees = Employee::inService()->get();
        $this->assertTrue($inServiceEmployees->contains($inServiceEmployee));
        $this->assertFalse($inServiceEmployees->contains($outOfServiceEmployee));
    }

    /** @test */
    public function employee_has_permission_method_works()
    {
        $permission = \App\Models\Permission::factory()->create(['slug' => 'some.permission']);
        $role = Role::factory()->create();

        // Attach permission directly
        $role->permissions()->attach($permission->id);

        $employee = Employee::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($employee->hasPermission('some.permission'));
        $this->assertFalse($employee->hasPermission('nonexistent.permission'));
    }

    /** @test */
    public function employee_relationships_work_correctly()
    {
        $store = Store::factory()->create();
        $role = Role::factory()->create();

        $employee = Employee::factory()->create([
            'store_id' => $store->id,
            'role_id' => $role->id,
        ]);

        $this->assertInstanceOf(Store::class, $employee->store);
        $this->assertInstanceOf(Role::class, $employee->role);
        $this->assertEquals($store->id, $employee->store->id);
        $this->assertEquals($role->id, $employee->role->id);
    }

    /** @test */
    public function employee_casts_work_correctly()
    {
        $hireDate = '2023-01-15';
        $salary = 50000.50;

        $employee = Employee::factory()->create([
            'hire_date' => $hireDate,
            'salary' => $salary,
            'is_active' => '1', // String boolean
            'is_in_service' => '0', // String boolean
        ]);

        $this->assertNotNull($employee->hire_date);
        $this->assertEquals($salary, $employee->salary);
        $this->assertTrue($employee->is_active);
        $this->assertFalse($employee->is_in_service);
    }
}