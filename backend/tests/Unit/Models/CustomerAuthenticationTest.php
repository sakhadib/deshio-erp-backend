<?php

namespace Tests\Unit\Models;

use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test employee for created_by field
        $this->employee = Employee::factory()->create();
    }

    /** @test */
    public function customer_extends_authenticatable()
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(\Illuminate\Foundation\Auth\User::class, $customer);
    }

    /** @test */
    public function customer_password_is_hidden_in_arrays()
    {
        $customer = Customer::factory()->create();

        $array = $customer->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }

    /** @test */
    public function customer_remember_token_is_hidden_in_arrays()
    {
        $customer = Customer::factory()->create();

        $array = $customer->toArray();

        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function customer_can_set_and_verify_password()
    {
        $customer = Customer::factory()->ecommerce()->create();
        $password = 'testpassword123';

        $customer->setPassword($password);

        $this->assertTrue($customer->verifyPassword($password));
        $this->assertFalse($customer->verifyPassword('wrongpassword'));
    }

    /** @test */
    public function customer_can_mark_email_as_verified()
    {
        $customer = Customer::factory()->create(['email_verified_at' => null]);

        $customer->markEmailAsVerified();

        $this->assertNotNull($customer->email_verified_at);
        $this->assertTrue($customer->hasVerifiedEmail());
    }

    /** @test */
    public function customer_has_verified_email_returns_correct_value()
    {
        $customerWithVerifiedEmail = Customer::factory()->create(['email_verified_at' => now()]);
        $customerWithoutVerifiedEmail = Customer::factory()->create(['email_verified_at' => null]);

        $this->assertTrue($customerWithVerifiedEmail->hasVerifiedEmail());
        $this->assertFalse($customerWithoutVerifiedEmail->hasVerifiedEmail());
    }

    /** @test */
    public function counter_customer_does_not_require_authentication()
    {
        $customer = Customer::factory()->counter()->create();

        $this->assertFalse($customer->requiresAuthentication());
        $this->assertFalse($customer->canLogin());
    }

    /** @test */
    public function social_commerce_customer_does_not_require_authentication()
    {
        $customer = Customer::factory()->socialCommerce()->create();

        $this->assertFalse($customer->requiresAuthentication());
        $this->assertFalse($customer->canLogin());
    }

    /** @test */
    public function ecommerce_customer_requires_authentication()
    {
        $customer = Customer::factory()->ecommerce()->create();

        $this->assertTrue($customer->requiresAuthentication());
        $this->assertTrue($customer->canLogin());
    }

    /** @test */
    public function inactive_ecommerce_customer_cannot_login()
    {
        $customer = Customer::factory()->ecommerce()->inactive()->create();

        $this->assertFalse($customer->canLogin());
    }

    /** @test */
    public function blocked_ecommerce_customer_cannot_login()
    {
        $customer = Customer::factory()->blocked()->create();

        $this->assertFalse($customer->canLogin());
    }

    /** @test */
    public function ecommerce_customer_without_password_cannot_login()
    {
        $customer = Customer::factory()->ecommerce()->create(['password' => null]);

        $this->assertFalse($customer->canLogin());
    }

    /** @test */
    public function customer_type_checks_work_correctly()
    {
        $counterCustomer = Customer::factory()->counter()->create();
        $socialCustomer = Customer::factory()->socialCommerce()->create();
        $ecommerceCustomer = Customer::factory()->ecommerce()->create();

        $this->assertTrue($counterCustomer->isCounterCustomer());
        $this->assertFalse($counterCustomer->isSocialCommerceCustomer());
        $this->assertFalse($counterCustomer->isEcommerceCustomer());

        $this->assertFalse($socialCustomer->isCounterCustomer());
        $this->assertTrue($socialCustomer->isSocialCommerceCustomer());
        $this->assertFalse($socialCustomer->isEcommerceCustomer());

        $this->assertFalse($ecommerceCustomer->isCounterCustomer());
        $this->assertFalse($ecommerceCustomer->isSocialCommerceCustomer());
        $this->assertTrue($ecommerceCustomer->isEcommerceCustomer());
    }

    /** @test */
    public function customer_status_checks_work_correctly()
    {
        $activeCustomer = Customer::factory()->create(['status' => 'active']);
        $inactiveCustomer = Customer::factory()->inactive()->create();
        $blockedCustomer = Customer::factory()->blocked()->create();

        $this->assertTrue($activeCustomer->isActive());
        $this->assertFalse($activeCustomer->isBlocked());

        $this->assertFalse($inactiveCustomer->isActive());
        $this->assertFalse($inactiveCustomer->isBlocked());

        $this->assertFalse($blockedCustomer->isActive());
        $this->assertTrue($blockedCustomer->isBlocked());
    }

    /** @test */
    public function customer_can_record_purchase()
    {
        $customer = Customer::factory()->create([
            'total_purchases' => 100,
            'total_orders' => 2,
        ]);

        $customer->recordPurchase(50, 123);

        $this->assertEquals(150, $customer->total_purchases);
        $this->assertEquals(3, $customer->total_orders);
        $this->assertNotNull($customer->first_purchase_at);
        $this->assertNotNull($customer->last_purchase_at);
    }

    /** @test */
    public function customer_generate_customer_code_creates_unique_code()
    {
        $code1 = Customer::generateCustomerCode();
        $code2 = Customer::generateCustomerCode();

        $this->assertNotEquals($code1, $code2);
        $this->assertStringStartsWith('CUST-', $code1);
        $this->assertStringStartsWith('CUST-', $code2);
    }

    /** @test */
    public function customer_scopes_work_correctly()
    {
        // Create test customers
        $counterCustomer = Customer::factory()->counter()->create();
        $socialCustomer = Customer::factory()->socialCommerce()->create();
        $ecommerceCustomer = Customer::factory()->ecommerce()->create();
        $activeCustomer = Customer::factory()->create(['status' => 'active']);
        $inactiveCustomer = Customer::factory()->inactive()->create();

        // Test customer type scopes
        $counterCustomers = Customer::counterCustomers()->get();
        $this->assertTrue($counterCustomers->contains($counterCustomer));
        $this->assertFalse($counterCustomers->contains($socialCustomer));

        $socialCustomers = Customer::socialCommerceCustomers()->get();
        $this->assertTrue($socialCustomers->contains($socialCustomer));
        $this->assertFalse($socialCustomers->contains($counterCustomer));

        $ecommerceCustomers = Customer::ecommerceCustomers()->get();
        $this->assertTrue($ecommerceCustomers->contains($ecommerceCustomer));
        $this->assertFalse($ecommerceCustomers->contains($counterCustomer));

        // Test status scopes
        $activeCustomers = Customer::active()->get();
        $this->assertTrue($activeCustomers->contains($activeCustomer));
        $this->assertFalse($activeCustomers->contains($inactiveCustomer));

        $inactiveCustomers = Customer::inactive()->get();
        $this->assertTrue($inactiveCustomers->contains($inactiveCustomer));
        $this->assertFalse($inactiveCustomers->contains($activeCustomer));
    }

    /** @test */
    public function customer_business_logic_methods_work_correctly()
    {
        $highValueCustomer = Customer::factory()->create(['total_purchases' => 1500]);
        $lowValueCustomer = Customer::factory()->create(['total_purchases' => 100]);

        $loyalCustomer = Customer::factory()->create(['total_purchases' => 600]);
        $nonLoyalCustomer = Customer::factory()->create(['total_purchases' => 200]);

        $recentCustomer = Customer::factory()->create(['first_purchase_at' => now()->subDays(30)]);
        $oldCustomer = Customer::factory()->create(['first_purchase_at' => now()->subDays(200)]);

        $atRiskCustomer = Customer::factory()->create(['last_purchase_at' => now()->subDays(100)]);
        $safeCustomer = Customer::factory()->create(['last_purchase_at' => now()->subDays(10)]);

        $this->assertTrue($highValueCustomer->isLoyalCustomer(1000));
        $this->assertFalse($lowValueCustomer->isLoyalCustomer(1000));

        $this->assertTrue($loyalCustomer->isLoyalCustomer(500));
        $this->assertFalse($nonLoyalCustomer->isLoyalCustomer(500));

        $this->assertTrue($recentCustomer->isRecentCustomer(90));
        $this->assertFalse($oldCustomer->isRecentCustomer(90));

        $this->assertTrue($atRiskCustomer->isAtRiskCustomer(90));
        $this->assertFalse($safeCustomer->isAtRiskCustomer(90));
    }

    /** @test */
    public function customer_static_factory_methods_work_correctly()
    {
        $counterCustomer = Customer::createCounterCustomer([
            'name' => 'Counter Customer',
            'phone' => '01712345678',
            'created_by' => $this->employee->id,
        ]);

        $socialCustomer = Customer::createSocialCommerceCustomer([
            'name' => 'Social Customer',
            'phone' => '01787654321',
            'created_by' => $this->employee->id,
        ]);

        $ecommerceCustomer = Customer::createEcommerceCustomer([
            'name' => 'E-commerce Customer',
            'email' => 'ecommerce@example.com',
            'phone' => '01711223344',
            'password' => 'password123',
            'created_by' => $this->employee->id,
        ]);

        $this->assertEquals('counter', $counterCustomer->customer_type);
        $this->assertNull($counterCustomer->password);
        $this->assertEquals('active', $counterCustomer->status);

        $this->assertEquals('social_commerce', $socialCustomer->customer_type);
        $this->assertNull($socialCustomer->password);
        $this->assertEquals('active', $socialCustomer->status);

        $this->assertEquals('ecommerce', $ecommerceCustomer->customer_type);
        $this->assertNotNull($ecommerceCustomer->password);
        $this->assertEquals('active', $ecommerceCustomer->status);
    }

    /** @test */
    public function customer_static_factory_methods_throw_exception_for_ecommerce_without_password()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('E-commerce customers must have a password');

        Customer::createEcommerceCustomer([
            'name' => 'E-commerce Customer',
            'email' => 'ecommerce@example.com',
            'phone' => '01711223344',
            'created_by' => $this->employee->id,
        ]);
    }

    /** @test */
    public function customer_find_by_methods_work_correctly()
    {
        $customer = Customer::factory()->create([
            'phone' => '01712345678',
            'email' => 'test@example.com',
            'customer_code' => 'CUST-2023-ABC123',
        ]);

        $foundByPhone = Customer::findByPhone('01712345678');
        $foundByEmail = Customer::findByEmail('test@example.com');
        $foundByCode = Customer::findByCode('CUST-2023-ABC123');

        $this->assertEquals($customer->id, $foundByPhone->id);
        $this->assertEquals($customer->id, $foundByEmail->id);
        $this->assertEquals($customer->id, $foundByCode->id);
    }

    /** @test */
    public function customer_get_customer_type_label_returns_correct_labels()
    {
        $counterCustomer = Customer::factory()->counter()->create();
        $socialCustomer = Customer::factory()->socialCommerce()->create();
        $ecommerceCustomer = Customer::factory()->ecommerce()->create();

        $this->assertEquals('Counter Sale', $counterCustomer->customer_type_label);
        $this->assertEquals('Social Commerce', $socialCustomer->customer_type_label);
        $this->assertEquals('E-commerce', $ecommerceCustomer->customer_type_label);
    }

    /** @test */
    public function customer_get_age_attribute_works_correctly()
    {
        $customerWithDOB = Customer::factory()->create([
            'date_of_birth' => now()->subYears(25)->subDays(30),
        ]);

        $customerWithoutDOB = Customer::factory()->create([
            'date_of_birth' => null,
        ]);

        $this->assertEquals(25, $customerWithDOB->age);
        $this->assertNull($customerWithoutDOB->age);
    }

    /** @test */
    public function customer_get_formatted_phone_attribute_formats_bangladeshi_numbers()
    {
        $customer = Customer::factory()->create([
            'phone' => '01712345678',
        ]);

        $this->assertEquals('+880 1712-345678', $customer->formatted_phone);
    }

    /** @test */
    public function customer_communication_preferences_work_correctly()
    {
        $customer = Customer::factory()->create();

        $customer->setCommunicationPreference('email', true);
        $customer->setCommunicationPreference('sms', false);

        $this->assertEquals(['email' => true, 'sms' => false], $customer->communication_preferences);
    }

    /** @test */
    public function customer_shopping_preferences_work_correctly()
    {
        $customer = Customer::factory()->create();

        $customer->setShoppingPreference('category', 'electronics');
        $customer->setShoppingPreference('budget', 'high');

        $this->assertEquals(['category' => 'electronics', 'budget' => 'high'], $customer->shopping_preferences);
    }

    /** @test */
    public function customer_social_profiles_work_correctly()
    {
        $customer = Customer::factory()->create();

        $customer->addSocialProfile('whatsapp', '+8801712345678');
        $customer->addSocialProfile('facebook', 'john.doe');

        $this->assertEquals('+8801712345678', $customer->getSocialProfile('whatsapp'));
        $this->assertEquals('john.doe', $customer->getSocialProfile('facebook'));
        $this->assertNull($customer->getSocialProfile('instagram'));
    }

    /** @test */
    public function customer_get_whats_app_number_returns_correct_number()
    {
        $customerWithWhatsApp = Customer::factory()->create(['phone' => '01712345678']);
        $customerWithWhatsApp->addSocialProfile('whatsapp', '+8801712345678');

        $customerWithoutWhatsApp = Customer::factory()->create(['phone' => '01787654321']);

        $this->assertEquals('+8801712345678', $customerWithWhatsApp->whats_app_number);
        $this->assertEquals('01787654321', $customerWithoutWhatsApp->whats_app_number);
    }

    /** @test */
    public function customer_status_management_methods_work_correctly()
    {
        $customer = Customer::factory()->create();

        $customer->activate();
        $this->assertEquals('active', $customer->status);

        $customer->deactivate();
        $this->assertEquals('inactive', $customer->status);

        $customer->block();
        $this->assertEquals('blocked', $customer->status);
    }

    /** @test */
    public function customer_lifetime_value_attribute_returns_total_purchases()
    {
        $customer = Customer::factory()->create(['total_purchases' => 1500.50]);

        $this->assertEquals(1500.50, $customer->lifetime_value);
    }

    /** @test */
    public function customer_average_order_value_calculates_correctly()
    {
        $customerWithOrders = Customer::factory()->create([
            'total_purchases' => 300,
            'total_orders' => 3,
        ]);

        $customerWithoutOrders = Customer::factory()->create([
            'total_purchases' => 100,
            'total_orders' => 0,
        ]);

        $this->assertEquals(100, $customerWithOrders->average_order_value);
        $this->assertEquals(0, $customerWithoutOrders->average_order_value);
    }

    /** @test */
    public function customer_days_since_last_purchase_calculates_correctly()
    {
        $customerWithPurchase = Customer::factory()->create([
            'last_purchase_at' => now()->subDays(5),
        ]);

        $customerWithoutPurchase = Customer::factory()->create([
            'last_purchase_at' => null,
        ]);

        $this->assertEquals(5, $customerWithPurchase->days_since_last_purchase);
        $this->assertNull($customerWithoutPurchase->days_since_last_purchase);
    }

    /** @test */
    public function customer_casts_work_correctly()
    {
        $customer = Customer::factory()->create([
            'date_of_birth' => '1995-03-15',
            'total_purchases' => '1500.50',
            'total_orders' => '5',
            'preferences' => ['communication' => ['email' => true]],
            'social_profiles' => ['whatsapp' => '+8801712345678'],
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $customer->date_of_birth);
        $this->assertEquals(1500.50, $customer->total_purchases);
        $this->assertEquals(5, $customer->total_orders);
        $this->assertIsArray($customer->preferences);
        $this->assertIsArray($customer->social_profiles);
    }

    /** @test */
    public function customer_implements_jwt_subject()
    {
        $customer = Customer::factory()->create([
            'customer_type' => 'ecommerce',
            'password' => bcrypt('password123'),
        ]);

        $this->assertInstanceOf(\Tymon\JWTAuth\Contracts\JWTSubject::class, $customer);
    }

    /** @test */
    public function get_jwt_identifier_returns_primary_key()
    {
        $customer = Customer::factory()->create([
            'customer_type' => 'ecommerce',
            'password' => bcrypt('password123'),
        ]);

        $this->assertEquals($customer->id, $customer->getJWTIdentifier());
    }

    /** @test */
    public function get_jwt_custom_claims_returns_correct_data()
    {
        $customer = Customer::factory()->create([
            'customer_type' => 'ecommerce',
            'email' => 'test@example.com',
            'phone' => '01712345678',
            'password' => bcrypt('password123'),
        ]);

        $claims = $customer->getJWTCustomClaims();

        $this->assertIsArray($claims);
        $this->assertEquals('ecommerce', $claims['customer_type']);
        $this->assertEquals('test@example.com', $claims['email']);
        $this->assertEquals('01712345678', $claims['phone']);
        $this->assertArrayHasKey('customer_code', $claims);
    }

    /** @test */
    public function jwt_token_can_be_generated_for_customer()
    {
        $customer = Customer::factory()->create([
            'customer_type' => 'ecommerce',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($customer);

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    /** @test */
    public function jwt_token_can_be_parsed_to_customer()
    {
        $customer = Customer::factory()->create([
            'customer_type' => 'ecommerce',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
        ]);

        // Generate token
        $token = \Tymon\JWTAuth\Facades\JWTAuth::fromUser($customer);
        
        // Parse token to get payload
        $payload = \Tymon\JWTAuth\Facades\JWTAuth::setToken($token)->getPayload();

        // Verify the token contains correct customer information
        $this->assertEquals($customer->id, $payload->get('sub'));
        $this->assertEquals($customer->customer_type, $payload->get('customer_type'));
        $this->assertEquals($customer->email, $payload->get('email'));
        $this->assertEquals($customer->phone, $payload->get('phone'));
        $this->assertEquals($customer->customer_code, $payload->get('customer_code'));
    }

    /** @test */
    public function customer_guard_uses_correct_provider()
    {
        $guardConfig = config('auth.guards.customer');
        
        $this->assertNotNull($guardConfig);
        $this->assertEquals('jwt', $guardConfig['driver']);
        $this->assertEquals('customers', $guardConfig['provider']);
    }

    /** @test */
    public function customers_provider_uses_customer_model()
    {
        $providerConfig = config('auth.providers.customers');
        
        $this->assertNotNull($providerConfig);
        $this->assertEquals('eloquent', $providerConfig['driver']);
        $this->assertEquals(\App\Models\Customer::class, $providerConfig['model']);
    }
}
