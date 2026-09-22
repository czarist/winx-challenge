<?php

namespace App\Console\Commands;

use App\Services\Search\ElasticsearchProductSearchService;
use Elastic\Transport\Exception\NoNodeAvailableException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('products:reindex')]
#[Description('Reindexa todos os produtos no Elasticsearch')]
class ReindexProducts extends Command
{
    public function handle(ElasticsearchProductSearchService $search): int
    {
        try {
            $search->reindex();
        } catch (NoNodeAvailableException $e) {
            $this->newLine();
            $this->error("Elasticsearch indisponível: {$e->getMessage()}");
            $this->line('A busca full-text (GET /products/search) ficará fora do ar até o índice ser gerado; rode "php artisan products:reindex" de novo quando o Elasticsearch estiver no ar.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Produtos reindexados com sucesso.');

        return self::SUCCESS;
    }
}
