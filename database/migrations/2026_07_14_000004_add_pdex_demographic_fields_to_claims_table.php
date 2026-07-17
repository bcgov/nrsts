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
        Schema::table('claims', function (Blueprint $table) {
            // Additional demographic fields sourced from the PDEX individual token.
            $table->string('indigenous_group')->nullable()->after('indigenous_identity');
            $table->string('racial_identity')->nullable()->after('visible_minority_status');
            $table->boolean('is_visible_minority')->nullable()->after('racial_identity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn(['indigenous_group', 'racial_identity', 'is_visible_minority']);
        });
    }
};
