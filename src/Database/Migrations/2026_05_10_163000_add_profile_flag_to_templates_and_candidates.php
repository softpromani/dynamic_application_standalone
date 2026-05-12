<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->boolean('is_profile')->default(false)->after('is_active');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->json('profile_data')->nullable()->after('signature_path');
            $table->boolean('is_profile_complete')->default(false)->after('profile_data');
        });
    }

    public function down(): void
    {
        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('is_profile');
        });

        Schema::table('applicants', function (Blueprint $table) {
            $table->dropColumn(['profile_data', 'is_profile_complete']);
        });
    }
};
