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
        Schema::table('employment_relationships', function (Blueprint $table) {
            $table->string('cargo')->nullable()->after('notes');
            $table->text('documentos')->nullable()->after('cargo');
            $table->text('observacoes')->nullable()->after('documentos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employment_relationships', function (Blueprint $table) {
            $table->dropColumn(['cargo', 'documentos', 'observacoes']);
        });
    }
};
