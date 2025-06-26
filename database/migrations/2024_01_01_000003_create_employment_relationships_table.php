<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employment_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->string('employer_name');
            $table->string('employer_cnpj', 18)->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 10, 2)->nullable();
            $table->string('position')->nullable();
            $table->string('cbo_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employment_relationships');
    }
}; 