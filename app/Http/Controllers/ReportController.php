<?php

namespace App\Http\Controllers;

use App\Models\Indicator as Objective;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Indicators report (table + filters).
     */
    public function indicators(Request $request)
    {
        $q = Objective::query();

        // Simple filters
        if ($search = $request->string('q')->trim()) {
            $q->where(function ($qq) use ($search) {
                $qq->where('objective_result', 'like', "%{$search}%")
                   ->orWhere('indicator', 'like', "%{$search}%")
                   ->orWhere('description', 'like', "%{$search}%")
                   ->orWhere('dost_agency', 'like', "%{$search}%");
            });
        }

        if ($agency = $request->string('agency')->trim()) {
            $q->where('dost_agency', 'like', "%{$agency}%");
        }

        if ($period = $request->string('period')->trim()) {
            $q->where('target_period', 'like', "%{$period}%");
        }

        $objectives = $q->latest('id')->paginate(15)->withQueryString();

        return view('reports.indicators', [
            'objectives' => $objectives,
            'filters'    => $request->only(['q', 'agency', 'period']),
        ]);
    }

    /**
     * CSV export for the same filters.
     */
    public function exportIndicatorsCsv(Request $request): StreamedResponse
    {
        $filename = 'indicators-'.now()->format('Ymd_His').'.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($request) {
            $handle = fopen('php://output', 'w');

            // Header row
            fputcsv($handle, [
                'ID', 'Objective/Result', 'Indicator', 'Description', 'DOST Agency',
                'Baseline', 'Accomplishments', 'Annual Targets', 'Target Period',
                'Target Value', 'MOV', 'Responsible', 'Reporting',
                'Assumptions/Risk', 'PC Secretariat Remarks', 'Created At',
            ]);

            // Reuse the same filter logic
            $q = Objective::query();
            if ($search = $request->string('q')->trim()) {
                $q->where(function ($qq) use ($search) {
                    $qq->where('objective_result', 'like', "%{$search}%")
                       ->orWhere('indicator', 'like', "%{$search}%")
                       ->orWhere('description', 'like', "%{$search}%")
                       ->orWhere('dost_agency', 'like', "%{$search}%");
                });
            }
            if ($agency = $request->string('agency')->trim()) {
                $q->where('dost_agency', 'like', "%{$agency}%");
            }
            if ($period = $request->string('period')->trim()) {
                $q->where('target_period', 'like', "%{$period}%");
            }

            $q->orderByDesc('id')->chunk(500, function ($rows) use ($handle) {
                foreach ($rows as $r) {
                    fputcsv($handle, [
                        $r->id,
                        $r->objective_result,
                        $r->indicator,
                        $r->description,
                        $r->dost_agency,
                        $r->baseline,
                        $r->accomplishments,
                        $r->annual_plan_targets,
                        $r->target_period,
                        $r->target_value,
                        $r->mov,
                        $r->responsible_agency,
                        $r->reporting_agency,
                        $r->assumptions_risk,
                        $r->pc_secretariat_remarks,
                        optional($r->created_at)->toDateTimeString(),
                    ]);
                }
            });

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Printer-friendly HTML (you can switch to a real PDF later).
     */
    public function exportIndicatorsPdf(Request $request)
    {
        // Reuse filters
        $q = Objective::query();
        if ($search = $request->string('q')->trim()) {
            $q->where(function ($qq) use ($search) {
                $qq->where('objective_result', 'like', "%{$search}%")
                   ->orWhere('indicator', 'like', "%{$search}%")
                   ->orWhere('description', 'like', "%{$search}%")
                   ->orWhere('dost_agency', 'like', "%{$search}%");
            });
        }
        if ($agency = $request->string('agency')->trim()) {
            $q->where('dost_agency', 'like', "%{$agency}%");
        }
        if ($period = $request->string('period')->trim()) {
            $q->where('target_period', 'like', "%{$period}%");
        }

        $rows = $q->orderBy('dost_agency')->orderBy('objective_result')->get();

        // Simple printable view (create this Blade)
        return view('reports.indicators-pdf', [
            'rows'    => $rows,
            'printed' => now(),
        ]);
    }
}
