<?php

namespace App\DataTransferObjects;

final readonly class ProductFilters
{
    public function __construct(
        public ?string $search = null,
        public ?string $categoria = null,
        public ?float $precoMin = null,
        public ?float $precoMax = null,
        public ?bool $emEstoque = null,
        public int $perPage = 15,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     */
    public static function fromArray(array $query): self
    {
        return new self(
            search: $query['search'] ?? null,
            categoria: $query['categoria'] ?? null,
            precoMin: isset($query['preco_min']) ? (float) $query['preco_min'] : null,
            precoMax: isset($query['preco_max']) ? (float) $query['preco_max'] : null,
            emEstoque: isset($query['em_estoque']) ? filter_var($query['em_estoque'], FILTER_VALIDATE_BOOLEAN) : null,
            perPage: (int) ($query['per_page'] ?? 15),
        );
    }
}
