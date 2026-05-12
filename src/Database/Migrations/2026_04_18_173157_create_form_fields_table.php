<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('form_template_id')->constrained('form_templates')->onDelete('cascade');
            $table->integer('step')->default(1);            // which step/page of the form
            $table->integer('sort_order')->default(0);      // order within the step
            $table->string('field_type');                   // text, textarea, number, date, select, subject, file, checkbox, radio
            $table->string('label');                        // "Full Name", "Date of Birth", etc.
            $table->string('name');                         // snake_case field key
            $table->string('placeholder')->nullable();
            $table->text('options')->nullable();            // JSON array for select/radio options
            $table->boolean('is_required')->default(false);
            $table->boolean('is_subject_field')->default(false); // marks a subject-picker field
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
