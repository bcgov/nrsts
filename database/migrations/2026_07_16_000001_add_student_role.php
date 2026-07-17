<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * The Student role was introduced with the claim/application feature but was
     * never seeded, so BCSC students logged in without any role and failed the
     * "create claim" policy (403). Add it idempotently and backfill existing
     * students that have no role.
     */
    public function up(): void
    {
        $exists = DB::table('roles')->where('name', Role::Student)->exists();

        if (! $exists) {
            DB::table('roles')->insert([
                'name' => Role::Student,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $studentRoleId = DB::table('roles')->where('name', Role::Student)->value('id');

        // Backfill: attach the Student role to BCSC users that currently have no role.
        $students = DB::table('users')
            ->whereNotNull('bcsc_user_guid')
            ->whereNotIn('id', function ($query) {
                $query->select('user_id')->from('role_user');
            })
            ->pluck('id');

        foreach ($students as $userId) {
            DB::table('role_user')->insert([
                'user_id' => $userId,
                'role_id' => $studentRoleId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $studentRoleId = DB::table('roles')->where('name', Role::Student)->value('id');

        if ($studentRoleId) {
            DB::table('role_user')->where('role_id', $studentRoleId)->delete();
            DB::table('roles')->where('id', $studentRoleId)->delete();
        }
    }
};
