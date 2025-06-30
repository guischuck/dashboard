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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nome do plano (Básico, Premium, Enterprise)
            $table->string('slug')->unique(); // Slug para identificação
            $table->text('description')->nullable(); // Descrição do plano
            $table->decimal('price', 10, 2); // Preço do plano
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annual']); // Ciclo de cobrança
            $table->integer('max_users')->nullable(); // Máximo de usuários (null = ilimitado)
            $table->integer('max_cases')->nullable(); // Máximo de casos (null = ilimitado)
            $table->json('features')->nullable(); // Features do plano em JSON
            $table->boolean('is_active')->default(true); // Plano ativo
            $table->boolean('is_featured')->default(false); // Plano em destaque
            $table->integer('trial_days')->default(30); // Dias de trial
            $table->integer('sort_order')->default(0); // Ordem de exibição
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
