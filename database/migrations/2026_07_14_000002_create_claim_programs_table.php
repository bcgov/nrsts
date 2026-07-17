<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Snapshot of the program associated with a claim at the time the claim
     * was created/submitted. Decoupled from the live programs table so
     * historical claims remain immutable if the source program later changes.
     */
    public function up(): void
    {
        Schema::create('claim_programs', function (Blueprint $table) {
            $table->id();

            $table->string('claim_guid', 32)->unique();
            $table->foreign('claim_guid')->references('guid')->on('claims')
                ->onDelete('cascade');

            // Reference to the source program record (no FK: snapshots persist
            // independently of changes to the live programs table).
            $table->string('program_guid', 32)->index();

            // Snapshot columns mirroring the programs table.
            $table->string('program_name');
            $table->string('program_type');
            $table->string('program_number')->nullable();
            $table->string('delivery_method')->nullable();
            $table->string('online_delivery_type')->nullable();
            $table->string('credential_type')->nullable();
            $table->string('micro_credential_type')->nullable();
            $table->string('high_priority_industry')->nullable();

            $table->float('total_duration_hrs')->default(0)->nullable();

            $table->boolean('creditable')->default(true);
            $table->boolean('full_time')->default(true);
            $table->boolean('prov_funded_micro_cred')->default(false);
            $table->boolean('indigenous_related_learning')->default(false);
            $table->boolean('diversity_inclusion_related_learning')->default(false);

            $table->boolean('active_status')->default(true);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_programs');
    }
};
