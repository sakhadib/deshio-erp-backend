<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStoreAssignmentTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;
    private Order $order;
    private Product $product;
    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::factory()->create();
        $this->store = Store::factory()->create();
        $this->product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'order_type' => 'ecommerce',
            'status' => 'pending_assignment',
            'store_id' => null,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 2,
            'unit_price' => 1000.00,
        ]);
    }

    /** @test */
    public function it_assigns_order_to_store_successfully()
    {
        // Create sufficient inventory
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
            'notes' => 'Best availability',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => "Order successfully assigned to {$this->store->name}",
            ]);

        // Verify database was updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'store_id' => $this->store->id,
            'status' => 'assigned_to_store',
            'processed_by' => $this->employee->id,
        ]);

        // Verify metadata was stored
        $order = Order::find($this->order->id);
        $this->assertArrayHasKey('assigned_at', $order->metadata);
        $this->assertEquals($this->employee->id, $order->metadata['assigned_by']);
        $this->assertEquals('Best availability', $order->metadata['assignment_notes']);
    }

    /** @test */
    public function it_rejects_assignment_with_insufficient_inventory()
    {
        // Create insufficient inventory (need 2, only have 1)
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => "Insufficient inventory for product: {$this->product->name}",
            ]);

        // Verify order was NOT updated
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'store_id' => null,
            'status' => 'pending_assignment',
        ]);
    }

    /** @test */
    public function it_validates_store_exists()
    {
        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => 99999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['store_id']);
    }

    /** @test */
    public function it_rejects_assignment_if_order_not_pending()
    {
        $this->order->update(['status' => 'assigned_to_store']);

        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order is not pending assignment',
            ]);
    }

    /** @test */
    public function it_validates_inventory_for_multiple_products()
    {
        $product2 = Product::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'quantity' => 3,
            'unit_price' => 500.00,
        ]);

        // Product 1 has enough inventory
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        // Product 2 has insufficient inventory
        ProductBatch::factory()->create([
            'product_id' => $product2->id,
            'store_id' => $this->store->id,
            'quantity' => 2,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(400)
            ->assertJsonFragment([
                'required' => 3,
                'available' => 2,
            ]);
    }

    /** @test */
    public function it_excludes_expired_batches_from_inventory_check()
    {
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 10,
            'availability' => true,
            'expiry_date' => now()->subDay(),
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [
                    'available' => 0,
                ],
            ]);
    }

    /** @test */
    public function it_excludes_unavailable_batches_from_inventory_check()
    {
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 10,
            'availability' => false,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'data' => [
                    'available' => 0,
                ],
            ]);
    }

    /** @test */
    public function it_aggregates_inventory_from_multiple_batches()
    {
        // Multiple batches of same product
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        // Should succeed because 1 + 1 = 2 (exactly what we need)
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_notes_length()
    {
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
            'notes' => str_repeat('a', 501), // Exceeds max length
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    /** @test */
    public function it_handles_order_not_found()
    {
        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/99999/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(500); // Will catch ModelNotFoundException
    }

    /** @test */
    public function it_returns_order_with_store_details_after_assignment()
    {
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'order' => [
                        'id',
                        'order_number',
                        'store_id',
                        'status',
                        'store' => [
                            'id',
                            'name',
                            'address',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function concurrent_assignments_are_prevented()
    {
        // This test would require actual concurrency testing
        // For now, we'll simulate the race condition check
        
        ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        // First assignment should succeed
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response1->assertStatus(200);

        // Second assignment should fail (order no longer pending)
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/order-management/orders/{$this->order->id}/assign-store", [
            'store_id' => $this->store->id,
        ]);

        $response2->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order is not pending assignment',
            ]);
    }
}
