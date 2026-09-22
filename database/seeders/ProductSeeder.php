<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        Product::factory()->count(40)->create();
        Product::factory()->count(10)->semEstoque()->create();
    }
}
