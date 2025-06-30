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
        Schema::create('company_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['trial', 'active', 'suspended', 'cancelled', 'expired'])->default('trial');
            $table->datetime('trial_ends_at')->nullable(); // Fim do período de trial
            $table->datetime('current_period_start'); // Início do período atual
            $table->datetime('current_period_end'); // Fim do período atual
            $table->datetime('cancelled_at')->nullable(); // Data de cancelamento
            $table->datetime('ends_at')->nullable(); // Data de término definitivo
            $table->decimal('amount', 10, 2); // Valor da assinatura
            $table->string('currency', 3)->default('BRL'); // Moeda
            $table->json('metadata')->nullable(); // Dados adicionais
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('current_period_end');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_subscriptions');
    }
};
