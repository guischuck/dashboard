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
        // Adicionar campo is_global para petition_templates
        Schema::table('petition_templates', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('is_active');
            
            // Permitir company_id nulo para modelos globais
            $table->unsignedBigInteger('company_id')->nullable()->change();
        });

        // Adicionar campo is_global para workflow_templates
        Schema::table('workflow_templates', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('is_active');
            $table->unsignedBigInteger('company_id')->nullable()->after('is_global');
            
            // Adicionar foreign key para company_id
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petition_templates', function (Blueprint $table) {
            $table->dropColumn('is_global');
        });

        Schema::table('workflow_templates', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['is_global', 'company_id']);
        });
    }
};
