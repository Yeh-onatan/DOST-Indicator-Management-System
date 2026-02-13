<?php

namespace App\Livewire\Super;

use App\Models\AuditLog;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * EntityHistory Component
 *
 * Displays audit history for a specific entity
 * Can be loaded as a standalone page or in a modal
 */
class EntityHistory extends Component
{
    use WithPagination;

    #[Locked]
    public string $entityType = '';

    #[Locked]
    public string|int $entityId = '';

    public string $entityName = '';
    public string $search = '';
    public string $actionFilter = '';
    public int $perPage = 25;

    /**
     * Mount the component with entity parameters
     */
    public function mount(string $entityType, string|int $entityId, string $entityName = ''): void
    {
        // Authorization check: Only SA, Admin, and Execom can view audit history
        if (!auth()->check() || (!auth()->user()->isSA() && !auth()->user()->isAdministrator() && !auth()->user()->isExecom())) {
            abort(403, 'You do not have permission to view audit history.');
        }

        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->entityName = $entityName;
    }

    /**
     * Listen for entity history modal open event
     */
    #[On('open-entity-history')]
    public function openEntityHistory(string $entityType, string|int $entityId, string $entityName = ''): void
    {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->entityName = $entityName;
        $this->search = '';
        $this->actionFilter = '';
        $this->resetPage();
    }

    /**
     * Reset search filters
     */
    public function resetFilters(): void
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->resetPage();
    }

    /**
     * Get the paginated audit logs for the entity
     */
    public function getAuditLogsProperty()
    {
        return AuditLog::query()
            ->forEntity($this->entityType, $this->entityId)
            ->with('actor')
            ->when($this->search, function ($query) {
                $query->searchDescription($this->search);
            })
            ->when($this->actionFilter, function ($query) {
                $query->where('action', $this->actionFilter);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);
    }

    /**
     * Get available action types for filtering
     */
    public function getAvailableActionsProperty(): array
    {
        return AuditLog::query()
            ->forEntity($this->entityType, $this->entityId)
            ->select('action')
            ->distinct()
            ->pluck('action')
            ->sort()
            ->toArray();
    }

    /**
     * Get human-readable entity type name
     */
    public function getEntityTypeNameProperty(): string
    {
        return match($this->entityType) {
            'Objective' => 'Indicator',
            'User' => 'User',
            'Agency' => 'Agency',
            'Office' => 'Office',
            'Proof' => 'Proof Document',
            default => $this->entityType,
        };
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.super.entity-history', [
            'logs' => $this->auditLogs,
            'availableActions' => $this->availableActions,
        ]);
    }
}
