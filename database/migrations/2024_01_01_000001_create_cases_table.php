<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cases', function (Blueprint $table) {
            $table->id();
            $table->string('case_number')->unique();
            $table->string('client_name');
            $table->string('client_cpf', 14);
            $table->string('benefit_type'); // Tipo de benefício INSS
            $table->enum('status', ['pending', 'analysis', 'completed', 'requirement', 'rejected'])->default('pending');
            $table->text('description')->nullable();
            $table->decimal('estimated_value', 10, 2)->nullable();
            $table->decimal('success_fee', 5, 2)->default(20.00); // Percentual de sucesso
            $table->date('filing_date')->nullable();
            $table->date('decision_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cases');
    }
}; 