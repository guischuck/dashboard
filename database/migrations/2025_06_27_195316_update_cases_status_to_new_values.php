<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primeiro, converter o enum para string
        Schema::table('cases', function (Blueprint $table) {
            $table->string('status')->default('pending')->change();
        });

        // Atualizar os status existentes para os novos valores
        DB::statement("UPDATE cases SET status = 'pendente' WHERE status = 'pending'");
        DB::statement("UPDATE cases SET status = 'em_coleta' WHERE status = 'analysis'");
        DB::statement("UPDATE cases SET status = 'aguarda_peticao' WHERE status = 'requirement'");
        DB::statement("UPDATE cases SET status = 'concluido' WHERE status = 'completed'");
        DB::statement("UPDATE cases SET status = 'rejeitado' WHERE status = 'rejected'");

        // Depois, converter para o novo enum
        Schema::table('cases', function (Blueprint $table) {
            $table->enum('status', [
                'pendente', 
                'em_coleta', 
                'aguarda_peticao', 
                'protocolado', 
                'concluido', 
                'rejeitado'
            ])->default('pendente')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Primeiro, converter o enum para string
        Schema::table('cases', function (Blueprint $table) {
            $table->string('status')->default('pendente')->change();
        });

        // Reverter os status para os valores antigos
        DB::statement("UPDATE cases SET status = 'pending' WHERE status = 'pendente'");
        DB::statement("UPDATE cases SET status = 'analysis' WHERE status = 'em_coleta'");
        DB::statement("UPDATE cases SET status = 'requirement' WHERE status = 'aguarda_peticao'");
        DB::statement("UPDATE cases SET status = 'completed' WHERE status = 'concluido'");
        DB::statement("UPDATE cases SET status = 'rejected' WHERE status = 'rejeitado'");

        // Depois, converter para o enum antigo
        Schema::table('cases', function (Blueprint $table) {
            $table->enum('status', [
                'pending', 
                'analysis', 
                'completed', 
                'requirement', 
                'rejected'
            ])->default('pending')->change();
        });
    }
};
