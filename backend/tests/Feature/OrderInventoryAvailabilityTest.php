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

class OrderInventoryAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;
    private Order $order;
    private Product $product1;
    private Product $product2;
    private Store $store1;
    private Store $store2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test employee
        $this->employee = Employee::factory()->create();

        // Create test stores
        $this->store1 = Store::factory()->create([
            'name' => 'Store 1',
            'is_warehouse' => false,
            'is_online' => true,
        ]);

        $this->store2 = Store::factory()->create([
            'name' => 'Store 2',
            'is_warehouse' => false,
            'is_online' => true,
        ]);

        // Create test products
        $this->product1 = Product::factory()->create([
            'name' => 'Product 1',
            'sku' => 'PROD-001',
        ]);

        $this->product2 = Product::factory()->create([
            'name' => 'Product 2',
            'sku' => 'PROD-002',
        ]);

        // Create test order
        $this->order = Order::factory()->create([
            'order_type' => 'ecommerce',
            'status' => 'pending_assignment',
            'store_id' => null,
        ]);

        // Create order items
        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product1->id,
            'product_name' => 'Product 1',
            'product_sku' => 'PROD-001',
            'quantity' => 2,
            'unit_price' => 1000.00,
        ]);

        OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product2->id,
            'product_name' => 'Product 2',
            'product_sku' => 'PROD-002',
            'quantity' => 1,
            'unit_price' => 500.00,
        ]);
    }

    /** @test */
    public function it_returns_store_with_full_inventory()
    {
        // Store 1 has full inventory
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 5,
            'availability' => true,
        ]);

        ProductBatch::factory()->create([
            'product_id' => $this->product2->id,
            'store_id' => $this->store1->id,
            'quantity' => 3,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $stores = $response->json('data.stores');
        $store1Data = collect($stores)->firstWhere('store_id', $this->store1->id);

        $this->assertTrue($store1Data['can_fulfill_entire_order']);
        $this->assertEquals(100.0, $store1Data['fulfillment_percentage']);
        $this->assertEquals(8, $store1Data['total_items_available']); // 5 + 3
        $this->assertEquals(3, $store1Data['total_items_required']); // 2 + 1
    }

    /** @test */
    public function it_returns_stores_with_partial_inventory()
    {
        // Store 1 has only Product 1
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 2,
            'availability' => true,
        ]);

        // Store 2 has only Product 2
        ProductBatch::factory()->create([
            'product_id' => $this->product2->id,
            'store_id' => $this->store2->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        
        // Both stores should have partial fulfillment
        foreach ($stores as $store) {
            $this->assertFalse($store['can_fulfill_entire_order']);
            $this->assertLessThan(100, $store['fulfillment_percentage']);
        }
    }

    /** @test */
    public function it_excludes_expired_batches()
    {
        // Store 1 has inventory but it's expired
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 10,
            'availability' => true,
            'expiry_date' => now()->subDays(1), // Expired yesterday
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        $store1Data = collect($stores)->firstWhere('store_id', $this->store1->id);

        // Should show 0 available for Product 1 due to expiry
        $product1Details = collect($store1Data['inventory_details'])
            ->firstWhere('product_id', $this->product1->id);
        
        $this->assertEquals(0, $product1Details['available_quantity']);
    }

    /** @test */
    public function it_excludes_unavailable_batches()
    {
        // Store 1 has inventory but availability is false
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 10,
            'availability' => false, // Not available
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        $store1Data = collect($stores)->firstWhere('store_id', $this->store1->id);

        $product1Details = collect($store1Data['inventory_details'])
            ->firstWhere('product_id', $this->product1->id);
        
        $this->assertEquals(0, $product1Details['available_quantity']);
    }

    /** @test */
    public function it_orders_stores_by_fulfillment_capability()
    {
        // Store 1: Full inventory
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 5,
            'availability' => true,
        ]);
        ProductBatch::factory()->create([
            'product_id' => $this->product2->id,
            'store_id' => $this->store1->id,
            'quantity' => 3,
            'availability' => true,
        ]);

        // Store 2: Partial inventory
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store2->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        
        // Store 1 should be first (can fulfill entire order)
        $this->assertEquals($this->store1->id, $stores[0]['store_id']);
        $this->assertTrue($stores[0]['can_fulfill_entire_order']);
    }

    /** @test */
    public function it_provides_recommendation_for_best_store()
    {
        // Store 1 has full inventory
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 5,
            'availability' => true,
        ]);
        ProductBatch::factory()->create([
            'product_id' => $this->product2->id,
            'store_id' => $this->store1->id,
            'quantity' => 3,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'recommendation' => [
                        'store_id' => $this->store1->id,
                        'reason' => 'Can fulfill entire order',
                        'fulfillment_percentage' => 100,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_recommends_best_partial_store_when_no_full_fulfillment()
    {
        // Store 1: 50% fulfillment
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        // Store 2: 33% fulfillment
        ProductBatch::factory()->create([
            'product_id' => $this->product2->id,
            'store_id' => $this->store2->id,
            'quantity' => 1,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $recommendation = $response->json('data.recommendation');
        
        // Should recommend Store 1 (higher percentage)
        $this->assertEquals($this->store1->id, $recommendation['store_id']);
        $this->assertEquals('Highest partial fulfillment capability', $recommendation['reason']);
        $this->assertArrayHasKey('note', $recommendation);
    }

    /** @test */
    public function it_returns_error_if_order_not_pending_assignment()
    {
        $this->order->update(['status' => 'assigned_to_store']);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Order is not pending assignment',
            ]);
    }

    /** @test */
    public function it_includes_batch_details_in_response()
    {
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'batch_number' => 'BATCH-001',
            'quantity' => 5,
            'sell_price' => 1200.00,
            'availability' => true,
            'expiry_date' => now()->addYear(),
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        $store1Data = collect($stores)->firstWhere('store_id', $this->store1->id);
        $product1Details = collect($store1Data['inventory_details'])
            ->firstWhere('product_id', $this->product1->id);

        $this->assertNotEmpty($product1Details['batches']);
        $batch = $product1Details['batches'][0];
        
        $this->assertEquals('BATCH-001', $batch['batch_number']);
        $this->assertEquals(5, $batch['quantity']);
        $this->assertEquals(1200.00, $batch['sell_price']);
        $this->assertNotNull($batch['expiry_date']);
    }

    /** @test */
    public function it_aggregates_multiple_batches_for_same_product()
    {
        // Multiple batches of Product 1 in Store 1
        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 2,
            'availability' => true,
        ]);

        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $this->store1->id,
            'quantity' => 3,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        $store1Data = collect($stores)->firstWhere('store_id', $this->store1->id);
        $product1Details = collect($store1Data['inventory_details'])
            ->firstWhere('product_id', $this->product1->id);

        // Total available should be 5 (2 + 3)
        $this->assertEquals(5, $product1Details['available_quantity']);
        $this->assertEquals(2, count($product1Details['batches']));
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(401);
    }

    /** @test */
    public function it_excludes_warehouse_stores()
    {
        $warehouse = Store::factory()->create([
            'name' => 'Warehouse',
            'is_warehouse' => true,
            'is_online' => true,
        ]);

        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $warehouse->id,
            'quantity' => 100,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        $warehouseInResults = collect($stores)->contains('store_id', $warehouse->id);

        $this->assertFalse($warehouseInResults);
    }

    /** @test */
    public function it_excludes_offline_stores()
    {
        $offlineStore = Store::factory()->create([
            'name' => 'Offline Store',
            'is_warehouse' => false,
            'is_online' => false,
        ]);

        ProductBatch::factory()->create([
            'product_id' => $this->product1->id,
            'store_id' => $offlineStore->id,
            'quantity' => 100,
            'availability' => true,
        ]);

        $token = auth('api')->login($this->employee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->getJson("/api/order-management/orders/{$this->order->id}/available-stores");

        $response->assertStatus(200);

        $stores = $response->json('data.stores');
        $offlineStoreInResults = collect($stores)->contains('store_id', $offlineStore->id);

        $this->assertFalse($offlineStoreInResults);
    }
}
