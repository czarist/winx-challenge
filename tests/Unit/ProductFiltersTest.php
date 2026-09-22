<?php

namespace Tests\Unit;

use App\DataTransferObjects\ProductFilters;
use PHPUnit\Framework\TestCase;

class ProductFiltersTest extends TestCase
{
    public function test_it_defaults_to_no_filters_and_a_page_size_of_15(): void
    {
        $filters = ProductFilters::fromArray([]);

        $this->assertNull($filters->search);
        $this->assertNull($filters->categoria);
        $this->assertNull($filters->precoMin);
        $this->assertNull($filters->precoMax);
        $this->assertNull($filters->emEstoque);
        $this->assertSame(15, $filters->perPage);
    }

    public function test_it_casts_query_string_values_to_the_right_types(): void
    {
        $filters = ProductFilters::fromArray([
            'search' => 'mouse',
            'categoria' => 'Periféricos',
            'preco_min' => '10.5',
            'preco_max' => '200',
            'em_estoque' => 'true',
            'per_page' => '30',
        ]);

        $this->assertSame('mouse', $filters->search);
        $this->assertSame('Periféricos', $filters->categoria);
        $this->assertSame(10.5, $filters->precoMin);
        $this->assertSame(200.0, $filters->precoMax);
        $this->assertTrue($filters->emEstoque);
        $this->assertSame(30, $filters->perPage);
    }

    public function test_it_parses_falsy_stock_filter_values(): void
    {
        $this->assertFalse(ProductFilters::fromArray(['em_estoque' => 'false'])->emEstoque);
        $this->assertFalse(ProductFilters::fromArray(['em_estoque' => '0'])->emEstoque);
        $this->assertTrue(ProductFilters::fromArray(['em_estoque' => '1'])->emEstoque);
    }
}
