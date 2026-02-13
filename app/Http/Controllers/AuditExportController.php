<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Audit Export Controller
 *
 * Handles export of audit logs to CSV/Excel formats
 */
class AuditExportController extends Controller
{
    /**
     * Download audit logs based on session filters
     */
    public function download(Request $request): StreamedResponse
    {
        // Check authorization
        if (!Auth::check() || !Auth::user()->isSuperAdmin()) {
            abort(403, 'Unauthorized access.');
        }

        $filters = session('audit_export_filters', []);
        $format = session('audit_export_format', 'csv');

        // Clear session
        session()->forget(['audit_export_filters', 'audit_export_format']);

        // Build query
        $query = AuditLog::with('actor');

        if (!empty($filters['search'])) {
            $query->searchDescription($filters['search']);
        }

        if (!empty($filters['actionFilter'])) {
            $query->where('action', $filters['actionFilter']);
        }

        if (!empty($filters['entityTypeFilter'])) {
            $query->where('entity_type', $filters['entityTypeFilter']);
        }

        if (!empty($filters['actorFilter'])) {
            $query->byActor($filters['actorFilter']);
        }

        if (!empty($filters['startDate'])) {
            $query->where('created_at', '>=', $filters['startDate']);
        }

        if (!empty($filters['endDate'])) {
            $query->where('created_at', '<=', $filters['endDate'] . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')->get();

        $filename = 'audit-logs-' . now()->format('Y-m-d-His');

        if ($format === 'csv') {
            return $this->downloadCsv($logs, $filename);
        }

        return $this->downloadExcel($logs, $filename);
    }

    /**
     * Download as CSV
     */
    protected function downloadCsv($logs, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($file, "\xEF\xBB\xBF");

            // CSV header
            fputcsv($file, [
                'ID',
                'Timestamp',
                'Actor',
                'Action',
                'Entity Type',
                'Entity ID',
                'Description',
                'Changes',
                'IP Address',
            ]);

            // CSV rows
            foreach ($logs as $log) {
                fputcsv($file, [
                    $log->id,
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->actor?->name ?? 'System',
                    $log->action_description,
                    $log->entity_type,
                    $log->entity_id,
                    $log->description ?? '',
                    json_encode($log->changes, JSON_UNESCAPED_UNICODE),
                    $log->ip_address ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Download as Excel (simple HTML table format)
     */
    protected function downloadExcel($logs, string $filename): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '.xls"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($logs) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />';
            echo '<table border="1">';
            echo '<tr>';
            echo '<th>ID</th>';
            echo '<th>Timestamp</th>';
            echo '<th>Actor</th>';
            echo '<th>Action</th>';
            echo '<th>Entity Type</th>';
            echo '<th>Entity ID</th>';
            echo '<th>Description</th>';
            echo '<th>Changes</th>';
            echo '<th>IP Address</th>';
            echo '</tr>';

            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($log->id) . '</td>';
                echo '<td>' . htmlspecialchars($log->created_at->format('Y-m-d H:i:s')) . '</td>';
                echo '<td>' . htmlspecialchars($log->actor?->name ?? 'System') . '</td>';
                echo '<td>' . htmlspecialchars($log->action_description) . '</td>';
                echo '<td>' . htmlspecialchars($log->entity_type) . '</td>';
                echo '<td>' . htmlspecialchars($log->entity_id) . '</td>';
                echo '<td>' . htmlspecialchars($log->description ?? '') . '</td>';
                echo '<td>' . htmlspecialchars(json_encode($log->changes, JSON_UNESCAPED_UNICODE)) . '</td>';
                echo '<td>' . htmlspecialchars($log->ip_address ?? '') . '</td>';
                echo '</tr>';
            }

            echo '</table>';
        };

        return response()->stream($callback, 200, $headers);
    }
}
