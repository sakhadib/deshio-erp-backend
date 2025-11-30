<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EcommerceOrderCreationTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Product $product1;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test customer
        $this->customer = Customer::factory()->create([
            'customer_type' => 'ecommerce',
            'status' => 'active',
        ]);

        // Create test products
        $this->product1 = Product::factory()->create([
            'name' => 'Test Product 1',
            'sku' => 'TEST-001',
        ]);

        $this->product2 = Product::factory()->create([
            'name' => 'Test Product 2',
            'sku' => 'TEST-002',
        ]);
    }

    /** @test */
    public function it_validates_empty_cart()
    {
        // Create customer address
        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Cart is empty',
            ]);
    }

    /** @test */
    public function it_creates_order_from_cart_successfully()
    {
        // Add items to cart
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'status' => 'active',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'city' => 'Dhaka',
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Order placed successfully. An employee will assign it to a store shortly.',
            ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'order_type' => 'ecommerce',
            'status' => 'pending_assignment',
            'store_id' => null,
        ]);

        // Verify order items were created
        $order = Order::where('customer_id', $this->customer->id)->first();
        $this->assertEquals(2, $order->items->count());

        // Verify cart was cleared
        $this->assertEquals(0, Cart::where('customer_id', $this->customer->id)
            ->where('status', 'active')
            ->count());
    }

    /** @test */
    public function it_calculates_order_totals_correctly()
    {
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'status' => 'active',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'city' => 'Dhaka',
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(201);

        $order = Order::where('customer_id', $this->customer->id)->first();

        // Subtotal: (2 * 1000) + (1 * 500) = 2500
        $this->assertEquals(2500.00, $order->subtotal);

        // Tax: 5% of 2500 = 125
        $this->assertEquals(125.00, $order->tax_amount);

        // Shipping: 60 (Dhaka)
        $this->assertEquals(60.00, $order->shipping_amount);

        // Total: 2500 + 125 + 60 = 2685
        $this->assertEquals(2685.00, $order->total_amount);
    }

    /** @test */
    public function it_validates_shipping_address_exists()
    {
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => 99999, // Non-existent
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_address_id']);
    }

    /** @test */
    public function it_validates_payment_method()
    {
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'invalid_method',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    /** @test */
    public function it_validates_all_products_still_exist()
    {
        // Add item to cart
        $cart = Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        // Delete the product
        $this->product1->delete();

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Some products in your cart are no longer available',
            ]);
    }

    /** @test */
    public function it_applies_different_shipping_charges_by_city()
    {
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        // Test Dhaka shipping (60 BDT)
        $dhakaAddress = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'city' => 'Dhaka',
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $dhakaAddress->id,
        ]);

        $response->assertStatus(201);
        $dhakaOrder = Order::where('customer_id', $this->customer->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertEquals(60.00, $dhakaOrder->shipping_amount);

        // Test Chittagong shipping (120 BDT) - Use product2 to avoid unique constraint
        DB::table('carts')->insert([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test Chittagong shipping (120 BDT)
        $chittagongAddress = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
            'city' => 'Chittagong',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $chittagongAddress->id,
        ]);

        $response->assertStatus(201);
        
        // Get the latest order after this second order creation
        $chittagongOrder = Order::where('customer_id', $this->customer->id)
            ->where('id', '>', $dhakaOrder->id)
            ->orderBy('id', 'desc')
            ->first();
        $this->assertNotNull($chittagongOrder);
        $this->assertEquals(120.00, $chittagongOrder->shipping_amount);
    }

    /** @test */
    public function it_creates_order_items_with_correct_data()
    {
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'unit_price' => 1000.00,
            'notes' => 'Gift wrap please',
            'status' => 'active',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(201);

        $orderItem = OrderItem::first();
        $this->assertEquals($this->product1->id, $orderItem->product_id);
        $this->assertEquals('Test Product 1', $orderItem->product_name);
        $this->assertEquals('TEST-001', $orderItem->product_sku);
        $this->assertEquals(2, $orderItem->quantity);
        $this->assertEquals(1000.00, $orderItem->unit_price);
        $this->assertEquals(2000.00, $orderItem->total_amount);
        $this->assertEquals('Gift wrap please', $orderItem->notes);
        $this->assertNull($orderItem->product_batch_id);
        $this->assertNull($orderItem->product_barcode_id);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => 1,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_stores_billing_address_same_as_shipping_when_not_provided()
    {
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(201);

        $order = Order::first();
        $this->assertEquals($order->shipping_address, $order->billing_address);
    }

    /** @test */
    public function it_ignores_saved_cart_items()
    {
        // Active cart item
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product1->id,
            'quantity' => 1,
            'unit_price' => 1000.00,
            'status' => 'active',
        ]);

        // Saved for later item (should be ignored)
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product2->id,
            'quantity' => 1,
            'unit_price' => 500.00,
            'status' => 'saved',
        ]);

        $address = CustomerAddress::factory()->create([
            'customer_id' => $this->customer->id,
        ]);

        $token = auth('customer')->login($this->customer);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson('/api/customer/orders/create-from-cart', [
            'payment_method' => 'cod',
            'shipping_address_id' => $address->id,
        ]);

        $response->assertStatus(201);

        $order = Order::first();
        $this->assertEquals(1, $order->items->count());
        $this->assertEquals($this->product1->id, $order->items->first()->product_id);

        // Verify saved item is still in cart
        $this->assertEquals(1, Cart::where('customer_id', $this->customer->id)
            ->where('status', 'saved')
            ->count());
    }
}
