<?php

namespace Tests\Unit\Controllers;

use App\Http\Controllers\EcommerceCatalogController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductImage;
use App\Models\Store;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EcommerceCatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected EcommerceCatalogController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new EcommerceCatalogController();
    }

    /** @test */
    public function get_products_returns_paginated_products()
    {
        // Create test data
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        ProductBatch::factory()->create([
            'product_id' => $product->id,
            'quantity' => 10,
            'sell_price' => 100.00
        ]);
        ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);

        $request = Request::create('/catalog/products', 'GET');

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('products', $data['data']);
        $this->assertArrayHasKey('pagination', $data['data']);
        $this->assertCount(1, $data['data']['products']);
        $this->assertEquals($product->id, $data['data']['products'][0]['id']);
        $this->assertEquals(100.00, $data['data']['products'][0]['selling_price']);
    }

    /** @test */
    public function get_products_filters_by_category()
    {
        $category1 = Category::factory()->create(['title' => 'Electronics']);
        $category2 = Category::factory()->create(['title' => 'Clothing']);

        $product1 = Product::factory()->create(['category_id' => $category1->id]);
        $product2 = Product::factory()->create(['category_id' => $category2->id]);

        ProductBatch::factory()->create(['product_id' => $product1->id, 'quantity' => 10]);
        ProductBatch::factory()->create(['product_id' => $product2->id, 'quantity' => 10]);

        $request = Request::create('/catalog/products', 'GET', ['category' => 'Electronics']);

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']['products']);
        $this->assertEquals($product1->id, $data['data']['products'][0]['id']);
    }

    /** @test */
    public function get_products_filters_by_price_range()
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        ProductBatch::factory()->create(['product_id' => $product1->id, 'quantity' => 10, 'sell_price' => 50.00]);
        ProductBatch::factory()->create(['product_id' => $product2->id, 'quantity' => 10, 'sell_price' => 150.00]);

        $request = Request::create('/catalog/products', 'GET', ['min_price' => 100, 'max_price' => 200]);

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']['products']);
        $this->assertEquals($product2->id, $data['data']['products'][0]['id']);
    }

    /** @test */
    public function get_products_searches_by_name()
    {
        $product1 = Product::factory()->create(['name' => 'iPhone 15']);
        $product2 = Product::factory()->create(['name' => 'Samsung Galaxy']);

        ProductBatch::factory()->create(['product_id' => $product1->id, 'quantity' => 10]);
        ProductBatch::factory()->create(['product_id' => $product2->id, 'quantity' => 10]);

        $request = Request::create('/catalog/products', 'GET', ['search' => 'iPhone']);

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']['products']);
        $this->assertEquals($product1->id, $data['data']['products'][0]['id']);
    }

    /** @test */
    public function get_products_filters_in_stock_only()
    {
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        ProductBatch::factory()->create(['product_id' => $product1->id, 'quantity' => 10]);
        ProductBatch::factory()->create(['product_id' => $product2->id, 'quantity' => 0]);

        $request = Request::create('/catalog/products', 'GET', ['in_stock' => true]);

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(1, $data['data']['products']);
        $this->assertEquals($product1->id, $data['data']['products'][0]['id']);
    }

    /** @test */
    public function get_product_returns_single_product()
    {
        $product = Product::factory()->create();
        ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10, 'sell_price' => 100.00]);
        ProductImage::factory()->create(['product_id' => $product->id, 'is_primary' => true]);

        $request = Request::create("/catalog/products/{$product->id}", 'GET');

        $response = $this->controller->getProduct($request, $product->id);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('product', $data['data']);
        $this->assertArrayHasKey('related_products', $data['data']);
        $this->assertEquals($product->id, $data['data']['product']['id']);
        $this->assertEquals(100.00, $data['data']['product']['selling_price']);
    }

    /** @test */
    public function get_product_returns_404_for_nonexistent_product()
    {
        $request = Request::create('/catalog/products/999', 'GET');

        $response = $this->controller->getProduct($request, 999);
        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Product not found', $data['message']);
        $this->assertEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function get_categories_returns_category_tree()
    {
        $parentCategory = Category::factory()->create(['parent_id' => null, 'is_active' => true]);
        $childCategory = Category::factory()->create(['parent_id' => $parentCategory->id, 'is_active' => true]);

        $request = Request::create('/catalog/categories', 'GET');

        $response = $this->controller->getCategories($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('categories', $data['data']);
        $this->assertCount(1, $data['data']['categories']); // Only root categories
        $this->assertEquals($parentCategory->id, $data['data']['categories'][0]['id']);
        $this->assertCount(1, $data['data']['categories'][0]['children']);
    }

    /** @test */
    public function get_featured_products_returns_recent_products_with_stock()
    {
        $product = Product::factory()->create(['created_at' => now()]);
        ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10, 'sell_price' => 100.00]);
        ProductImage::factory()->create(['product_id' => $product->id]);

        $request = Request::create('/catalog/featured-products', 'GET');

        $response = $this->controller->getFeaturedProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('featured_products', $data['data']);
        $this->assertCount(1, $data['data']['featured_products']);
        $this->assertEquals($product->id, $data['data']['featured_products'][0]['id']);
    }

    /** @test */
    public function search_products_returns_search_results_with_suggestions()
    {
        $product = Product::factory()->create(['name' => 'iPhone 15 Pro']);
        ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10, 'sell_price' => 100.00]);

        $request = Request::create('/catalog/search', 'GET', ['q' => 'iPhone']);

        $response = $this->controller->searchProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('products', $data['data']);
        $this->assertArrayHasKey('suggestions', $data['data']);
        $this->assertCount(1, $data['data']['products']);
        $this->assertEquals($product->id, $data['data']['products'][0]['id']);
    }

    /** @test */
    public function search_products_returns_error_for_short_query()
    {
        $request = Request::create('/catalog/search', 'GET', ['q' => 'i']);

        $response = $this->controller->searchProducts($request);
        $data = $response->getData(true);

        $this->assertFalse($data['success']);
        $this->assertEquals('Search query must be at least 2 characters', $data['message']);
        $this->assertEquals(400, $response->getStatusCode());
    }

    /** @test */
    public function get_price_range_returns_min_max_prices()
    {
        ProductBatch::factory()->create(['quantity' => 10, 'sell_price' => 50.00]);
        ProductBatch::factory()->create(['quantity' => 10, 'sell_price' => 200.00]);

        $response = $this->controller->getPriceRange();
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('min_price', $data['data']);
        $this->assertArrayHasKey('max_price', $data['data']);
        $this->assertEquals(50.00, $data['data']['min_price']);
        $this->assertEquals(200.00, $data['data']['max_price']);
    }

    /** @test */
    public function get_new_arrivals_returns_recently_added_products()
    {
        $recentProduct = Product::factory()->create(['created_at' => now()->subDays(5)]);
        $oldProduct = Product::factory()->create(['created_at' => now()->subDays(60)]);

        ProductBatch::factory()->create(['product_id' => $recentProduct->id, 'quantity' => 10, 'sell_price' => 100.00]);
        ProductBatch::factory()->create(['product_id' => $oldProduct->id, 'quantity' => 10, 'sell_price' => 100.00]);

        $request = Request::create('/catalog/new-arrivals', 'GET', ['days' => 30]);

        $response = $this->controller->getNewArrivals($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertArrayHasKey('new_arrivals', $data['data']);
        $this->assertCount(1, $data['data']['new_arrivals']);
        $this->assertEquals($recentProduct->id, $data['data']['new_arrivals'][0]['id']);
    }

    /** @test */
    public function get_products_handles_pagination_parameters()
    {
        // Create multiple products
        for ($i = 0; $i < 15; $i++) {
            $product = Product::factory()->create();
            ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
        }

        $request = Request::create('/catalog/products', 'GET', ['per_page' => 5]);

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(5, $data['data']['products']);
        $this->assertEquals(5, $data['data']['pagination']['per_page']);
        $this->assertEquals(15, $data['data']['pagination']['total']);
    }

    /** @test */
    public function get_products_respects_max_per_page_limit()
    {
        $request = Request::create('/catalog/products', 'GET', ['per_page' => 100]);

        $response = $this->controller->getProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertLessThanOrEqual(50, $data['data']['pagination']['per_page']);
    }

    /** @test */
    public function get_featured_products_respects_limit_parameter()
    {
        // Create multiple products
        for ($i = 0; $i < 10; $i++) {
            $product = Product::factory()->create();
            ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
        }

        $request = Request::create('/catalog/featured-products', 'GET', ['limit' => 3]);

        $response = $this->controller->getFeaturedProducts($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(3, $data['data']['featured_products']);
    }

    /** @test */
    public function get_new_arrivals_respects_limit_parameter()
    {
        // Create multiple recent products
        for ($i = 0; $i < 10; $i++) {
            $product = Product::factory()->create(['created_at' => now()->subDays(rand(1, 10))]);
            ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10]);
        }

        $request = Request::create('/catalog/new-arrivals', 'GET', ['limit' => 5]);

        $response = $this->controller->getNewArrivals($request);
        $data = $response->getData(true);

        $this->assertTrue($data['success']);
        $this->assertCount(5, $data['data']['new_arrivals']);
    }

    /** @test */
    public function get_categories_uses_cache()
    {
        $category = Category::factory()->create(['is_active' => true, 'parent_id' => null]);

        // First call should cache
        $request = Request::create('/catalog/categories', 'GET');
        $this->controller->getCategories($request);

        // Verify cache exists
        $this->assertTrue(Cache::has('ecommerce_categories'));
    }

    /** @test */
    public function get_featured_products_uses_cache()
    {
        $product = Product::factory()->create();
        ProductBatch::factory()->create(['product_id' => $product->id, 'quantity' => 10]);

        // First call should cache
        $request = Request::create('/catalog/featured-products', 'GET');
        $this->controller->getFeaturedProducts($request);

        // Verify cache exists (cache key includes limit)
        $this->assertTrue(Cache::has('featured_products_8'));
    }

    /** @test */
    public function get_price_range_uses_cache()
    {
        ProductBatch::factory()->create(['quantity' => 10, 'sell_price' => 100.00]);

        // First call should cache
        $this->controller->getPriceRange();

        // Verify cache exists
        $this->assertTrue(Cache::has('product_price_range'));
    }
}