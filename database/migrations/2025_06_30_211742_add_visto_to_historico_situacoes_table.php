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
        Schema::table('historico_situacoes', function (Blueprint $table) {
            $table->boolean('visto')->default(false)->after('data_mudanca');
            $table->timestamp('visto_em')->nullable()->after('visto');
            $table->index(['visto']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('historico_situacoes', function (Blueprint $table) {
            $table->dropColumn(['visto', 'visto_em']);
        });
    }
};
