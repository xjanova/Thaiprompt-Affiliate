<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Vendor;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function guests_can_view_product_listing()
    {
        Product::factory()->count(5)->create();

        $response = $this->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products');
    }

    /** @test */
    public function guests_can_view_single_product()
    {
        $product = Product::factory()->create();

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertViewIs('products.show');
        $response->assertViewHas('product');
        $response->assertSee($product->name);
    }

    /** @test */
    public function it_can_filter_products_by_category()
    {
        $category = Category::factory()->create();
        $product1 = Product::factory()->create(['category_id' => $category->id]);
        $product2 = Product::factory()->create();

        $response = $this->get(route('products.index', ['category' => $category->id]));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertDontSee($product2->name);
    }

    /** @test */
    public function it_can_search_products()
    {
        $product1 = Product::factory()->create(['name' => 'iPhone 15 Pro']);
        $product2 = Product::factory()->create(['name' => 'Samsung Galaxy']);

        $response = $this->get(route('products.index', ['search' => 'iPhone']));

        $response->assertStatus(200);
        $response->assertSee($product1->name);
        $response->assertDontSee($product2->name);
    }

    /** @test */
    public function it_can_filter_products_by_price_range()
    {
        $product1 = Product::factory()->create(['price' => 1000]);
        $product2 = Product::factory()->create(['price' => 5000]);
        $product3 = Product::factory()->create(['price' => 10000]);

        $response = $this->get(route('products.index', [
            'min_price' => 2000,
            'max_price' => 8000
        ]));

        $response->assertStatus(200);
        $response->assertDontSee($product1->name);
        $response->assertSee($product2->name);
        $response->assertDontSee($product3->name);
    }

    /** @test */
    public function authenticated_users_can_add_product_to_cart()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock_quantity' => 10]);

        $response = $this->actingAs($user)
            ->post(route('cart.add'), [
                'product_id' => $product->id,
                'quantity' => 2
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);
    }

    /** @test */
    public function it_shows_out_of_stock_products_correctly()
    {
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'stock_status' => 'out_of_stock'
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('สินค้าหมด');
    }

    /** @test */
    public function it_calculates_discount_correctly()
    {
        $product = Product::factory()->create([
            'price' => 1000,
            'sale_price' => 800
        ]);

        $this->assertTrue($product->hasDiscount());
        $this->assertEquals(800, $product->getFinalPrice());
        $this->assertEquals(20, $product->getDiscountPercentage());
    }
}
