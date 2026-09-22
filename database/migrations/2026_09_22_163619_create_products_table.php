<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2);
            $table->string('categoria');
            $table->unsignedInteger('estoque')->default(0);
            $table->timestamps();

            $table->index('categoria');
            $table->index('preco');
            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
