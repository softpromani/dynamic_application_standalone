<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\Program;
use Softpro\Core\Models\CustomEntity;
use Softpro\Core\Models\Opening;
use Softpro\Core\Models\FormTemplate;
use Softpro\Core\Models\ProgramApplicationType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProgramController extends Controller
{
    public function index()
    {
        return Inertia::render('Programs/Index', [
            'programs' => Program::with(['applicationTypes'])->withCount('openings')->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Programs/Create', [
            'customEntities' => CustomEntity::with('values')->orderBy('display_name')->get(),
            'templates' => FormTemplate::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'job_code'               => 'required|string|unique:programs',
            'title'                  => 'required|string|max:255',
            'application_start_date' => 'required|date',
            'application_end_date'   => 'required|date|after_or_equal:application_start_date',
            'last_payment_date'      => 'required|date|after_or_equal:application_end_date',
            'application_fee'        => 'nullable|numeric|min:0',
            'fine_amount'            => 'nullable|numeric|min:0',
            'tax_percentage'         => 'nullable|numeric|min:0|max:100',
            'form_template_id'       => 'nullable|exists:form_templates,id',
            'openings'              => 'nullable|array',
            'application_types'      => 'required_if:is_payable,true|array',
            'application_types.*.name'=> 'nullable|required_if:is_payable,true|string|max:255',
            'application_types.*.fee' => 'nullable|required_if:is_payable,true|numeric|min:0',
            'application_types.*.fine_amount' => 'nullable|numeric|min:0',
            'footer_notes'           => 'nullable|string',
            'is_payable'             => 'required|boolean',
            'custom_entity_id'       => 'nullable|exists:custom_entities,id',
            'preview_config'         => 'nullable|array',
        ]);

        $program = Program::create($request->except(['openings', 'application_types']));

        $openings = $request->input('openings', []);
        if (empty($openings)) {
            $program->openings()->create([
                'subject_id' => null,
                'seats'      => 0,
            ]);
        } else {
            foreach ($openings as $v) {
                $program->openings()->create([
                    'subject_id' => $v['subject_id'] ?? null,
                    'seats'      => $v['seats'] ?? 0,
                ]);
            }
        }

        foreach ($request->input('application_types', []) as $type) {
            $program->applicationTypes()->create($type);
        }

        return redirect()->route('programs.index')->with('success', 'Program created successfully.');
    }

    public function edit(Program $program)
    {
        $program->load(['openings.subject', 'applicationTypes']);
        
        return Inertia::render('Programs/Edit', [
            'program'   => $program,
            'customEntities' => CustomEntity::with('values')->orderBy('display_name')->get(),
            'templates' => FormTemplate::where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Program $program)
    {
        $request->validate([
            'job_code'               => 'required|string|unique:programs,job_code,' . $program->id,
            'title'                  => 'required|string|max:255',
            'application_start_date' => 'required|date',
            'application_end_date'   => 'required|date|after_or_equal:application_start_date',
            'last_payment_date'      => 'required|date|after_or_equal:application_end_date',
            'application_fee'        => 'nullable|numeric|min:0',
            'fine_amount'            => 'nullable|numeric|min:0',
            'tax_percentage'         => 'nullable|numeric|min:0|max:100',
            'form_template_id'       => 'nullable|exists:form_templates,id',
            'openings'              => 'nullable|array',
            'openings.*.id'         => 'nullable|exists:openings,id',
            'application_types'      => 'required_if:is_payable,true|array',
            'application_types.*.name'=> 'nullable|required_if:is_payable,true|string|max:255',
            'application_types.*.fee' => 'nullable|required_if:is_payable,true|numeric|min:0',
            'application_types.*.fine_amount' => 'nullable|numeric|min:0',
            'footer_notes'           => 'nullable|string',
            'is_payable'             => 'required|boolean',
            'custom_entity_id'       => 'nullable|exists:custom_entities,id',
            'preview_config'         => 'nullable|array',
        ]);

        $program->update($request->except(['openings', 'application_types']));

        $incoming = collect($request->input('openings', []));

        if ($incoming->isEmpty()) {
            $program->openings()->delete();
            $program->openings()->create([
                'subject_id' => null,
                'seats'      => 0,
            ]);
        } else {
            $keptIds = $incoming->pluck('id')->filter()->values();
            $program->openings()->whereNotIn('id', $keptIds)->delete();

            foreach ($incoming as $v) {
                if (!empty($v['id'])) {
                    Opening::where('id', $v['id'])->update([
                        'seats' => $v['seats'] ?? 0,
                        'subject_id' => $v['subject_id'] ?? null
                    ]);
                } else {
                    $program->openings()->create([
                        'subject_id' => $v['subject_id'] ?? null,
                        'seats'      => $v['seats'] ?? 0
                    ]);
                }
            }
        }

        $incomingTypes = collect($request->input('application_types', []));
        $keptTypeIds = $incomingTypes->pluck('id')->filter()->values();
        $program->applicationTypes()->whereNotIn('id', $keptTypeIds)->delete();

        foreach ($incomingTypes as $t) {
            if (!empty($t['id'])) {
                ProgramApplicationType::where('id', $t['id'])->update([
                    'name' => $t['name'],
                    'fee' => $t['fee'],
                    'fine_amount' => $t['fine_amount'] ?? 0
                ]);
            } else {
                $program->applicationTypes()->create($t);
            }
        }

        return redirect()->route('programs.index')->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program)
    {
        $program->delete();
        return redirect()->back()->with('success', 'Program deleted successfully.');
    }
}
