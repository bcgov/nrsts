<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The allocation concept has been fully replaced by program offerings.
     */
    public function up(): void
    {
        Schema::dropIfExists('allocations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('allocations', function (Blueprint $table) {
            $table->id();
            $table->string('guid', 32)->index()->unique();

            $table->string('institution_guid', 32)->index();
            $table->foreign('institution_guid')->references('guid')->on('institutions')
                ->onDelete('cascade');
            $table->string('program_year_guid', 32)->index();
            $table->foreign('program_year_guid')->references('guid')->on('program_years')
                ->onDelete('cascade');

            $table->float('total_amount', 8, 2)->default(0)->index();
            $table->integer('total_seats')->default(0)->index();
            $table->string('status')->default('active')->index()->comment('active|completed|cancelled');

            $table->softDeletes();
            $table->timestamps();
        });
    }
};
