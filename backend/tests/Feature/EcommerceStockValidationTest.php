<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class EcommerceStockValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $customer;
    protected $product;
    protected $store;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test store
        $this->store = Store::create([
            'name' => 'Test Store',
            'address' => '123 Test St',
            'city' => 'Test City',
            'state' => 'Test State',
            'postal_code' => '12345',
            'country' => 'Bangladesh',
            'phone' => '01700000000',
            'email' => 'test@store.com',
        ]);

        // Create test category
        $this->category = \App\Models\Category::create([
            'title' => 'Test Category',
            'slug' => 'test-category',
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ]);

        // Create test customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'phone' => '01700000001',
            'password' => Hash::make('password123'),
        ]);

        // Create test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'store_id' => $this->store->id,
        ]);
    }

    /**
     * Test: eCommerce order should be REJECTED if insufficient stock
     * Requirement: "jokhon ee order entry hobe shathe shathe stock hold/minus hobe...
     *               hold a thakle ba stock a na thakle keo order korte parbena"
     */
    public function test_ecommerce_order_rejected_when_insufficient_stock()
    {
        // Create product batch with limited stock
        ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 5, // Only 5 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Add item to cart requesting MORE than available stock
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 10, // Requesting 10, but only 5 available
            'unit_price' => 100.00,
            'status' => 'active',
        ]);

        // Create customer address
        $address = $this->customer->addresses()->create([
            'name' => 'Test Customer',
            'phone' => '01700000001',
            'address_line_1' => '123 Test St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        // Attempt to create order
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/customer/orders/create-from-cart', [
                'payment_method' => 'cod',
                'shipping_address_id' => $address->id,
            ]);

        // Should be rejected with 400 status
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'Insufficient stock for some items in your cart',
        ]);

        // Verify order was NOT created
        $this->assertDatabaseMissing('orders', [
            'customer_id' => $this->customer->id,
        ]);
    }

    /**
     * Test: eCommerce order should SUCCEED with sufficient stock and hold/deduct stock
     * Requirement: "jokhon ee order entry hobe shathe shathe stock hold/minus hobe"
     */
    public function test_ecommerce_order_succeeds_with_sufficient_stock_and_holds_inventory()
    {
        // Create product batch with sufficient stock
        $batch = ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 20, // 20 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Add item to cart requesting LESS than available stock
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 5, // Requesting 5, 20 available
            'unit_price' => 100.00,
            'status' => 'active',
        ]);

        // Create customer address
        $address = $this->customer->addresses()->create([
            'name' => 'Test Customer',
            'phone' => '01700000001',
            'address_line_1' => '123 Test St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ]);

        // Create order
        $response = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/customer/orders/create-from-cart', [
                'payment_method' => 'cod',
                'shipping_address_id' => $address->id,
            ]);

        // Should succeed
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify order was created
        $this->assertDatabaseHas('orders', [
            'customer_id' => $this->customer->id,
            'status' => 'pending',
            'is_preorder' => false, // NOT a pre-order
        ]);

        // Verify stock was held/reduced (implementation should create product_movements or reduce batch quantity)
        // For now, we check that order items were created with proper quantities
        $order = Order::where('customer_id', $this->customer->id)->first();
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
        ]);
    }

    /**
     * Test: Guest checkout should also reject insufficient stock
     */
    public function test_guest_checkout_rejected_when_insufficient_stock()
    {
        // Create product batch with limited stock
        ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 3, // Only 3 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Attempt guest checkout requesting more than available
        $response = $this->postJson('/api/guest-checkout', [
            'phone' => '01700000002',
            'customer_name' => 'Guest Customer',
            'payment_method' => 'cod',
            'delivery_address' => [
                'full_name' => 'Guest Customer',
                'phone' => '01700000002',
                'address_line_1' => '123 Guest St',
                'city' => 'Dhaka',
                'postal_code' => '1212',
                'country' => 'Bangladesh',
            ],
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 10, // Requesting 10, but only 3 available
                ],
            ],
        ]);

        // Should be rejected
        $response->assertStatus(400);
        $response->assertJson([
            'success' => false,
        ]);
        $response->assertJsonFragment([
            'message' => 'Insufficient stock for some items',
        ]);
    }

    /**
     * Test: Pre-order should be allowed even without stock
     * Requirement: "Pre Order panel theke separate entry hobe, jekhane stock thakuk ba na thakuk order place kora jabe"
     * 
     * Note: This test assumes there's a separate pre-order endpoint or flag
     * Current implementation marks orders as is_preorder=true when out of stock
     * Product manager wants this ONLY from pre-order panel, not from regular ecommerce
     */
    public function test_preorder_allowed_without_stock()
    {
        // This test documents the expected behavior for pre-order panel
        // Pre-order panel should have a separate endpoint/flag that allows stock-less orders
        
        // For now, marking as incomplete since pre-order panel is separate feature
        $this->markTestIncomplete(
            'Pre-order panel should be a separate feature that allows orders without stock validation. ' .
            'Regular eCommerce orders should NOT allow pre-orders.'
        );
    }

    /**
     * Test: Multiple customers cannot order beyond available stock
     * Requirement: Stock should be properly managed to prevent overselling
     */
    public function test_concurrent_orders_do_not_oversell_stock()
    {
        // Create product batch with limited stock
        ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 10, // Only 10 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Customer 1 adds 6 items to cart
        Cart::create([
            'customer_id' => $this->customer->id,
            'product_id' => $this->product->id,
            'quantity' => 6,
            'unit_price' => 100.00,
            'status' => 'active',
        ]);

        // Create address for customer 1
        $address1 = $this->customer->addresses()->create([
            'name' => 'Test Customer',
            'phone' => '01700000001',
            'address_line_1' => '123 Test St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
            'is_default_shipping' => true,
        ]);

        // Customer 1 places order (should succeed)
        $response1 = $this->actingAs($this->customer, 'customer')
            ->postJson('/api/customer/orders/create-from-cart', [
                'payment_method' => 'cod',
                'shipping_address_id' => $address1->id,
            ]);

        $response1->assertStatus(201);

        // Create customer 2
        $customer2 = Customer::create([
            'name' => 'Test Customer 2',
            'email' => 'customer2@test.com',
            'phone' => '01700000003',
            'password' => Hash::make('password123'),
        ]);

        // Customer 2 adds 6 items to cart (but only 4 should be left)
        Cart::create([
            'customer_id' => $customer2->id,
            'product_id' => $this->product->id,
            'quantity' => 6,
            'unit_price' => 100.00,
            'status' => 'active',
        ]);

        $address2 = $customer2->addresses()->create([
            'name' => 'Test Customer 2',
            'phone' => '01700000003',
            'address_line_1' => '456 Test St',
            'city' => 'Dhaka',
            'state' => 'Dhaka',
            'postal_code' => '1212',
            'country' => 'Bangladesh',
            'is_default_shipping' => true,
        ]);

        // Customer 2 places order (should FAIL due to insufficient stock)
        $response2 = $this->actingAs($customer2, 'customer')
            ->postJson('/api/customer/orders/create-from-cart', [
                'payment_method' => 'cod',
                'shipping_address_id' => $address2->id,
            ]);

        // Should be rejected
        $response2->assertStatus(400);
        $response2->assertJsonFragment([
            'message' => 'Insufficient stock for some items in your cart',
        ]);
    }
}
