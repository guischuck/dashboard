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
        Schema::create('petition_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do template
            $table->string('category'); // Categoria: recurso, requerimento, defesa, etc.
            $table->string('benefit_type')->nullable(); // Tipo de benefício específico
            $table->text('description')->nullable(); // Descrição do template
            $table->longText('content'); // Conteúdo do template com placeholders
            $table->json('variables')->nullable(); // Variáveis disponíveis no template
            $table->json('sections')->nullable(); // Seções do template
            $table->boolean('is_active')->default(true); // Se o template está ativo
            $table->boolean('is_default')->default(false); // Se é template padrão
            $table->foreignId('created_by')->constrained('users'); // Usuário que criou
            $table->timestamps();
            
            $table->index(['category', 'benefit_type']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petition_templates');
    }
};
