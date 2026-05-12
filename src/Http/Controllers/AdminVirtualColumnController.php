<?php

namespace Softpro\Core\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminVirtualColumnController extends Controller
{
    public function index()
    {
        $driver = DB::connection()->getDriverName();
        $virtualColumns = [];

        if ($driver === 'mysql') {
            $columns = DB::select("SHOW FULL COLUMNS FROM applications");
            $vCols = array_filter($columns, function($col) {
                return str_contains($col->Extra, 'VIRTUAL') || str_contains($col->Extra, 'STORED') || str_contains($col->Extra, 'GENERATED');
            });
            $virtualColumns = array_map(fn($col) => ['Field' => $col->Field, 'Type' => $col->Type, 'Extra' => $col->Extra], array_values($vCols));
        } else {
            $columns = DB::select("PRAGMA table_xinfo(applications)");
            $vCols = array_filter($columns, function($col) { return $col->hidden > 0; });
            $virtualColumns = array_map(fn($col) => ['Field' => $col->name, 'Type' => $col->type, 'Extra' => 'VIRTUAL'], array_values($vCols));
        }

        $sampleKeys = [];
        $latestApp = DB::table('applications')->whereNotNull('responses')->latest()->first();
        if ($latestApp) {
            $responses = json_decode($latestApp->responses, true);
            if (is_array($responses)) { $sampleKeys = array_keys($responses); }
        }

        return Inertia::render('Admin/VirtualColumns/Index', [
            'virtualColumns' => $virtualColumns,
            'sampleKeys' => $sampleKeys,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'column_name' => 'required|string|alpha_dash|max:64',
            'json_key' => 'required|string',
            'data_type' => 'required|string|in:DECIMAL(10,2),INTEGER,VARCHAR(255),DATE,DATETIME',
        ]);

        $colName = $request->column_name;
        $jsonKey = $request->json_key;
        $dataType = $request->data_type;
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            if (str_contains($dataType, 'DECIMAL')) $dataType = 'NUMERIC';
            if ($dataType === 'VARCHAR(255)') $dataType = 'TEXT';
        }

        if ($driver === 'mysql') {
            $sql = "ALTER TABLE applications ADD COLUMN {$colName} {$dataType} GENERATED ALWAYS AS (responses->>'$.\"{$jsonKey}\"') VIRTUAL";
        } else {
            $sql = "ALTER TABLE applications ADD COLUMN {$colName} {$dataType} GENERATED ALWAYS AS (JSON_EXTRACT(responses, '$.\"{$jsonKey}\"')) VIRTUAL";
        }

        try {
            DB::statement($sql);
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE applications ADD INDEX ( {$colName} )");
            } else {
                DB::statement("CREATE INDEX idx_app_{$colName} ON applications({$colName})");
            }
            return redirect()->back()->with('success', "Virtual column '{$colName}' added successfully.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Failed to add column: " . $e->getMessage())
                ->with('manual_sql', $sql);
        }
    }

    public function destroy($columnName)
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $columns = DB::select("SHOW FULL COLUMNS FROM applications WHERE Field = ?", [$columnName]);
            if (empty($columns) || !str_contains($columns[0]->Extra, 'GENERATED')) {
                return redirect()->back()->with('error', "Cannot drop non-virtual column.");
            }
        }

        try {
            DB::statement("ALTER TABLE applications DROP COLUMN {$columnName}");
            return redirect()->back()->with('success', "Column '{$columnName}' dropped.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', "Failed to drop column: " . $e->getMessage());
        }
    }
}
