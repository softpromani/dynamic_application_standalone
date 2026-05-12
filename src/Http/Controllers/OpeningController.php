<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\Opening;
use Softpro\Core\Models\Program;
use Softpro\Core\Models\CustomEntity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OpeningController extends Controller
{
    public function index()
    {
        $subjectEntity = CustomEntity::where('name', 'subject')->first();
        $subjects = $subjectEntity ? $subjectEntity->values : [];

        return Inertia::render('Openings/Index', [
            'openings' => Opening::with(['program', 'subject'])->get(),
            'programs' => Program::all(),
            'subjects' => $subjects,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'program_id' => 'required|exists:programs,id',
            'subject_id' => 'required|exists:custom_entity_values,id',
            'seats' => 'required|integer|min:1',
        ]);

        $exists = Opening::where('program_id', $request->program_id)
                         ->where('subject_id', $request->subject_id)
                         ->first();

        if ($exists) {
            return redirect()->back()->withErrors(['subject_id' => 'Opening for this subject already exists in this job post.']);
        }

        Opening::create($request->all());

        return redirect()->back()->with('success', 'Opening created successfully.');
    }

    public function update(Request $request, Opening $opening)
    {
        $request->validate([
            'seats' => 'required|integer|min:1',
        ]);

        $opening->update($request->only('seats'));

        return redirect()->back()->with('success', 'Opening updated successfully.');
    }

    public function destroy(Opening $opening)
    {
        $opening->delete();
        return redirect()->back()->with('success', 'Opening deleted successfully.');
    }
}
