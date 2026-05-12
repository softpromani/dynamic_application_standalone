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
        Schema::table('applicants', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('gender')->nullable()->after('dob');
            $table->string('category')->nullable()->after('gender');
            $table->string('father_name')->nullable()->after('category');
            $table->string('mother_name')->nullable()->after('father_name');
            $table->string('marital_status')->nullable()->after('mother_name');
            $table->text('permanent_address')->nullable()->after('marital_status');
            $table->text('correspondence_address')->nullable()->after('permanent_address');
            $table->string('id_proof_type')->nullable();
            $table->string('id_proof_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            //
        });
    }
};
