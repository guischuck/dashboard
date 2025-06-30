<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('employment_relationships', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('employment_relationships', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });
    }
}; 