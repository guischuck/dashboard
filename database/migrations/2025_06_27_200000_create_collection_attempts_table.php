<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('collection_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employment_relationship_id')->constrained()->onDelete('cascade');
            $table->unsignedTinyInteger('tentativa_num');
            $table->string('endereco')->nullable();
            $table->string('rastreamento')->nullable();
            $table->date('data_envio')->nullable();
            $table->string('retorno')->nullable();
            $table->string('email')->nullable();
            $table->string('telefone')->nullable();
            $table->timestamps();
            $table->unique(['employment_relationship_id', 'tentativa_num'], 'collection_attempts_unique');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('collection_attempts');
    }
}; 