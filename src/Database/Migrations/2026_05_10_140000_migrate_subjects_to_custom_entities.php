<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create the Subject Custom Entity if it doesn't exist
        $entity = DB::table('custom_entities')->where('name', 'subject')->first();
        if (!$entity) {
            $entityId = DB::table('custom_entities')->insertGetId([
                'name' => 'subject',
                'display_name' => 'Subject',
                'description' => 'Migrated from dedicated subjects table',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $entityId = $entity->id;
        }

        // 2. Migrate data
        if (Schema::hasTable('subjects')) {
            $subjects = DB::table('subjects')->get();
            foreach ($subjects as $subject) {
                $newValueId = DB::table('custom_entity_values')->insertGetId([
                    'custom_entity_id' => $entityId,
                    'label' => $subject->name,
                    'value' => $subject->code ?? $subject->name,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // 3. Update openings
                if (Schema::hasTable('openings')) {
                    DB::table('openings')->where('subject_id', $subject->id)->update(['subject_id' => $newValueId]);
                }
            }

            // 3.5 Update FormFields that were "subject" pickers
            DB::table('form_fields')
                ->where('field_type', 'subject')
                ->orWhere('is_subject_field', 1)
                ->update([
                    'field_type' => 'select',
                    'custom_entity_id' => $entityId,
                    'is_subject_field' => 0
                ]);

            // 4. Drop the old table
            // We should first drop the foreign key if it exists
            Schema::table('openings', function (Blueprint $table) {
                // Determine the correct foreign key name if possible, or use the default
                $table->dropForeign(['subject_id']);
            });

            Schema::dropIfExists('subjects');

            // 5. Re-add foreign key pointing to custom_entity_values
            Schema::table('openings', function (Blueprint $table) {
                $table->foreign('subject_id')->references('id')->on('custom_entity_values')->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        // Not easily reversible without data loss
    }
};
