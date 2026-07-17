<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The seat and dollar limits that used to live on allocations now belong to
     * each program offering.
     */
    public function up(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->float('total_amount', 8, 2)->default(0)->index()->after('location_name');
            $table->integer('total_seats')->default(0)->index()->after('total_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_offerings', function (Blueprint $table) {
            $table->dropColumn(['total_amount', 'total_seats']);
        });
    }
};
