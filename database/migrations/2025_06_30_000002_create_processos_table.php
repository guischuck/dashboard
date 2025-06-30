<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('processos', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo')->index();
            $table->string('servico')->default('N/A');
            $table->string('situacao')->default('N/A');
            $table->string('situacao_anterior')->nullable();
            $table->timestamp('ultima_atualizacao')->nullable();
            $table->timestamp('protocolado_em')->nullable();
            $table->string('cpf');
            $table->string('nome')->default('N/A');
            $table->foreignId('id_empresa')->constrained('companies')->onDelete('cascade');
            $table->timestamp('criado_em')->nullable();
            $table->timestamp('atualizado_em')->nullable();
            $table->timestamps();
            
            // Índices
            $table->unique(['protocolo', 'id_empresa']);
            $table->index(['id_empresa', 'cpf']);
            $table->index(['situacao']);
            $table->index(['ultima_atualizacao']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('processos');
    }
}; 