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
        // Rename Tables
        Schema::rename('recruitment_jobs', 'programs');
        Schema::rename('candidates', 'applicants');
        Schema::rename('vacancies', 'openings');
        Schema::rename('job_application_types', 'program_application_types');

        // Rename Columns in openings
        Schema::table('openings', function (Blueprint $table) {
            $table->renameColumn('recruitment_job_id', 'program_id');
        });

        // Rename Columns in applications
        Schema::table('applications', function (Blueprint $table) {
            $table->renameColumn('candidate_id', 'applicant_id');
            $table->renameColumn('vacancy_id', 'opening_id');
            $table->renameColumn('job_application_type_id', 'program_application_type_id');
        });

        // Rename Columns in program_application_types
        Schema::table('program_application_types', function (Blueprint $table) {
            $table->renameColumn('recruitment_job_id', 'program_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert Columns in program_application_types
        Schema::table('program_application_types', function (Blueprint $table) {
            $table->renameColumn('program_id', 'recruitment_job_id');
        });

        // Revert Columns in applications
        Schema::table('applications', function (Blueprint $table) {
            $table->renameColumn('applicant_id', 'candidate_id');
            $table->renameColumn('opening_id', 'vacancy_id');
            $table->renameColumn('program_application_type_id', 'job_application_type_id');
        });

        // Revert Columns in openings
        Schema::table('openings', function (Blueprint $table) {
            $table->renameColumn('program_id', 'recruitment_job_id');
        });

        // Revert Tables
        Schema::rename('program_application_types', 'job_application_types');
        Schema::rename('openings', 'vacancies');
        Schema::rename('applicants', 'candidates');
        Schema::rename('programs', 'recruitment_jobs');
    }
};
