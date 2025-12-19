<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use App\Models\Employee;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AllOrderTypesStockValidationTest extends TestCase
{
    use RefreshDatabase;

    protected $store;
    protected $category;
    protected $product;
    protected $employee;

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
        $this->category = Category::create([
            'title' => 'Test Category',
            'slug' => 'test-category',
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ]);

        // Create test product
        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-SKU-001',
            'description' => 'Test Description',
            'category_id' => $this->category->id,
            'store_id' => $this->store->id,
        ]);

        // Create test employee for authentication
        $this->employee = Employee::create([
            'name' => 'Test Employee',
            'email' => 'employee@test.com',
            'phone' => '01700000001',
            'password' => Hash::make('password123'),
            'employee_id' => 'EMP001',
            'status' => 'active',
            'store_id' => $this->store->id, // Added required store_id
        ]);
    }

    /**
     * Test: POS (counter) orders should reject when insufficient stock
     */
    public function test_pos_counter_order_rejected_when_insufficient_stock()
    {
        // Create batch with limited stock
        ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 3, // Only 3 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Get the batch
        $batch = ProductBatch::where('product_id', $this->product->id)->first();

        // Attempt to create counter order requesting MORE than available
        $response = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'counter',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Walk-in Customer',
                    'phone' => '01700000002',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 10, // Requesting 10, only 3 available
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        // Should be rejected with error
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        
        // Verify error message mentions insufficient stock
        $this->assertStringContainsString('Insufficient stock', $response->json('message'));
    }

    /**
     * Test: POS (counter) orders should succeed and deduct stock immediately
     */
    public function test_pos_counter_order_succeeds_and_deducts_stock()
    {
        // Create batch with sufficient stock
        $batch = ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 10, // 10 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Create counter order
        $response = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'counter',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Walk-in Customer',
                    'phone' => '01700000002',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 5, // Order 5
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        // Should succeed
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify stock was deducted
        $batch->refresh();
        $this->assertEquals(5, $batch->quantity); // 10 - 5 = 5
    }

    /**
     * Test: Social commerce orders should reject when insufficient stock
     */
    public function test_social_commerce_order_rejected_when_insufficient_stock()
    {
        // Create batch with limited stock
        ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 3, // Only 3 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        $batch = ProductBatch::where('product_id', $this->product->id)->first();

        // Attempt social commerce order
        $response = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'social_commerce',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Social Commerce Customer',
                    'phone' => '01700000003',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 8, // Requesting 8, only 3 available
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        // Should be rejected
        $response->assertStatus(500);
        $response->assertJson([
            'success' => false,
        ]);
        
        $this->assertStringContainsString('Insufficient stock', $response->json('message'));
    }

    /**
     * Test: Social commerce orders should succeed and deduct stock immediately
     */
    public function test_social_commerce_order_succeeds_and_deducts_stock()
    {
        // Create batch with sufficient stock
        $batch = ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 15, // 15 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Create social commerce order
        $response = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'social_commerce',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Social Commerce Customer',
                    'phone' => '01700000003',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 7, // Order 7
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        // Should succeed
        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
        ]);

        // Verify stock was deducted
        $batch->refresh();
        $this->assertEquals(8, $batch->quantity); // 15 - 7 = 8
    }

    /**
     * Test: Verify exact stock limit - if stock is 3, max order is 3
     */
    public function test_exact_stock_limit_enforcement()
    {
        // Create batch with EXACTLY 3 items
        $batch = ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 3, // Exactly 3 in stock
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Test 1: Order EXACTLY 3 - should succeed
        $response1 = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'counter',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Customer 1',
                    'phone' => '01700000010',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'batch_id' => $batch->id,
                        'quantity' => 3, // Order exactly 3
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        $response1->assertStatus(201);
        
        // Verify stock is now 0
        $batch->refresh();
        $this->assertEquals(0, $batch->quantity);

        // Test 2: Try to order 1 more - should fail
        $batch2 = ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-002',
            'quantity' => 3,
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        $response2 = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'counter',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Customer 2',
                    'phone' => '01700000011',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'batch_id' => $batch2->id,
                        'quantity' => 4, // Try to order 4, but only 3 available
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        $response2->assertStatus(500);
        $this->assertStringContainsString('Insufficient stock', $response2->json('message'));
    }

    /**
     * Test: Pre-orders should NOT deduct stock (separate handling)
     */
    public function test_preorder_does_not_deduct_stock()
    {
        // Create batch with stock
        $batch = ProductBatch::create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 5,
            'cost_price' => 80.00,
            'sell_price' => 100.00,
            'tax_percentage' => 0,
        ]);

        // Create pre-order (no batch_id provided)
        $response = $this->actingAs($this->employee, 'employee')
            ->postJson('/api/orders', [
                'order_type' => 'counter',
                'store_id' => $this->store->id,
                'customer' => [
                    'name' => 'Pre-order Customer',
                    'phone' => '01700000020',
                ],
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        // No batch_id - indicates pre-order
                        'quantity' => 10,
                        'unit_price' => 100.00,
                    ],
                ],
            ]);

        $response->assertStatus(201);
        
        // Verify stock NOT deducted (still 5)
        $batch->refresh();
        $this->assertEquals(5, $batch->quantity);

        // Verify order marked as pre-order
        $order = \App\Models\Order::latest()->first();
        $this->assertTrue($order->is_preorder);
    }
}
