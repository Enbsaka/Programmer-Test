<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EcommerceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_details_include_categories(): void
    {
        $category = Category::query()->create(['name' => 'Perifericos']);

        $product = Product::query()->create([
            'name' => 'Mouse Vertical',
            'description' => 'Mouse ergonomico para trabalho.',
            'price' => 199.90,
            'stock' => 15,
        ]);

        $product->categories()->attach($category->id);

        $response = $this->getJson("/api/products/{$product->id}");

        $response
            ->assertOk()
            ->assertJsonPath('name', 'Mouse Vertical')
            ->assertJsonPath('categories.0.name', 'Perifericos');
    }

    public function test_authenticated_customer_can_create_and_update_an_order(): void
    {
        $user = User::query()->create([
            'name' => 'Teste',
            'email' => 'teste@example.com',
            'password' => '12345678',
        ]);

        $user->customer()->create([
            'name' => 'Cliente Teste',
            'email' => 'teste@example.com',
            'document' => '98765432100',
        ]);

        $product = Product::query()->create([
            'name' => 'Monitor 24',
            'description' => 'Monitor Full HD.',
            'price' => 899.90,
            'stock' => 5,
        ]);

        Sanctum::actingAs($user);

        $storeResponse = $this->postJson('/api/orders', [
            'payment_method' => 'pix',
            'products' => [
                [
                    'id' => $product->id,
                    'quantity' => 2,
                ],
            ],
        ]);

        $storeResponse
            ->assertCreated()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('payments.0.method', 'pix');

        $orderId = $storeResponse->json('id');

        $this->patchJson("/api/orders/{$orderId}", [
            'status' => 'paid',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'paid');
    }
}
