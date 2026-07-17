<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The descriptive PDEX metadata below is no longer captured on programs.
     * active_status and last_touch_by_user_guid are intentionally retained.
     */
    private array $columns = [
        'program_type',
        'program_number',
        'delivery_method',
        'online_delivery_type',
        'credential_type',
        'micro_credential_type',
        'high_priority_industry',
        'total_duration_hrs',
        'creditable',
        'full_time',
        'prov_funded_micro_cred',
        'indigenous_related_learning',
        'diversity_inclusion_related_learning',
        'excel_guid',
        'start_date',
        'end_date',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            foreach ($this->columns as $column) {
                if (Schema::hasColumn('programs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('program_type')->nullable();
            $table->string('program_number')->nullable()->comment('Historical program number');
            $table->string('delivery_method')->nullable()->comment('In-person|Hybrid or Blended|Online');
            $table->string('online_delivery_type')->nullable()->comment('Synchronous|Asynchronous');
            $table->string('credential_type')->nullable()->comment('Micro-Credential|Short-Certificate|Other');
            $table->string('micro_credential_type')->nullable()->comment('No Applicable|Provincially Approved|Not Provincially Approved');
            $table->string('high_priority_industry')->nullable();
            $table->float('total_duration_hrs')->default(0)->nullable();
            $table->boolean('creditable')->default(true);
            $table->boolean('full_time')->default(true);
            $table->boolean('prov_funded_micro_cred')->default(false)->comment('ProvinciallyFundedMicroCredentialFlg');
            $table->boolean('indigenous_related_learning')->default(false)->comment('Indigenous Related Learning Flg');
            $table->boolean('diversity_inclusion_related_learning')->default(false)->comment('Diversity Inclusion Related Flg');
            $table->string('excel_guid')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
        });
    }
};
