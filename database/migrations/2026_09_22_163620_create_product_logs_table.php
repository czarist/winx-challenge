<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_logs', function (Blueprint $table) {
            $table->id();

            // Sem FK em product_id: a exclusão de um produto é síncrona no
            // controller, mas o log correspondente só é gravado depois, de
            // forma assíncrona. Nesse momento o produto já não existe mais,
            // então uma constraint de FK rejeitaria justamente o registro
            // de auditoria da própria exclusão. O id é mantido apenas como
            // referência histórica.
            $table->unsignedBigInteger('product_id')->nullable();
            $table->index('product_id');

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_logs');
    }
};
