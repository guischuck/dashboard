<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inss_processes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained()->onDelete('cascade');
            $table->string('process_number')->unique();
            $table->string('protocol_number')->nullable();
            $table->enum('status', ['analysis', 'completed', 'requirement', 'rejected', 'appeal'])->default('analysis');
            $table->text('last_movement')->nullable();
            $table->date('last_movement_date')->nullable();
            $table->boolean('is_seen')->default(false);
            $table->boolean('has_changes')->default(false);
            $table->json('movements_history')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inss_processes');
    }
}; 