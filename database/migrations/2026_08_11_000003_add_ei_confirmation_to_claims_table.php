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
            // EI confirmation recorded by Institution/Ministry. Once confirmed the
            // date and confirming user are stamped and the field locks for institutions.
            $table->boolean('ei_confirmation')->nullable();
            $table->timestamp('ei_confirmation_date')->nullable();
            $table->string('ei_confirmation_user_guid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'ei_confirmation',
                'ei_confirmation_date',
                'ei_confirmation_user_guid',
            ]);
        });
    }
};
