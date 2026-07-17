<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Snapshot of the program offering associated with a claim at the time the
     * claim was created/submitted. Decoupled from the live program_offerings
     * table so historical claims remain immutable if the offering later changes.
     */
    public function up(): void
    {
        Schema::create('claim_program_offerings', function (Blueprint $table) {
            $table->id();

            $table->string('claim_guid', 32)->unique();
            $table->foreign('claim_guid')->references('guid')->on('claims')
                ->onDelete('cascade');

            // Reference to the source program offering record (no FK: snapshots
            // persist independently of changes to the live program_offerings table).
            $table->string('program_offering_guid', 32)->index();

            // Snapshot columns mirroring the program_offerings table.
            $table->string('institution_guid', 32)->index();
            $table->string('program_guid', 32)->index();

            $table->string('offering_name');
            $table->text('offering_description')->nullable();
            $table->date('study_start_date')->nullable();
            $table->date('study_end_date')->nullable();
            $table->string('location_name')->nullable();

            $table->boolean('active_status')->default(true);

            $table->string('created_by_guid', 32)->nullable();
            $table->string('updated_by_guid', 32)->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_program_offerings');
    }
};
