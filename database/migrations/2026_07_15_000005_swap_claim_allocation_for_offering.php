<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Claims are now tied to a specific program offering instead of an
     * institution-wide allocation.
     */
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['allocation_guid']);
            $table->dropColumn('allocation_guid');

            $table->string('program_offering_guid', 32)->nullable()->index()->after('institution_guid');
            $table->foreign('program_offering_guid')->references('guid')->on('program_offerings')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['program_offering_guid']);
            $table->dropColumn('program_offering_guid');

            $table->string('allocation_guid', 32)->nullable()->index()->after('institution_guid');
        });
    }
};
