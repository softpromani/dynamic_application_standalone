<?php

namespace Softpro\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Softpro\Core\Models\Application;
use Softpro\Core\Models\FormTemplate;
use Softpro\Core\Models\CustomEntity;

class ApplicantProfileController extends Controller
{
    protected function getStoragePath($path)
    {
        if (function_exists('tenant')) {
            return tenant('id') . '/' . $path;
        }
        return $path;
    }

    public function dashboard()
    {
        $applicant = Auth::guard('applicant')->user();
        $applications = Application::with(['opening.job', 'opening.subject', 'transactions'])
            ->where('applicant_id', $applicant->id)
            ->latest()
            ->get();

        $profileTemplate = FormTemplate::where('is_profile', true)->latest()->first();

        return Inertia::render('Applicant/Dashboard', [
            'applications' => $applications,
            'isProfileComplete' => (bool)$applicant->is_profile_complete,
            'hasProfileTemplate' => (bool)$profileTemplate,
        ]);
    }

    public function showProfileSetup()
    {
        $applicant = Auth::guard('applicant')->user();
        
        $profileTemplate = FormTemplate::where('is_active', true)
            ->where('is_profile', true)
            ->with(['fields' => fn($q) => $q->orderBy('step')->orderBy('sort_order')])
            ->latest()
            ->first();

        if (!$profileTemplate) {
            return redirect()->route('applicant.dashboard')
                ->with('error', 'Profile configuration is not set by admin.');
        }

        return Inertia::render('Applicant/Profile/Edit', [
            'template' => $profileTemplate,
            'existingData' => $applicant->profile_data ?? (object)[],
            'applicant' => $applicant,
            'customEntityData' => $this->getCustomEntityData($profileTemplate),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $applicant = Auth::guard('applicant')->user();
        
        $profileTemplate = FormTemplate::where('is_active', true)
            ->where('is_profile', true)
            ->with('fields')
            ->latest()
            ->firstOrFail();

        $rules = [];
        foreach ($profileTemplate->fields as $field) {
            $rule = [];
            if ($field->is_required) {
                $rule[] = 'required';
            } else {
                $rule[] = 'nullable';
            }

            if ($field->field_type === 'email') $rule[] = 'email';
            if ($field->field_type === 'number') $rule[] = 'numeric';
            
            $rules["responses.{$field->id}"] = $rule;
            
            if ($field->system_alias) {
                $rules["responses.{$field->system_alias}"] = $rule;
            }
        }

        $request->validate($rules);

        $coreUpdates = [];
        $responses = $request->responses;
        
        $aliasMap = [
            'system_name' => 'name',
            'system_phone' => 'phone',
            'system_dob' => 'dob',
            'system_gender' => 'gender',
            'system_category' => 'category',
            'system_father_name' => 'father_name',
            'system_mother_name' => 'mother_name',
            'system_marital_status' => 'marital_status',
            'system_address_perm' => 'permanent_address',
            'system_address_corr' => 'correspondence_address',
            'system_id_proof_type' => 'id_proof_type',
            'system_id_proof_number' => 'id_proof_number',
        ];

        foreach ($aliasMap as $alias => $column) {
            if (isset($responses[$alias])) {
                $coreUpdates[$column] = $responses[$alias];
            }
        }

        if (isset($responses['system_photo']) && str_starts_with($responses['system_photo'], 'data:image')) {
            $path = $this->saveBase64Image($responses['system_photo'], $this->getStoragePath('applicant/photos'), $applicant->id);
            $coreUpdates['profile_photo_path'] = $path;
            $responses['system_photo'] = $path;
        }

        if (isset($responses['system_signature']) && str_starts_with($responses['system_signature'], 'data:image')) {
            $path = $this->saveBase64Image($responses['system_signature'], $this->getStoragePath('applicant/signatures'), $applicant->id);
            $coreUpdates['signature_path'] = $path;
            $responses['system_signature'] = $path;
        }

        $applicant->update(array_merge($coreUpdates, [
            'profile_data' => $responses,
            'is_profile_complete' => true,
        ]));

        return redirect()->route('applicant.dashboard')
            ->with('success', 'Profile updated successfully.');
    }

    private function getCustomEntityData($template)
    {
        $entityIds = $template->fields->pluck('custom_entity_id')->filter()->unique();
        if ($entityIds->isEmpty()) return (object)[];

        return CustomEntity::whereIn('id', $entityIds)
            ->with('values')
            ->get()
            ->keyBy('id');
    }

    private function saveBase64Image($base64Data, $directory, $prefix = '')
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
            $data = substr($base64Data, strpos($base64Data, ',') + 1);
            $type = strtolower($type[1]);

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                throw new \Exception('invalid image type');
            }
            $data = str_replace(' ', '+', $data);
            $data = base64_decode($data);

            if ($data === false) {
                throw new \Exception('base64_decode failed');
            }
        } else {
            return $base64Data;
        }

        $fileName = ($prefix ? $prefix . '_' : '') . Str::random(20) . '.' . $type;
        $path = $directory . '/' . $fileName;

        Storage::disk('public')->put($path, $data);

        return $path;
    }
}
