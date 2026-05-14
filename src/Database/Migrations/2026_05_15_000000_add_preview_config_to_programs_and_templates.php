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
        Schema::table('programs', function (Blueprint $table) {
            $table->json('preview_config')->nullable()->after('footer_notes');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->json('preview_config')->nullable()->after('is_profile');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('preview_config');
        });

        Schema::table('form_templates', function (Blueprint $table) {
            $table->dropColumn('preview_config');
        });
    }
};
