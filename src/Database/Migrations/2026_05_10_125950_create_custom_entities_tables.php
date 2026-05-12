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
        Schema::create('custom_entities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // e.g. "department"
            $table->string('display_name');  // e.g. "Department"
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('custom_entity_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('custom_entity_id')->constrained()->onDelete('cascade');
            $table->string('value');
            $table->string('label');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->foreignId('custom_entity_id')->nullable()->constrained('custom_entities')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_entity_id');
        });
        Schema::dropIfExists('custom_entity_values');
        Schema::dropIfExists('custom_entities');
    }
};
