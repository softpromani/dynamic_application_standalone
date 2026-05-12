<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Rename status='submitted' to 'pending' for all existing applications.
     * 
     * - 'form_status' tracks form lifecycle: draft | submitted
     * - 'status' tracks payment lifecycle: pending | paid | failed
     * 
     * Any application that had status='submitted' was waiting for payment,
     * so it should now be 'pending'.
     */
    public function up(): void
    {
        DB::table('applications')
            ->where('status', 'submitted')
            ->update(['status' => 'pending']);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        DB::table('applications')
            ->where('status', 'pending')
            ->where('form_status', 'submitted')
            ->update(['status' => 'submitted']);
    }
};
