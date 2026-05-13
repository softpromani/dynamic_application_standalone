<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Softpro\Core\Models\Application;

class SyncAllApplicationPaymentStatuses extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Automatically sync all application statuses when this migration runs
        // This ensures the 800+ existing applications are fixed upon deployment
        Application::all()->each(function ($application) {
            $application->syncPaymentStatus();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No reversal needed for data synchronization
    }
}
