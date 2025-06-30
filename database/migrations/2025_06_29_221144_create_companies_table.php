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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome da empresa/escritório
            $table->string('slug')->unique(); // Slug único para URLs
            $table->string('cnpj', 18)->nullable(); // CNPJ da empresa
            $table->string('email')->nullable(); // Email principal da empresa
            $table->string('phone')->nullable(); // Telefone da empresa
            $table->text('address')->nullable(); // Endereço completo
            $table->string('city')->nullable(); // Cidade
            $table->string('state', 2)->nullable(); // Estado (UF)
            $table->string('zip_code', 10)->nullable(); // CEP
            $table->string('logo_path')->nullable(); // Caminho do logo
            $table->json('settings')->nullable(); // Configurações específicas da empresa
            $table->enum('plan', ['basic', 'premium', 'enterprise'])->default('basic'); // Plano contratado
            $table->integer('max_users')->default(5); // Máximo de usuários permitidos
            $table->integer('max_cases')->default(100); // Máximo de casos permitidos
            $table->boolean('is_active')->default(true); // Se a empresa está ativa
            $table->timestamp('trial_ends_at')->nullable(); // Fim do período de teste
            $table->timestamp('subscription_ends_at')->nullable(); // Fim da assinatura
            $table->timestamps();
            
            // Índices
            $table->index('slug');
            $table->index('is_active');
            $table->index('plan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
