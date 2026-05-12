<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\FormTemplate;
use Softpro\Core\Models\FormField;
use Softpro\Core\Models\Program;
use Softpro\Core\Models\CustomEntity;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FormTemplateController extends Controller
{
    public function index()
    {
        $templates = FormTemplate::withCount(['fields', 'jobs'])
            ->latest()
            ->get();

        return Inertia::render('Templates/Index', [
            'templates' => $templates,
        ]);
    }

    public function create()
    {
        $profileTemplate = FormTemplate::where('is_profile', true)->with('fields')->latest()->first();

        $subjectEntity = CustomEntity::where('name', 'subject')->first();
        $subjects = $subjectEntity ? $subjectEntity->values : [];

        return Inertia::render('Templates/Create', [
            'virtualColumns' => $this->getVirtualColumns(),
            'customEntities' => CustomEntity::orderBy('display_name')->get(['id', 'display_name']),
            'profileFields'  => $profileTemplate ? $profileTemplate->fields : [],
            'subjects'       => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'fields'             => 'required|array|min:1',
            'fields.*.step'      => 'required|integer|min:1',
            'fields.*.label'     => 'required|string|max:255',
            'fields.*.field_type'=> 'required|string',
            'fields.*.system_alias'=> 'nullable|string|max:100',
            'fields.*.custom_entity_id'=> 'nullable|exists:custom_entities,id',
        ]);

        if ($request->is_profile) {
            FormTemplate::where('id', '!=', 0)->update(['is_profile' => false]);
        }

        $template = FormTemplate::create([
            'name'               => $request->name,
            'description'        => $request->description,
            'is_active'          => true,
            'is_profile'         => $request->is_profile ?? false,
        ]);

        foreach ($request->fields as $index => $field) {
            $template->fields()->create([
                'step'             => $field['step'],
                'sort_order'       => $index,
                'field_type'       => $field['field_type'],
                'label'            => $field['label'],
                'name'             => Str::slug($field['label'], '_'),
                'placeholder'      => $field['placeholder'] ?? null,
                'options'          => isset($field['options']) ? $field['options'] : null,
                'is_required'      => $field['is_required'] ?? false,
                'system_alias'     => $field['system_alias'] ?? null,
                'custom_entity_id' => $field['custom_entity_id'] ?? null,
            ]);
        }

        return redirect()->route('templates.index')
            ->with('success', 'Form template created successfully.');
    }

    public function show(FormTemplate $template)
    {
        $template->load(['fields' => fn($q) => $q->orderBy('step')->orderBy('sort_order'), 'jobs']);
        $steps = $template->fields->groupBy('step');

        $subjectEntity = CustomEntity::where('name', 'subject')->first();
        $subjects = $subjectEntity ? $subjectEntity->values : [];

        return Inertia::render('Templates/Show', [
            'template' => $template,
            'steps'    => $steps,
            'subjects' => $subjects,
        ]);
    }

    public function edit(FormTemplate $template)
    {
        $template->load(['fields' => fn($q) => $q->orderBy('step')->orderBy('sort_order')]);
        $profileTemplate = FormTemplate::where('is_profile', true)->with('fields')->latest()->first();
        
        $subjectEntity = CustomEntity::where('name', 'subject')->first();
        $subjects = $subjectEntity ? $subjectEntity->values : [];
        
        return Inertia::render('Templates/Edit', [
            'template' => $template,
            'virtualColumns' => $this->getVirtualColumns(),
            'customEntities' => CustomEntity::orderBy('display_name')->get(['id', 'display_name']),
            'profileFields'  => $profileTemplate ? $profileTemplate->fields : [],
            'subjects'       => $subjects,
        ]);
    }

    public function update(Request $request, FormTemplate $template)
    {
        $request->validate([
            'name'               => 'required|string|max:255',
            'description'        => 'nullable|string',
            'fields'             => 'required|array|min:1',
            'fields.*.step'      => 'required|integer|min:1',
            'fields.*.label'     => 'required|string|max:255',
            'fields.*.field_type'=> 'required|string',
            'fields.*.system_alias'=> 'nullable|string|max:100',
            'fields.*.custom_entity_id'=> 'nullable|exists:custom_entities,id',
        ]);

        if ($request->is_profile) {
            FormTemplate::where('id', '!=', $template->id)->update(['is_profile' => false]);
        }

        $template->update([
            'name'               => $request->name,
            'description'        => $request->description,
            'is_profile'         => $request->is_profile ?? false,
        ]);

        $sentFieldIds = collect($request->fields)->pluck('id')->filter()->toArray();
        $template->fields()->whereNotIn('id', $sentFieldIds)->delete();

        foreach ($request->fields as $index => $fieldData) {
            $attributes = [
                'step'             => $fieldData['step'],
                'sort_order'       => $index,
                'field_type'       => $fieldData['field_type'],
                'label'            => $fieldData['label'],
                'name'             => Str::slug($fieldData['label'], '_'),
                'placeholder'      => $fieldData['placeholder'] ?? null,
                'options'          => isset($fieldData['options']) ? $fieldData['options'] : null,
                'is_required'      => $fieldData['is_required'] ?? false,
                'system_alias'     => $fieldData['system_alias'] ?? null,
                'custom_entity_id' => $fieldData['custom_entity_id'] ?? null,
            ];

            if (isset($fieldData['id']) && $fieldData['id']) {
                $template->fields()->where('id', $fieldData['id'])->update($attributes);
            } else {
                $template->fields()->create($attributes);
            }
        }

        return redirect()->route('templates.index')
            ->with('success', 'Template updated successfully.');
    }

    public function destroy(FormTemplate $template)
    {
        $template->delete();
        return redirect()->back()->with('success', 'Template deleted.');
    }

    public function toggleStatus(FormTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);
        return redirect()->back()->with('success', 'Template status updated.');
    }

    public function preview(FormTemplate $template)
    {
        $template->load(['fields' => fn($q) => $q->orderBy('step')->orderBy('sort_order')]);
        
        $subjectEntity = CustomEntity::where('name', 'subject')->first();
        $subjects = $subjectEntity ? $subjectEntity->values : [];

        return Inertia::render('Templates/PrintPreview', [
            'template' => $template,
            'subjects' => $subjects,
        ]);
    }

    private function getVirtualColumns()
    {
        try {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                $columns = DB::select("SHOW FULL COLUMNS FROM applications");
                return array_map(fn($col) => (object)['Field' => $col->Field], array_values(array_filter($columns, function($col) {
                    return str_contains($col->Extra, 'VIRTUAL') || str_contains($col->Extra, 'STORED') || str_contains($col->Extra, 'GENERATED');
                })));
            } else {
                $columns = DB::select("PRAGMA table_xinfo(applications)");
                return array_map(fn($col) => (object)['Field' => $col->name], array_values(array_filter($columns, function($col) {
                    return $col->hidden > 0;
                })));
            }
        } catch (\Exception $e) {
            return [];
        }
    }
}
