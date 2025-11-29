<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerAuthApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test customer registration endpoint
     */
    public function test_customer_can_register()
    {
        $response = $this->postJson('/api/customer-auth/register', [
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '01712345678',
            'country' => 'Bangladesh',
        ]);

        // Debug: Print the response
        if ($response->status() !== 201) {
            dump($response->json());
            dump($response->status());
        }

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'customer_code',
                        'status',
                        'email_verified',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('customers', [
            'email' => 'test@example.com',
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);
    }

    /**
     * Test customer registration validation fails with invalid data
     */
    public function test_customer_registration_requires_valid_data()
    {
        $response = $this->postJson('/api/customer-auth/register', [
            'name' => 'T', // Too short
            'email' => 'invalid-email', // Invalid format
            'password' => 'short', // Too short
            'phone' => '01712345678',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * Test customer registration prevents duplicate email
     */
    public function test_customer_registration_prevents_duplicate_email()
    {
        Customer::factory()->create([
            'email' => 'existing@example.com',
            'customer_type' => 'ecommerce',
        ]);

        $response = $this->postJson('/api/customer-auth/register', [
            'name' => 'Test Customer',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '01712345679',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test customer can login with valid credentials
     */
    public function test_customer_can_login_with_valid_credentials()
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/customer-auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'customer' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'customer_code',
                        'status',
                        'email_verified',
                        'total_orders',
                        'total_purchases',
                        'last_purchase_at',
                    ],
                    'token',
                    'token_type',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'bearer',
                    'customer' => [
                        'email' => 'test@example.com',
                    ],
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    /**
     * Test customer login fails with invalid credentials
     */
    public function test_customer_login_fails_with_invalid_credentials()
    {
        Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/customer-auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test inactive customer cannot login
     */
    public function test_inactive_customer_cannot_login()
    {
        Customer::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'inactive',
        ]);

        $response = $this->postJson('/api/customer-auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test non-ecommerce customer cannot login
     */
    public function test_counter_customer_cannot_login()
    {
        Customer::factory()->create([
            'email' => 'counter@example.com',
            'phone' => '01712345678',
            'customer_type' => 'counter',
            'status' => 'active',
            'password' => null, // Counter customers don't have passwords
        ]);

        $response = $this->postJson('/api/customer-auth/login', [
            'email' => 'counter@example.com',
            'password' => 'anypassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid credentials',
            ]);
    }

    /**
     * Test authenticated customer can access /me endpoint
     */
    public function test_authenticated_customer_can_access_profile()
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($customer);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer-auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'email' => $customer->email,
                        'customer_type' => 'ecommerce',
                    ],
                ],
            ]);
    }

    /**
     * Test unauthenticated request to protected endpoint fails
     */
    public function test_unauthenticated_request_fails()
    {
        $response = $this->getJson('/api/customer-auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test customer can logout
     */
    public function test_customer_can_logout()
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($customer);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer-auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Successfully logged out',
            ]);

        // Verify token is invalidated
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/customer-auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test customer can refresh token
     */
    public function test_customer_can_refresh_token()
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($customer);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/customer-auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'token',
                    'token_type',
                    'expires_in',
                    'customer',
                ],
            ]);

        $newToken = $response->json('data.token');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($token, $newToken);
    }

    /**
     * Test remember me extends token TTL
     */
    public function test_remember_me_login()
    {
        $customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/customer-auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'remember_me' => true,
        ]);

        $response->assertStatus(200);

        $expiresIn = $response->json('data.expires_in');
        // Remember me should give longer expiry (2 weeks = 20160 minutes = 1209600 seconds)
        $this->assertGreaterThan(3600, $expiresIn); // Greater than 1 hour
    }
}
