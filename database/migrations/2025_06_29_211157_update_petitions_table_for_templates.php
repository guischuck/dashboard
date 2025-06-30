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
        Schema::table('petitions', function (Blueprint $table) {
            // Verificar se as colunas não existem antes de adicionar
            if (!Schema::hasColumn('petitions', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users');
            }
            if (!Schema::hasColumn('petitions', 'template_variables')) {
                $table->json('template_variables')->nullable();
            }
            if (!Schema::hasColumn('petitions', 'ai_prompt')) {
                $table->text('ai_prompt')->nullable();
            }
            if (!Schema::hasColumn('petitions', 'ai_tokens_used')) {
                $table->integer('ai_tokens_used')->nullable();
            }
            if (!Schema::hasColumn('petitions', 'generated_at')) {
                $table->timestamp('generated_at')->nullable();
            }
            if (!Schema::hasColumn('petitions', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable();
            }
            if (!Schema::hasColumn('petitions', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('petitions', 'version')) {
                $table->string('version')->default('1.0');
            }
            
            // Índices para melhor performance
            if (!Schema::hasColumn('petitions', 'status')) {
                $table->index(['status', 'created_at']);
            }
            if (Schema::hasColumn('petitions', 'category')) {
                $table->index(['category', 'created_at']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petitions', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'template_variables',
                'ai_prompt',
                'ai_tokens_used',
                'generated_at',
                'submitted_at',
                'notes',
                'version'
            ]);
        });
    }
};
