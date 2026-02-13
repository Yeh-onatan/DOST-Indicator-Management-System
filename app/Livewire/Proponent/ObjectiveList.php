<?php

namespace App\Livewire\Proponent;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Indicator as Objective;   // <= add this use
use Illuminate\Support\Facades\Auth;

class ObjectiveList extends Component
{
    use WithPagination;

    public string $search = '';
    public int $pageSize = 10;

    protected $queryString = ['search'];

    public function mount(): void
    {
        $this->pageSize = (int) (auth()->user()?->settings?->table_page_size ?? 10);
        if (! in_array($this->pageSize, [10, 25, 50], true)) {
            $this->pageSize = 10;
        }
    }

    protected function getObjectives()
    {
        $userId = Auth::id();
        if (! $userId) {
            return Objective::query()->whereRaw('1=0')->paginate($this->pageSize);
        }

        return Objective::where('submitted_by_user_id', $userId)
            ->when($this->search, function ($q) {
                $s = '%'.$this->search.'%';
                $q->where(function ($q2) use ($s) {
                    $q2->where('objective_result', 'like', $s)
                       ->orWhere('indicator', 'like', $s)
                       ->orWhere('description', 'like', $s);
                });
            })
            ->latest()
            ->paginate($this->pageSize);
    }

    public function render()
    {
        return view('livewire.proponent.objective-list', [
            'objectives' => $this->getObjectives(),
        ]);
    }
}