<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Snapshot of the institution associated with a claim at the time the
     * claim was created/submitted. The snapshot is intentionally decoupled
     * from the live institutions table so historical claims remain immutable
     * even if the source institution record later changes.
     */
    public function up(): void
    {
        Schema::create('claim_institutions', function (Blueprint $table) {
            $table->id();

            $table->string('claim_guid', 32)->unique();
            $table->foreign('claim_guid')->references('guid')->on('claims')
                ->onDelete('cascade');

            // Reference to the source institution record (no FK: snapshots persist
            // independently of changes to the live institutions table).
            $table->string('institution_guid', 32)->index();

            // Snapshot columns mirroring the institutions table.
            $table->string('bceid_business_guid')->nullable();
            $table->string('name');
            $table->boolean('active_status')->default(false);

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_institutions');
    }
};
