<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('workflow_template_id')->nullable()->after('case_id');
            $table->integer('order')->default(0)->after('workflow_template_id');
            $table->boolean('is_workflow_task')->default(false)->after('order');
            
            $table->foreign('workflow_template_id')->references('id')->on('workflow_templates')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['workflow_template_id']);
            $table->dropColumn(['workflow_template_id', 'order', 'is_workflow_task']);
        });
    }
}; 