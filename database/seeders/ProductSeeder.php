<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            if (Product::query()->exists()) {
                return;
            }

            Product::factory()->count(40)->create();
            Product::factory()->count(10)->semEstoque()->create();
        });
    }
}
