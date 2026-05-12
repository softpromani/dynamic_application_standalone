<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\Application;
use Softpro\Core\Models\Program;
use Softpro\Core\Models\Opening;
use Softpro\Core\Models\CustomEntity;
use Softpro\Core\Models\FormField;
use Softpro\Core\Models\ProgramApplicationType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;

class ApplicantApplicationController extends Controller
{
    protected function getStoragePath($path)
    {
        if (function_exists('tenant')) {
            return tenant('id') . '/' . $path;
        }
        return $path;
    }

    public function index()
    {
        $applicant = Auth::guard('applicant')->user();
        
        $programs = Program::with(['openings.subject', 'applicationTypes', 'customEntity'])
            ->where('is_active', true)
            ->where('application_end_date', '>=', now())
            ->get();

        $applicationsList = Application::where('applicant_id', $applicant->id)
            ->pluck('form_status', 'opening_id')
            ->toArray();
            
        $myApplications = empty($applicationsList) ? (object)[] : $applicationsList;

        return Inertia::render('Applicant/BrowsePrograms', [
            'programs' => $programs,
            'myApplications' => $myApplications,
            'profileComplete' => $this->isProfileComplete($applicant),
        ]);
    }

    public function showForm($opening_id)
    {
        $applicant = Auth::guard('applicant')->user();

        if (!$this->isProfileComplete($applicant)) {
            return redirect()->route('applicant.profile-setup')->with('error', 'Please complete your profile before applying.');
        }

        $opening = Opening::with(['job.formTemplate' => function($q) {
            $q->where('is_active', true);
        }, 'job.applicationTypes', 'subject'])->findOrFail($opening_id);

        $application = Application::where('applicant_id', $applicant->id)
            ->where('opening_id', $opening_id)
            ->first();

        if ($application && $application->form_status !== 'draft') {
            return redirect()->route('applicant.dashboard')->with('error', 'You have already applied for this opening.');
        }

        $template = $opening->job->formTemplate;
        if ($template) {
            $template->load(['fields' => function($q) {
                $q->orderBy('step')->orderBy('sort_order');
            }]);
            
            $customEntityIds = $template->fields->pluck('custom_entity_id')->filter()->unique();
            $customEntityData = CustomEntity::with('values')
                ->whereIn('id', $customEntityIds)
                ->get()
                ->keyBy('id');
        }

        return Inertia::render('Applicant/Apply/Form', [
            'opening' => $opening,
            'template' => $template,
            'applicationTypes' => $opening->job->applicationTypes,
            'existingDraft' => $application,
            'existingResponses' => $application ? $application->responses : (object)[],
            'customEntityData' => $customEntityData ?? (object)[]
        ]);
    }

    public function preview($opening_id)
    {
        $applicant = Auth::guard('applicant')->user();
        
        $application = Application::where('applicant_id', $applicant->id)
            ->where('opening_id', $opening_id)
            ->firstOrFail();

        if ($application->form_status !== 'draft') {
            return redirect()->route('applicant.dashboard')->with('error', 'Application already submitted.');
        }

        $application->load(['opening.job.formTemplate.fields' => function($q) {
            $q->orderBy('step')->orderBy('sort_order');
        }, 'opening.subject', 'applicationType']);

        $template = $application->opening->job->formTemplate;
        
        if ($template) {
            $customEntityIds = $template->fields->pluck('custom_entity_id')->filter()->unique();
            $customEntityData = CustomEntity::with('values')
                ->whereIn('id', $customEntityIds)
                ->get()
                ->keyBy('id');
        }
        $responses = $application->responses ?? [];

        return Inertia::render('Applicant/Apply/Preview', [
            'template' => $template,
            'application' => $application,
            'applicant' => $applicant,
            'responses' => $responses,
            'customEntityData' => $customEntityData ?? (object)[]
        ]);
    }

    public function saveStep(Request $request, $opening_id)
    {
        $applicant = Auth::guard('applicant')->user();
        $opening = Opening::with('job')->findOrFail($opening_id);

        $application = Application::where('applicant_id', $applicant->id)
            ->where('opening_id', $opening_id)
            ->first();

        if (!$application) {
            $typeId = $request->input('program_application_type_id');
            $type = ProgramApplicationType::where('program_id', $opening->program_id)->find($typeId);
            
            $fee = $type ? $type->fee : 0;
            $fine = ($type && now() > $opening->job->application_end_date) ? ($type->fine_amount ?? 0) : 0;

            $application = Application::create([
                'application_no' => $this->generateApplicationNo($opening),
                'applicant_id' => $applicant->id,
                'opening_id' => $opening_id,
                'program_application_type_id' => $typeId,
                'status' => 'pending',
                'form_status' => 'draft',
                'fee_amount' => $fee,
                'tax_amount' => ($fee * ($opening->job->tax_percentage ?? 0)) / 100,
                'fine_amount' => $fine,
                'total_amount' => $fee + (($fee * ($opening->job->tax_percentage ?? 0)) / 100) + $fine,
            ]);
        }

        if ($request->has('program_application_type_id')) {
            $typeId = $request->input('program_application_type_id');
            $type = ProgramApplicationType::where('program_id', $opening->program_id)->find($typeId);
            if ($type) {
                $fee = $type->fee;
                $fine = (now() > $opening->job->application_end_date) ? ($type->fine_amount ?? 0) : 0;
                $application->update([
                    'program_application_type_id' => $typeId,
                    'fee_amount' => $fee,
                    'tax_amount' => ($fee * ($opening->job->tax_percentage ?? 0)) / 100,
                    'fine_amount' => $fine,
                    'total_amount' => $fee + (($fee * ($opening->job->tax_percentage ?? 0)) / 100) + $fine,
                ]);
            }
        }

        $application->update(['current_step' => (int)$request->current_step]);

        $inputResponses = (array)$request->input('responses', []);
        $fileResponses = (array)$request->file('responses', []);
        $allFieldIds = array_unique(array_merge(array_keys($inputResponses), array_keys($fileResponses)));
        
        foreach ($request->allFiles() as $fileGroup) {
            $files = is_array($fileGroup) ? Arr::flatten($fileGroup) : [$fileGroup];
            foreach ($files as $file) {
                if ($file->getSize() > (2 * 1024 * 1024)) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'One or more files exceed the 2MB size limit.'
                    ], 422);
                }
            }
        }

        $applicationResponses = $application->responses ?? [];

        foreach ($allFieldIds as $field_id) {
            $field = FormField::find($field_id);
            if (!$field) continue;

            $value = $inputResponses[$field_id] ?? null;

            if ($field->field_type === 'table') {
                $config = is_string($field->options) ? json_decode($field->options, true) : $field->options;
                if (!is_array($value)) $value = [];
                
                $rowIndices = array_keys($value);
                if (isset($fileResponses[$field_id]) && is_array($fileResponses[$field_id])) {
                    $rowIndices = array_unique(array_merge($rowIndices, array_keys($fileResponses[$field_id])));
                }

                foreach ($rowIndices as $rowIdx) {
                    if (!isset($value[$rowIdx])) $value[$rowIdx] = [];
                    foreach ($config['columns'] ?? [] as $col) {
                        $colLabel = $col['label'];
                        if ($request->hasFile("responses.$field_id.$rowIdx.$colLabel")) {
                            $file = $request->file("responses.$field_id.$rowIdx.$colLabel");
                            $fileName = $application->applicant_id . '_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                            $value[$rowIdx][$colLabel] = $file->storeAs($this->getStoragePath('applicant/files'), $fileName, 'public');
                        }
                    }
                }
            } elseif ($field->field_type === 'file' || str_starts_with($field->field_type, 'system_')) {
                if ($request->hasFile("responses.$field_id")) {
                    $file = $request->file("responses.$field_id");
                    $fileName = $application->applicant_id . '_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                    $value = $file->storeAs($this->getStoragePath('applicant/files'), $fileName, 'public');
                }
            }

            $key = $field->system_alias ?: $field->id;
            $applicationResponses[$key] = $value;
        }

        $application->update(['responses' => $applicationResponses]);

        return response()->json(['success' => true]);
    }

    public function uploadTemp(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:2048'
        ]);

        if ($request->hasFile('file')) {
            $applicant = Auth::guard('applicant')->user();
            $file = $request->file('file');
            $fileName = $applicant->id . '_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($this->getStoragePath('applicant/files'), $fileName, 'public');
            return response()->json(['success' => true, 'path' => $path]);
        }

        return response()->json(['success' => false, 'message' => 'No file received.'], 400);
    }

    public function submitForm(Request $request, $opening_id)
    {
        $applicant = Auth::guard('applicant')->user();

        if (!$this->isProfileComplete($applicant)) {
            return redirect()->route('applicant.profile-setup');
        }

        $opening = Opening::with(['job.formTemplate' => function($q) {
            $q->where('is_active', true);
        }])->findOrFail($opening_id);
        
        $application = Application::where('applicant_id', $applicant->id)
            ->where('opening_id', $opening_id)
            ->first();

        if ($application && $application->form_status !== 'draft') {
            return redirect()->back()->with('error', 'You have already applied for this opening.');
        }

        $template = $opening->job->formTemplate;
        
        $typeId = $application ? $application->program_application_type_id : $request->input('program_application_type_id');
        $type = ProgramApplicationType::where('program_id', $opening->program_id)->find($typeId);
        
        $fee = $type ? $type->fee : 0;
        $tax = ($fee * ($opening->job->tax_percentage ?? 0)) / 100;
        $fine = ($type && now() > $opening->job->application_end_date) ? ($type->fine_amount ?? 0) : 0;
        $total = $fee + $tax + $fine;

        if (!$application) {
            $application = Application::create([
                'application_no' => $this->generateApplicationNo($opening),
                'applicant_id' => $applicant->id,
                'opening_id' => $opening_id,
                'program_application_type_id' => $typeId,
                'status' => 'pending', 
                'form_status' => 'draft',
                'fee_amount' => $fee,
                'tax_amount' => $tax,
                'fine_amount' => $fine,
                'total_amount' => $total,
            ]);
        }

        if ($template) {
            $applicationResponses = $application->responses ?? [];

            foreach ($template->fields as $field) {
                if ($request->has("responses.{$field->id}") || $request->hasFile("responses.{$field->id}")) {
                    $value = $request->input("responses.{$field->id}");
                    
                    if ($field->is_subject_field) {
                        $value = $opening->subject_id;
                    }

                    if ($field->field_type === 'table') {
                        $config = is_string($field->options) ? json_decode($field->options, true) : $field->options;
                        if (!is_array($value)) $value = [];

                        $rowIndices = array_keys($value);
                        $fileData = $request->file("responses.{$field->id}");
                        if (is_array($fileData)) {
                            $rowIndices = array_unique(array_merge($rowIndices, array_keys($fileData)));
                        }

                        foreach ($rowIndices as $rowIdx) {
                            if (!isset($value[$rowIdx])) $value[$rowIdx] = [];
                            foreach ($config['columns'] ?? [] as $col) {
                                $colLabel = $col['label'];
                                if ($request->hasFile("responses.{$field->id}.$rowIdx.$colLabel")) {
                                    $file = $request->file("responses.{$field->id}.$rowIdx.$colLabel");
                                    $fileName = $applicant->id . '_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                                    $value[$rowIdx][$colLabel] = $file->storeAs($this->getStoragePath('applicant/files'), $fileName, 'public');
                                }
                            }
                        }
                    } elseif ($field->field_type === 'file' || str_starts_with($field->field_type, 'system_')) {
                        if ($request->hasFile("responses.{$field->id}")) {
                            $file = $request->file("responses.{$field->id}");
                            $fileName = $applicant->id . '_' . time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
                            $value = $file->storeAs($this->getStoragePath('applicant/files'), $fileName, 'public');
                        }
                    }
                    $key = $field->system_alias ?: $field->id;
                    $applicationResponses[$key] = $value;
                }
            }

            $application->update(['responses' => $applicationResponses]);
        }

        $isFreeJob = !($opening->job->is_payable ?? true) || $total <= 0;
        $isAlreadyPaid = ($application->status === 'paid') || $isFreeJob;
        
        $application->update([
            'status' => $isAlreadyPaid ? 'paid' : 'pending',
            'form_status' => 'submitted',
            'submitted_at' => now(),
            'total_amount' => $isFreeJob ? 0 : $total,
        ]);

        if ($isAlreadyPaid) {
            return Inertia::location(route('applicant.dashboard'));
        }

        return Inertia::location(route('applicant.payment.initiate', $application->id));
    }

    private function generateApplicationNo($opening)
    {
        $program = $opening->job;
        $count = Application::whereHas('opening', function($q) use ($program) {
            $q->where('program_id', $program->id);
        })->count();
        
        $nextNumber = $count + 1;
        return $program->job_code . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    private function isProfileComplete($applicant)
    {
        return (bool)($applicant->is_profile_complete ?? false);
    }

    public function print(Application $application)
    {
        $applicant = Auth::guard('applicant')->user();
        
        if ($application->applicant_id !== $applicant->id) {
            abort(403);
        }

        $application->load(['opening.job.formTemplate.fields' => function($q) {
            $q->orderBy('step')->orderBy('sort_order');
        }, 'opening.subject']);

        $template = $application->opening->job->formTemplate;
        
        if ($template) {
            $customEntityIds = $template->fields->pluck('custom_entity_id')->filter()->unique();
            $customEntityData = CustomEntity::with('values')
                ->whereIn('id', $customEntityIds)
                ->get()
                ->keyBy('id');
        }
        $responses = $application->responses ?? [];

        return Inertia::render('Templates/PrintPreview', [
            'template' => $template,
            'application' => $application,
            'applicant' => $applicant,
            'responses' => $responses,
            'customEntityData' => $customEntityData ?? (object)[]
        ]);
    }
}
