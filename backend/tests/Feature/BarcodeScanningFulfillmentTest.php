<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductBatch;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BarcodeScanningFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private Employee $storeEmployee;
    private Store $store;
    private Order $order;
    private OrderItem $orderItem;
    private Product $product;
    private ProductBatch $batch;
    private ProductBarcode $barcode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        
        $this->storeEmployee = Employee::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->product = Product::factory()->create();

        $this->order = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'assigned_to_store',
            'order_type' => 'ecommerce',
        ]);

        $this->orderItem = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => 1,
            'product_barcode_id' => null,
        ]);

        $this->batch = ProductBatch::factory()->create([
            'product_id' => $this->product->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
        ]);

        $this->barcode = ProductBarcode::factory()->create([
            'barcode' => 'BC123456789',
            'product_id' => $this->product->id,
            'batch_id' => $this->batch->id,
            'current_store_id' => $this->store->id,
            'current_status' => 'in_shop',
        ]);
    }

    /** @test */
    public function it_scans_barcode_successfully()
    {
        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Barcode scanned successfully',
            ]);

        // Verify order item was updated
        $this->assertDatabaseHas('order_items', [
            'id' => $this->orderItem->id,
            'product_barcode_id' => $this->barcode->id,
            'product_batch_id' => $this->batch->id,
        ]);

        // Verify barcode status was updated
        $this->assertDatabaseHas('product_barcodes', [
            'id' => $this->barcode->id,
            'current_status' => 'in_shipment',
        ]);

        // Verify batch quantity was deducted
        $this->assertEquals(4, $this->batch->fresh()->quantity);
    }

    /** @test */
    public function it_transitions_status_to_picking_on_first_scan()
    {
        // Create a second order item so the order isn't complete after first scan
        $product2 = Product::factory()->create();
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'quantity' => 1,
            'product_barcode_id' => null,
        ]);

        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(200);

        // Order status should change to 'picking' (not complete yet)
        $this->assertDatabaseHas('orders', [
            'id' => $this->order->id,
            'status' => 'picking',
        ]);
    }

    /** @test */
    public function it_transitions_status_to_ready_for_shipment_on_last_scan()
    {
        $token = auth('api')->login($this->storeEmployee);

        // This is the only item, so scanning it should complete the order
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'order_status' => 'ready_for_shipment',
                ],
            ]);

        // Order should be marked as ready for shipment
        $order = $this->order->fresh();
        $this->assertEquals('ready_for_shipment', $order->status);
        $this->assertNotNull($order->fulfilled_at);
        $this->assertEquals($this->storeEmployee->id, $order->fulfilled_by);
    }

    /** @test */
    public function it_rejects_invalid_barcode()
    {
        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'INVALID-BARCODE',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Barcode not found or not available in this store',
            ]);
    }

    /** @test */
    public function it_rejects_barcode_from_wrong_product()
    {
        $wrongProduct = Product::factory()->create();
        $wrongBarcode = ProductBarcode::factory()->create([
            'barcode' => 'BC999999999',
            'product_id' => $wrongProduct->id,
            'current_store_id' => $this->store->id,
            'current_status' => 'in_shop',
        ]);

        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC999999999',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Scanned barcode does not match the order item product',
            ]);
    }

    /** @test */
    public function it_prevents_duplicate_scanning()
    {
        // First scan
        $this->orderItem->update([
            'product_barcode_id' => $this->barcode->id,
        ]);

        $token = auth('api')->login($this->storeEmployee);

        // Try to scan again
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'This order item has already been scanned',
            ]);
    }

    /** @test */
    public function it_rejects_barcode_from_different_store()
    {
        $otherStore = Store::factory()->create();
        $otherBarcode = ProductBarcode::factory()->create([
            'barcode' => 'BC888888888',
            'product_id' => $this->product->id,
            'current_store_id' => $otherStore->id,
            'current_status' => 'in_shop',
        ]);

        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC888888888',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Barcode not found or not available in this store',
            ]);
    }

    /** @test */
    public function it_rejects_barcode_with_wrong_status()
    {
        $this->barcode->update(['current_status' => 'in_warehouse']);

        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Barcode not found or not available in this store',
            ]);
    }

    /** @test */
    public function it_stores_scan_metadata()
    {
        $token = auth('api')->login($this->storeEmployee);

        $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $barcode = $this->barcode->fresh();
        $this->assertEquals($this->order->id, $barcode->location_metadata['order_id']);
        $this->assertEquals($this->order->order_number, $barcode->location_metadata['order_number']);
        $this->assertArrayHasKey('scanned_at', $barcode->location_metadata);
        $this->assertEquals($this->storeEmployee->id, $barcode->location_metadata['scanned_by']);
    }

    /** @test */
    public function it_returns_fulfillment_progress()
    {
        // Create order with multiple items
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'quantity' => 1,
            'product_barcode_id' => null,
        ]);

        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'fulfillment_progress' => [
                        'fulfilled_items' => 1,
                        'total_items' => 2,
                        'percentage' => 50.0,
                        'is_complete' => false,
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            // Missing barcode and order_item_id
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['barcode', 'order_item_id']);
    }

    /** @test */
    public function it_requires_authentication()
    {
        $response = $this->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_rejects_if_order_not_in_correct_status()
    {
        $this->order->update(['status' => 'shipped']);

        $token = auth('api')->login($this->storeEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(500); // Will throw ModelNotFoundException
    }

    /** @test */
    public function it_only_allows_employees_from_same_store()
    {
        $otherStore = Store::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'store_id' => $otherStore->id,
        ]);

        $token = auth('api')->login($otherEmployee);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response->assertStatus(500); // Order not found for their store
    }

    /** @test */
    public function it_handles_multiple_items_scanning_correctly()
    {
        // Create second item
        $product2 = Product::factory()->create();
        $orderItem2 = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'product_id' => $product2->id,
            'product_name' => $product2->name,
            'quantity' => 1,
        ]);

        $batch2 = ProductBatch::factory()->create([
            'product_id' => $product2->id,
            'store_id' => $this->store->id,
            'quantity' => 5,
        ]);

        $barcode2 = ProductBarcode::factory()->create([
            'barcode' => 'BC222222222',
            'product_id' => $product2->id,
            'batch_id' => $batch2->id,
            'current_store_id' => $this->store->id,
            'current_status' => 'in_shop',
        ]);

        $token = auth('api')->login($this->storeEmployee);

        // Scan first item
        $response1 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC123456789',
            'order_item_id' => $this->orderItem->id,
        ]);

        $response1->assertStatus(200);
        $this->assertEquals('picking', $this->order->fresh()->status);

        // Scan second item
        $response2 = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
        ])->postJson("/api/store/fulfillment/orders/{$this->order->id}/scan-barcode", [
            'barcode' => 'BC222222222',
            'order_item_id' => $orderItem2->id,
        ]);

        $response2->assertStatus(200);
        $this->assertEquals('ready_for_shipment', $this->order->fresh()->status);
    }
}
