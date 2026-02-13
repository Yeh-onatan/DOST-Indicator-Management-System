<section class="w-full">
    <div class="relative mb-6 w-full">
        <h1 class="text-2xl font-extrabold text-[var(--text)]">Audit Logs</h1>
        <p class="text-[var(--text-muted)]">Who changed what and when</p>
        <hr class="border-[var(--border)]">
    </div>
    <div class="space-y-3">
            @forelse($logs as $log)
                <div class="rounded-lg border border-[var(--border)] bg-[var(--card-bg)] p-3 md:p-4">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="whitespace-nowrap text-[var(--text-muted)]">{{ $log->created_at?->format('Y-m-d H:i') }}</span>
                        <span class="text-[var(--text)]">•</span>
                        <span class="font-medium">{{ optional($log->actor)->name ?? '—' }}</span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-600/10 text-blue-600 border border-blue-600/30">
                            {{ ucfirst($log->action) }}
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-zinc-500/10 text-[var(--text)] border border-[var(--border)]">
                            {{ $log->entity_type }} #{{ $log->entity_id }}
                        </span>
                        @php
                            $st = ($log->entity_type === 'Objective') ? ($objectiveStatuses[$log->entity_id] ?? null) : null;
                        @endphp
                        @if($st)
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium border @class([
                                'bg-green-500/10 text-green-600 border-green-600/30' => $st === 'APPROVED',
                                'bg-yellow-500/10 text-yellow-600 border-yellow-600/30' => $st === 'DRAFT',
                                'bg-rose-500/10 text-rose-600 border-rose-600/30' => $st === 'REJECTED',
                                'bg-zinc-500/10 text-zinc-600 border-zinc-600/30' => !in_array($st,['APPROVED','DRAFT','REJECTED'])
                            ])">
                                Status: {{ $st === 'DRAFT' ? 'FOR APPROVAL' : $st }}
                            </span>
                        @elseif($log->entity_type === 'Objective')
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-rose-500/10 text-rose-600 border border-rose-600/30">
                                Unapproved
                            </span>
                        @endif
                    </div>

                    @php
                        $c = $log->changes;
                        $fmt = function($v){
                            if (is_null($v) || $v === '') return '—';
                            return is_scalar($v) ? (string)$v : json_encode($v, JSON_UNESCAPED_UNICODE);
                        };
                    @endphp

                    <div class="mt-3 text-xs">
                        @if (isset($c['diff']) && is_array($c['diff']) && count($c['diff']))
                            <div class="overflow-auto rounded-lg border border-[var(--border)]">
                                <table class="w-full text-left">
                                    <thead class="bg-[var(--bg)] text-[var(--text-muted)]">
                                        <tr>
                                            <th class="p-2">Field</th>
                                            <th class="p-2">Before</th>
                                            <th class="p-2">After</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($c['diff'] as $field => $pair)
                                            @php
                                                $before = $pair['before'] ?? null;
                                                $after = $pair['after'] ?? null;
                                                $beforeVal = $fmt($before);
                                                $afterVal = $fmt($after);
                                                $showBeforeQuotes = !is_null($before) && $before !== '';
                                                $showAfterQuotes = !is_null($after) && $after !== '';
                                            @endphp
                                            <tr class="border-t border-[var(--border)]">
                                                <td class="p-2 font-medium text-[var(--text)]">{{ $field }}</td>
                                                <td class="p-2 text-red-600/80 line-through break-words">
                                                    @if($showBeforeQuotes)&quot;{{ $beforeVal }}&quot;@else{{ $beforeVal }}@endif
                                                    <span class="ms-1 text-[var(--text-muted)]">(before)</span>
                                                </td>
                                                <td class="p-2 text-green-600 font-semibold break-words">
                                                    @if($showAfterQuotes)&quot;{{ $afterVal }}&quot;@else{{ $afterVal }}@endif
                                                    <span class="ms-1 text-[var(--text-muted)]">(after)</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @elseif(isset($c['deleted']))
                            <div class="rounded-md bg-rose-500/5 border border-rose-500/20 p-2 text-[var(--text)]">Deleted snapshot</div>
                            <pre class="mt-2 whitespace-pre-wrap text-[var(--text-muted)]">{{ json_encode($c['deleted'], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        @elseif($c)
                            <pre class="whitespace-pre-wrap text-[var(--text-muted)]">{{ json_encode($c, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre>
                        @else
                            <span class="text-[var(--text-muted)]">—</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center p-6 text-[var(--text-muted)] border rounded">No logs</div>
            @endforelse
    </div>
</section>
