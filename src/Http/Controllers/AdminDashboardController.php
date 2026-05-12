<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\Application;
use Softpro\Core\Models\Applicant;
use Softpro\Core\Models\Program;
use Softpro\Core\Models\Opening;
use Softpro\Core\Models\CustomEntity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $subjectEntity = CustomEntity::where('name', 'subject')->first();
        $stats = [
            'total_applications' => Application::count(),
            'total_candidates'   => Applicant::count(),
            'paid_applications'  => Application::where('status', 'paid')->count(),
            'submitted_applications' => Application::where('form_status', 'submitted')->count(),
            'pending_payments'   => Application::where('status', 'pending')->count(),
            'total_programs'     => Program::count(),
            'total_subjects'     => $subjectEntity ? $subjectEntity->values()->count() : 0,
        ];

        $status_breakdown = Application::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        $program_breakdown = Program::withCount('applications')
            ->get(['id', 'title', 'job_code'])
            ->map(fn($program) => [
                'label' => $program->job_code,
                'title' => $program->title,
                'value' => $program->applications_count
            ]);

        $recent_applications = Application::with(['applicant', 'opening.subject'])
            ->latest()
            ->take(10)
            ->get();

        $subject_breakdown = DB::table('custom_entity_values')
            ->join('openings', 'custom_entity_values.id', '=', 'openings.subject_id')
            ->join('applications', 'openings.id', '=', 'applications.opening_id')
            ->select('custom_entity_values.label as label', DB::raw('count(applications.id) as value'))
            ->where('custom_entity_values.custom_entity_id', $subjectEntity?->id)
            ->groupBy('custom_entity_values.id', 'custom_entity_values.label')
            ->orderByDesc('value')
            ->take(10)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats'            => $stats,
            'statusBreakdown'  => $status_breakdown,
            'programBreakdown' => $program_breakdown,
            'subjectBreakdown' => $subject_breakdown,
            'recentApps'       => $recent_applications,
        ]);
    }

    public function exportApplications(Request $request)
    {
        $query = Application::with([
            'applicant',
            'opening.subject',
            'opening.program',
        ]);

        if ($request->filled('program_id') || $request->filled('job_id')) {
            $pid = $request->program_id ?? $request->job_id;
            $query->whereHas('opening', fn($q) => $q->where('program_id', $pid));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('form_status')) {
            $query->where('form_status', $request->form_status);
        }

        if ($request->filled('action_status')) {
            $query->where('action_status', $request->action_status);
        }

        $applications = $query->latest()->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="applications_export_' . now()->format('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($applications) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, [
                'App No', 
                'Applicant Name', 
                'Email', 
                'Phone', 
                'Gender',
                'Category',
                'Subject', 
                'Program Code', 
                'Form Status', 
                'Payment Status',
                'Action Status',
                'Applied Date'
            ]);

            foreach ($applications as $app) {
                fputcsv($file, [
                    $app->application_no,
                    $app->applicant->name,
                    $app->applicant->email,
                    $app->applicant->phone,
                    $app->applicant->gender,
                    $app->applicant->category,
                    $app->opening->subject ? $app->opening->subject->label : '—',
                    $app->opening->program->job_code,
                    $app->form_status,
                    $app->status,
                    $app->action_status,
                    $app->created_at->format('Y-m-d H:i'),
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
