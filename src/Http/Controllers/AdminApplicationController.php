<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\Application;
use Softpro\Core\Models\Program;
use Softpro\Core\Models\Transaction;
use Softpro\Core\Models\CustomEntityValue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminApplicationController extends Controller
{
    public function index(Request $request)
    {
        $query = Application::with([
            'applicant',
            'opening.subject',
            'opening.program',
        ]);

        if ($request->filled('program_id')) {
            $query->whereHas('opening', fn($q) => $q->where('program_id', $request->program_id));
        }

        if ($request->filled('action_status')) {
            $query->where('action_status', $request->action_status);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('form_status')) {
            $query->where('form_status', $request->form_status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('application_no', 'like', "%{$search}%")
                  ->orWhereHas('applicant', function($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $applications = $query->latest()->paginate(20)->withQueryString();
        $programs = Program::orderBy('title')->get(['id', 'title', 'job_code']);

        return Inertia::render('Applications/Index', [
            'applications' => $applications,
            'programs'     => $programs,
            'filters'      => $request->only(['program_id', 'action_status', 'status', 'form_status', 'search']),
        ]);
    }

    public function show(Application $application)
    {
        $application->load([
            'applicant',
            'opening.subject',
            'opening.program.formTemplate.fields',
            'transactions',
        ]);

        return Inertia::render('Applications/Show', [
            'application' => $application,
        ]);
    }

    public function updateStatus(Request $request, Application $application)
    {
        $request->validate([
            'action_status' => 'required|in:pending,under_review,approved,rejected',
        ]);

        $application->update(['action_status' => $request->action_status]);

        return redirect()->back()->with('success', 'Application status updated.');
    }

    public function unlockForm(Application $application)
    {
        $application->update(['form_status' => 'draft']);

        return redirect()->back()->with('success', 'Application unlocked for applicant editing.');
    }

    public function print(Application $application)
    {
        $application->load([
            'opening.program.formTemplate.fields' => function($q) {
                $q->orderBy('step')->orderBy('sort_order');
            }, 
            'opening.subject', 
            'applicant'
        ]);

        $template = $application->opening->program->formTemplate;
        $responses = $application->responses;

        return Inertia::render('Templates/PrintPreview', [
            'template' => $template,
            'application' => $application,
            'applicant' => $application->applicant,
            'responses' => $responses,
            // Fallback to CustomEntityValue if Subject model is missing
            'subjects' => CustomEntityValue::orderBy('label')->get(['id', 'label as name', 'value as code']),
        ]);
    }

    public function refreshPaymentStatus(Application $application)
    {
        $transaction = $application->transactions()
            ->whereNotIn('status', ['success', 'completed'])
            ->latest()
            ->first();

        if (!$transaction) {
            return redirect()->back()->with('error', 'No pending transaction found for this application.');
        }

        try {
            $path = storage_path() . '/json/worldline_paymentgateway.json';
            if (!file_exists($path)) {
                return redirect()->back()->with('error', 'Payment gateway configuration missing.');
            }
            
            $mer_array = json_decode(file_get_contents($path), true);
            date_default_timezone_set('Asia/Calcutta');
            $strCurDate = date('d-m-Y');

            $arr_req = [
                'merchant'    => ['identifier' => $mer_array['merchantCode']],
                'transaction' => [
                    'deviceIdentifier' => 'S',
                    'currency'         => $mer_array['currency'],
                    'identifier'       => $transaction->merchant_transaction_id,
                    'dateTime'         => $strCurDate,
                    'requestType'      => 'O',
                ],
            ];

            $curl = curl_init();
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($arr_req));
            curl_setopt($curl, CURLOPT_URL, 'https://www.paynimo.com/api/paynimoV2.req');
            curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $res_result = curl_exec($curl);
            curl_close($curl);

            $responseData = json_decode($res_result, true);
            $statusCode   = $responseData['paymentMethod']['paymentTransaction']['statusCode'] ?? null;
            $statusMsg    = $responseData['paymentMethod']['paymentTransaction']['statusMessage'] ?? '';

            if ($statusCode) {
                if ($statusCode === '0300') {
                    $finalStatus = 'success';
                } elseif (in_array($statusCode, ['0398', '0399', '0392'])) {
                    $finalStatus = 'failed';
                } elseif (in_array($statusCode, ['0396', '0397', '0002'])) {
                    $finalStatus = 'pending';
                } else {
                    $finalStatus = $transaction->status;
                }

                $transaction->update([
                    'status'        => $finalStatus,
                    'response_data' => array_merge($transaction->response_data ?? [], [
                        'admin_status_check_request'  => $arr_req,
                        'admin_status_check_response' => $responseData,
                        'admin_checked_at'            => now(),
                    ]),
                ]);

                $application->syncPaymentStatus();

                return redirect()->back()->with('success', 'Payment status refreshed: ' . ucfirst($finalStatus));
            }

            return redirect()->back()->with('info', 'Could not determine payment status.');

        } catch (\Exception $e) {
            Log::error('Admin payment refresh failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to refresh payment status.');
        }
    }
    
    public function bulkSyncPayments()
    {
        $applications = Application::all();
        $updatedCount = 0;

        foreach ($applications as $application) {
            $oldStatus = $application->status;
            $newStatus = $application->syncPaymentStatus();
            if ($oldStatus !== $newStatus) {
                $updatedCount++;
            }
        }

        return redirect()->back()->with('success', "Synchronization completed. $updatedCount applications were updated.");
    }
}
