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
        if (Schema::hasTable('recruitment_jobs') && !Schema::hasTable('programs')) {
            Schema::rename('recruitment_jobs', 'programs');
        }
        if (Schema::hasTable('candidates') && !Schema::hasTable('applicants')) {
            Schema::rename('candidates', 'applicants');
        }
        if (Schema::hasTable('vacancies') && !Schema::hasTable('openings')) {
            Schema::rename('vacancies', 'openings');
        }
        if (Schema::hasTable('job_application_types') && !Schema::hasTable('program_application_types')) {
            Schema::rename('job_application_types', 'program_application_types');
        }

        // Rename Columns in openings
        if (Schema::hasTable('openings')) {
            Schema::table('openings', function (Blueprint $table) {
                if (Schema::hasColumn('openings', 'recruitment_job_id')) {
                    $table->renameColumn('recruitment_job_id', 'program_id');
                }
            });
        }

        // Rename Columns in applications
        if (Schema::hasTable('applications')) {
            Schema::table('applications', function (Blueprint $table) {
                if (Schema::hasColumn('applications', 'candidate_id')) {
                    $table->renameColumn('candidate_id', 'applicant_id');
                }
                if (Schema::hasColumn('applications', 'vacancy_id')) {
                    $table->renameColumn('vacancy_id', 'opening_id');
                }
                if (Schema::hasColumn('applications', 'job_application_type_id')) {
                    $table->renameColumn('job_application_type_id', 'program_application_type_id');
                }
            });
        }

        // Rename Columns in program_application_types
        if (Schema::hasTable('program_application_types')) {
            Schema::table('program_application_types', function (Blueprint $table) {
                if (Schema::hasColumn('program_application_types', 'recruitment_job_id')) {
                    $table->renameColumn('recruitment_job_id', 'program_id');
                }
            });
        }
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
