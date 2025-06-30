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
        Schema::create('historico_situacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_processo')->constrained('processos')->onDelete('cascade');
            $table->string('situacao_anterior')->nullable();
            $table->string('situacao_atual');
            $table->timestamp('data_mudanca')->useCurrent();
            $table->foreignId('id_empresa')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            
            // Índices
            $table->index(['id_processo', 'id_empresa']);
            $table->index(['data_mudanca']);
            $table->index(['id_empresa']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_situacoes');
    }
}; 