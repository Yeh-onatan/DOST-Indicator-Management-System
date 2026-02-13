<div class="p-6 bg-white dark:bg-neutral-800 shadow-xl sm:rounded-lg">
    <h3 class="text-xl font-bold mb-4 text-[var(--text)] dark:text-white">Your Submitted Objectives</h3>
    
    <!-- Search Bar -->
    <div class="mb-4">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search objectives by title or description..."
               class="w-full border-gray-300 rounded-lg shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-neutral-700 dark:border-neutral-600 dark:text-white">
    </div>

    @if($objectives->isEmpty())
        <div class="text-center py-10 text-[var(--text)-muted]">
            <p>You have not submitted any objectives yet.</p>
            <p class="mt-2">Click <a href="{{ route('dashboard') }}" class="text-indigo-600 hover:text-indigo-800 font-semibold">here</a> to return to the dashboard and create one.</p>
        </div>
    @else
        <!-- Objectives Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-neutral-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-neutral-700">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Target</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Period</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 dark:bg-neutral-800 dark:divide-neutral-700">
                    @php
                        $nf = auth()->user()?->settings?->number_format === '1.234,56'
                            ? ['th' => '.', 'dec' => ',']
                            : ['th' => ',', 'dec' => '.'];
                        $precisionMap = [
                            'target_value' => 2,
                        ];
                        $fmtNum = function($n, $key = null) use ($nf, $precisionMap) {
                            if ($n === null || $n === '') return '0';
                            $p = $precisionMap[$key] ?? 2;
                            return number_format((float)$n, $p, $nf['dec'], $nf['th']);
                        };
                    @endphp
                    @foreach ($objectives as $objective)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-[var(--text)] dark:text-white">{{ $objective->title }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--text)-muted]">{{ $fmtNum($objective->target_value, 'target_value') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-[var(--text)-muted]">{{ $objective->target_period }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($objective->status == 'DRAFT') bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100
                                    @elseif($objective->status == 'APPROVED') bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100
                                    @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    {{ $objective->status === 'DRAFT' ? 'FOR APPROVAL' : $objective->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="#" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-200">View/Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $objectives->links() }}
        </div>
    @endif
</div>
