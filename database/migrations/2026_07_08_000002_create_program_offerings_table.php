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
        Schema::create('program_offerings', function (Blueprint $table) {
            $table->id();
            $table->string('guid', 32)->index()->unique();

            $table->string('institution_guid', 32);
            $table->foreign('institution_guid')->references('guid')->on('institutions')
                ->onDelete('cascade');

            $table->string('program_guid', 32);
            $table->foreign('program_guid')->references('guid')->on('programs')
                ->onDelete('cascade');

            $table->string('offering_name');
            $table->text('offering_description')->nullable();
            $table->date('study_start_date')->nullable();
            $table->date('study_end_date')->nullable();
            $table->string('location_name')->nullable();

            $table->boolean('active_status')->default(true)
                ->comment('offering status set by inst.: true / false');

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
        Schema::dropIfExists('program_offerings');
    }
};
