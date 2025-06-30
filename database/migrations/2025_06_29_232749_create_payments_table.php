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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_subscription_id')->constrained()->onDelete('cascade');
            $table->string('payment_id')->unique(); // ID do pagamento no gateway
            $table->enum('status', ['pending', 'processing', 'paid', 'failed', 'cancelled', 'refunded']);
            $table->enum('payment_method', ['credit_card', 'debit_card', 'bank_transfer', 'pix', 'boleto']);
            $table->decimal('amount', 10, 2); // Valor do pagamento
            $table->string('currency', 3)->default('BRL'); // Moeda
            $table->datetime('paid_at')->nullable(); // Data do pagamento
            $table->datetime('due_date')->nullable(); // Data de vencimento
            $table->string('gateway')->nullable(); // Gateway de pagamento (stripe, mercadopago, etc)
            $table->string('gateway_payment_id')->nullable(); // ID no gateway
            $table->json('gateway_response')->nullable(); // Resposta do gateway
            $table->text('failure_reason')->nullable(); // Motivo da falha
            $table->timestamps();

            $table->index(['status', 'paid_at']);
            $table->index('due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
