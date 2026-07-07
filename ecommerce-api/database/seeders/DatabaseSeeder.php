<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::query()->create([
            'name' => 'Enzo Demo',
            'email' => 'enzo@example.com',
            'password' => Hash::make('12345678'),
        ]);

        $user->customer()->create([
            'name' => 'Enzo Demo',
            'email' => 'enzo@example.com',
            'document' => '12345678900',
        ]);

        $categories = collect([
            'Notebooks',
            'Monitores',
            'Perifericos',
            'Componentes',
        ])->mapWithKeys(function ($name) {
            $category = Category::query()->create(['name' => $name]);

            return [$name => $category];
        });

        $products = [
            [
                'name' => 'Notebook Pro 14',
                'description' => 'Notebook leve para produtividade com 16GB de RAM e SSD NVMe.',
                'price' => 6499.90,
                'stock' => 7,
                'categories' => ['Notebooks'],
            ],
            [
                'name' => 'Monitor UltraWide 29',
                'description' => 'Monitor ultrawide ideal para multitarefa e setup de desenvolvimento.',
                'price' => 1799.90,
                'stock' => 10,
                'categories' => ['Monitores'],
            ],
            [
                'name' => 'Mouse Gamer Precision',
                'description' => 'Mouse ergonomico com sensor de alta precisao e botoes programaveis.',
                'price' => 249.90,
                'stock' => 25,
                'categories' => ['Perifericos'],
            ],
            [
                'name' => 'Teclado Mecanico RGB',
                'description' => 'Teclado mecanico com switches tatteis e iluminacao configuravel.',
                'price' => 399.90,
                'stock' => 18,
                'categories' => ['Perifericos'],
            ],
            [
                'name' => 'SSD NVMe 1TB',
                'description' => 'Armazenamento de alta velocidade para notebooks e desktops.',
                'price' => 519.90,
                'stock' => 30,
                'categories' => ['Componentes'],
            ],
        ];

        foreach ($products as $item) {
            $product = Product::query()->create([
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => $item['price'],
                'stock' => $item['stock'],
            ]);

            $product->categories()->attach(
                collect($item['categories'])->map(fn ($name) => $categories[$name]->id)->all()
            );
        }
    }
}
