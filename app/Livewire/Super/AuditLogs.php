<?php

namespace App\Livewire\Super;

use Livewire\Attributes\Url;
use Livewire\Component;
use App\Models\AuditLog;
use App\Models\Indicator as Objective;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;

/**
 * Super Admin: Audit Logs index
 *
 * Enhanced audit log viewer with filtering, pagination, and export functionality.
 */
class AuditLogs extends Component
{
    use WithPagination;

    // URL-bound filters for bookmarkable/sharable links
    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $actionFilter = '';

    #[Url(history: true)]
    public string $entityTypeFilter = '';

    #[Url(history: true)]
    public string $actorFilter = '';

    #[Url(history: true)]
    public string $startDate = '';

    #[Url(history: true)]
    public string $endDate = '';

    #[Url(history: true)]
    public int $perPage = 50;

    // For status display
    /** @var array<string, string|null> Map of objective id => current status */
    public array $objectiveStatuses = [];

    // For export
    public bool $showExportModal = false;
    public string $exportFormat = 'csv';

    /**
     * Reset all filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->entityTypeFilter = '';
        $this->actorFilter = '';
        $this->startDate = '';
        $this->endDate = '';
        $this->resetPage();
    }

    /**
     * Open export modal
     */
    public function openExportModal(): void
    {
        $this->showExportModal = true;
    }

    /**
     * Close export modal
     */
    public function closeExportModal(): void
    {
        $this->showExportModal = false;
        $this->exportFormat = 'csv';
    }

    /**
     * Export audit logs - redirects to download route with filters
     */
    public function export(): void
    {
        // Store filters in session for the export route
        session([
            'audit_export_filters' => [
                'search' => $this->search,
                'actionFilter' => $this->actionFilter,
                'entityTypeFilter' => $this->entityTypeFilter,
                'actorFilter' => $this->actorFilter,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ],
            'audit_export_format' => $this->exportFormat,
        ]);

        $this->closeExportModal();

        // Redirect to export route
        $this->redirect(route('audit-export.download'), navigate: true);
    }

    /**
     * Generate CSV content
     */
    protected function generateCsvContent($logs): string
    {
        $output = fopen('php://temp', 'r+');

        // CSV header
        fputcsv($output, [
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
            fputcsv($output, [
                $log->id,
                $log->created_at->format('Y-m-d H:i:s'),
                $log->actor?->name ?? 'System',
                $log->action_description,
                $log->entity_type,
                $log->entity_id,
                $log->description ?? '',
                json_encode($log->changes),
                $log->ip_address ?? '',
            ]);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Generate Excel content (simple HTML table format)
     */
    protected function generateExcelContent($logs): string
    {
        $output = '<table border="1">';
        $output .= '<tr>';
        $output .= '<th>ID</th>';
        $output .= '<th>Timestamp</th>';
        $output .= '<th>Actor</th>';
        $output .= '<th>Action</th>';
        $output .= '<th>Entity Type</th>';
        $output .= '<th>Entity ID</th>';
        $output .= '<th>Description</th>';
        $output .= '<th>Changes</th>';
        $output .= '<th>IP Address</th>';
        $output .= '</tr>';

        foreach ($logs as $log) {
            $output .= '<tr>';
            $output .= '<td>' . $log->id . '</td>';
            $output .= '<td>' . $log->created_at->format('Y-m-d H:i:s') . '</td>';
            $output .= '<td>' . htmlspecialchars($log->actor?->name ?? 'System') . '</td>';
            $output .= '<td>' . htmlspecialchars($log->action_description) . '</td>';
            $output .= '<td>' . htmlspecialchars($log->entity_type) . '</td>';
            $output .= '<td>' . htmlspecialchars($log->entity_id) . '</td>';
            $output .= '<td>' . htmlspecialchars($log->description ?? '') . '</td>';
            $output .= '<td>' . htmlspecialchars(json_encode($log->changes)) . '</td>';
            $output .= '<td>' . htmlspecialchars($log->ip_address ?? '') . '</td>';
            $output .= '</tr>';
        }

        $output .= '</table>';
        return $output;
    }

    /**
     * Get the filtered logs query
     */
    protected function getFilteredLogsQuery()
    {
        $query = AuditLog::with('actor');

        // Search in description
        if ($this->search) {
            $query->searchDescription($this->search);
        }

        // Filter by action
        if ($this->actionFilter) {
            $query->where('action', $this->actionFilter);
        }

        // Filter by entity type
        if ($this->entityTypeFilter) {
            $query->where('entity_type', $this->entityTypeFilter);
        }

        // Filter by actor
        if ($this->actorFilter) {
            $query->byActor($this->actorFilter);
        }

        // Filter by date range
        if ($this->startDate) {
            $query->where('created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->where('created_at', '<=', $this->endDate . ' 23:59:59');
        }

        return $query;
    }

    /**
     * Get paginated logs
     */
    public function getLogsProperty()
    {
        $logs = $this->getFilteredLogsQuery()
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        // Preload Objective statuses for relevant logs
        $ids = $logs
            ->filter(fn($log) => $log->entity_type === 'Objective' && $log->entity_id)
            ->pluck('entity_id')
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            $map = Objective::whereIn('id', $ids)->pluck('status', 'id');
            $this->objectiveStatuses = collect($map)->mapWithKeys(fn($v, $k) => [(string)$k => $v])->toArray();
        }

        return $logs;
    }

    /**
     * Get available filters
     */
    public function getAvailableFiltersProperty(): array
    {
        return [
            'actions' => AuditLog::select('action')->distinct()->orderBy('action')->pluck('action')->toArray(),
            'entityTypes' => AuditLog::select('entity_type')->whereNotNull('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type')->toArray(),
            'actors' => User::select('id', 'name')->whereHas('auditLogs')->orderBy('name')->get()->toArray(),
        ];
    }

    /**
     * Get active filter count
     */
    public function getActiveFilterCountProperty(): int
    {
        return (int) $this->search +
            (int) $this->actionFilter +
            (int) $this->entityTypeFilter +
            (int) $this->actorFilter +
            (int) $this->startDate +
            (int) $this->endDate;
    }

    public function render()
    {
        return view('livewire.super.audit-logs', [
            'logs' => $this->logs,
            'availableFilters' => $this->availableFilters,
        ])->layout('components.layouts.app');
    }
}
