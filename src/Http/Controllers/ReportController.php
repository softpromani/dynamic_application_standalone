<?php

namespace Softpro\Core\Http\Controllers;

use Softpro\Core\Models\Application;
use Softpro\Core\Models\Program;
use Softpro\Core\Models\Opening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportController extends Controller
{
    public function index()
    {
        $programs = Program::orderBy('title')->get(['id', 'title', 'job_code']);
        
        return Inertia::render('Admin/Reports', [
            'jobs' => $programs
        ]);
    }

    public function masterExcel(Request $request)
    {
        $spreadsheet = new Spreadsheet();
        
        // --- Sheet 1: Executive Summary ---
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Executive Summary');
        
        $this->setStyleHeader($sheet1, 'A1:C1');
        $sheet1->setCellValue('A1', 'Report Parameter');
        $sheet1->setCellValue('B1', 'Metric');
        $sheet1->setCellValue('C1', 'Count');
        
        $stats = [
            ['Total Applications', 'All Statuses', Application::count()],
            ['Submitted Applications', 'Ready for Review', Application::where('form_status', 'submitted')->count()],
            ['Paid Applications', 'Successful Payment', Application::where('status', 'paid')->count()],
            ['Draft Applications', 'Incomplete', Application::where('form_status', 'draft')->count()],
        ];
        
        $row = 2;
        foreach ($stats as $stat) {
            $sheet1->fromArray($stat, NULL, 'A' . $row);
            $row++;
        }
        
        // --- Sheet 2: All Applications (Master List) ---
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Master Application List');
        
        $headers = [
            'App No', 'Applicant Name', 'Email', 'Phone', 'Gender', 'Category', 
            'Subject', 'Program Code', 'Form Status', 'Payment Status', 'Action Status', 'Total Amount', 'Applied Date'
        ];
        
        $this->setStyleHeader($sheet2, 'A1:L1');
        $sheet2->fromArray($headers, NULL, 'A1');
        
        $query = Application::with(['applicant', 'opening.subject', 'opening.job']);
        if ($request->filled('job_id')) {
            $query->whereHas('opening', fn($q) => $q->where('program_id', $request->job_id));
        }
        $applications = $query->latest()->get();
        
        $dataRows = [];
        foreach ($applications as $app) {
            $dataRows[] = [
                $app->application_no,
                $app->applicant->name,
                $app->applicant->email,
                $app->applicant->phone,
                $app->applicant->gender,
                $app->applicant->category,
                $app->opening->subject ? $app->opening->subject->label : '—',
                $app->opening->job->job_code,
                $app->form_status,
                $app->status,
                $app->action_status,
                $app->total_amount,
                $app->created_at->format('Y-m-d H:i'),
            ];
        }
        $sheet2->fromArray($dataRows, NULL, 'A2');
        
        // --- Sheet 3: Subject-wise Stats ---
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Subject-wise Counts');
        
        $this->setStyleHeader($sheet3, 'A1:D1');
        $sheet3->setCellValue('A1', 'Subject Code');
        $sheet3->setCellValue('B1', 'Subject Name');
        $sheet3->setCellValue('C1', 'Program Title');
        $sheet3->setCellValue('D1', 'Applications');
        
        $subjectStats = Opening::with(['subject', 'job'])
            ->withCount('applications')
            ->orderBy('applications_count', 'desc')
            ->get();
            
        $subRows = [];
        foreach ($subjectStats as $vs) {
            $subRows[] = [
                $vs->subject ? $vs->subject->value : '—',
                $vs->subject ? $vs->subject->label : '—',
                $vs->job->title,
                $vs->applications_count
            ];
        }
        $sheet3->fromArray($subRows, NULL, 'A2');
        
        // Auto-size columns
        foreach ($spreadsheet->getAllSheets() as $s) {
            foreach (range('A', $s->getHighestColumn()) as $col) {
                $s->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'LNMU_Master_Report_' . now()->format('Ymd_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        $writer->save('php://output');
        exit;
    }

    public function dossierExcel(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        
        $applications = Application::with([
            'applicant', 
            'opening.subject', 
            'opening.job.formTemplate.fields' => function($q) {
                $q->orderBy('step')->orderBy('sort_order');
            }
        ])->whereIn('id', $request->ids)->get();

        if ($applications->isEmpty()) {
            return back()->with('error', 'No applications selected.');
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($applications as $app) {
            $sheet = $spreadsheet->createSheet();
            $sheetTitle = substr($app->application_no, -31);
            $sheet->setTitle($sheetTitle);

            $row = 1;
            
            $sheet->mergeCells("A$row:C$row");
            $sheet->setCellValue("A$row", "Application Dossier: " . $app->application_no);
            $this->setStyleHeader($sheet, "A$row:C$row");
            $row += 2;

            $sheet->setCellValue("A$row", "Basic Information")->getStyle("A$row")->getFont()->setBold(true);
            $row++;
            $sheet->fromArray([
                ['Field', 'Value'],
                ['Name', $app->applicant->name],
                ['Email', $app->applicant->email],
                ['Phone', $app->applicant->phone],
                ['Gender', $app->applicant->gender],
                ['Category', $app->applicant->category],
                ['Subject', $app->opening->subject ? $app->opening->subject->label : '—'],
                ['Form Status', $app->form_status],
                ['Payment Status', $app->status],
                ['Action Status', $app->action_status],
                ['Applied Date', $app->created_at->format('d-m-Y H:i')]
            ], NULL, "A$row");
            $this->setStyleSubHeader($sheet, "A$row:B$row");
            $row += 11;

            $sheet->setCellValue("A$row", "Detailed Responses")->getStyle("A$row")->getFont()->setBold(true);
            $row++;
            $sheet->setCellValue("A$row", "Field Label");
            $sheet->setCellValue("B$row", "Value");
            $this->setStyleSubHeader($sheet, "A$row:B$row");
            $row++;

            $templateFields = $app->opening->job->formTemplate->fields;
            $responses = is_array($app->responses) ? $app->responses : [];

            foreach ($templateFields as $field) {
                $value = '—';
                if ($field->system_alias && isset($responses[$field->system_alias])) {
                    $value = $responses[$field->system_alias];
                } elseif (isset($responses[$field->id])) {
                    $value = $responses[$field->id];
                }

                if ($field->field_type === 'file' && $value && $value !== '—') {
                    $url = asset('storage/' . $value);
                    $sheet->setCellValue("A$row", $field->label);
                    $sheet->setCellValue("B$row", "View File");
                    $sheet->getCell("B$row")->getHyperlink()->setUrl($url);
                    $sheet->getStyle("B$row")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF0000FF'))->setUnderline(true);
                    $row++;
                } 
                elseif ($field->field_type === 'table' && $value && is_array($value)) {
                    $sheet->setCellValue("A$row", $field->label);
                    $row++;
                    if (!empty($value)) {
                        $cols = array_keys($value[0]);
                        $sheet->fromArray([$cols], NULL, "B$row");
                        $endCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($cols) + 1);
                        $this->setStyleSubHeader($sheet, "B$row:{$endCol}$row");
                        $row++;
                        foreach ($value as $tr) {
                            $sheet->fromArray([array_values($tr)], NULL, "B$row");
                            $row++;
                        }
                    } else {
                        $sheet->setCellValue("B$row", "No data");
                        $row++;
                    }
                }
                else {
                    $sheet->setCellValue("A$row", $field->label);
                    $displayVal = is_array($value) ? implode(', ', $value) : $value;
                    $sheet->setCellValue("B$row", $displayVal);
                    $row++;
                }
            }

            foreach (range('A', 'Z') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'LNMU_Dossier_Export_' . now()->format('Ymd_His') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. $fileName .'"');
        $writer->save('php://output');
        exit;
    }

    private function setStyleHeader($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 14],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '8B0000']
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }

    private function setStyleSubHeader($sheet, $range)
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '333333']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EEEEEE']
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);
    }
}
