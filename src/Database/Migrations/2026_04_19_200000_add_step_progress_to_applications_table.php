<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            // Tracks how far the applicant has progressed through the form steps
            $table->unsignedTinyInteger('current_step')->default(1)->after('status');
            // draft = filling form, pending = submitted awaiting payment
            $table->string('form_status')->default('draft')->after('current_step');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['current_step', 'form_status']);
        });
    }
};
