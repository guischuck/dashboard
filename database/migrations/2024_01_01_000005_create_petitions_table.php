<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('petitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('content');
            $table->string('type'); // recurso, requerimento, etc
            $table->enum('status', ['draft', 'generated', 'submitted', 'approved'])->default('draft');
            $table->string('file_path')->nullable();
            $table->json('ai_generation_data')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('petitions');
    }
}; 