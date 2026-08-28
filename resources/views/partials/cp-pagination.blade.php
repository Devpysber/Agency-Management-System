{{-- Shared client-portal pagination footer. Expects $paginator (LengthAwarePaginator). --}}
@if ($paginator->total() > 0)
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;
                padding:14px 20px;border-top:1px solid var(--cp-border);">
        <span class="t-muted" style="font-size:12.5px;">
            Showing {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>
        @if ($paginator->hasPages())
            <div style="display:flex;gap:4px;align-items:center;">
                <button class="cp-btn cp-btn-ghost cp-btn-sm" wire:click="previousPage"
                        @disabled($paginator->onFirstPage())>
                    <i class="fas fa-chevron-left"></i>
                </button>
                @php
                    $start = max(1, $paginator->currentPage() - 2);
                    $end = min($paginator->lastPage(), $paginator->currentPage() + 2);
                @endphp
                @if ($start > 1)
                    <button class="cp-btn cp-btn-ghost cp-btn-sm" wire:click="gotoPage(1)">1</button>
                    @if ($start > 2)<span class="t-muted" style="padding:0 4px;">…</span>@endif
                @endif
                @for ($p = $start; $p <= $end; $p++)
                    <button class="cp-btn cp-btn-sm {{ $p == $paginator->currentPage() ? 'cp-btn-primary' : 'cp-btn-ghost' }}"
                            wire:click="gotoPage({{ $p }})">{{ $p }}</button>
                @endfor
                @if ($end < $paginator->lastPage())
                    @if ($end < $paginator->lastPage() - 1)<span class="t-muted" style="padding:0 4px;">…</span>@endif
                    <button class="cp-btn cp-btn-ghost cp-btn-sm" wire:click="gotoPage({{ $paginator->lastPage() }})">
                        {{ $paginator->lastPage() }}
                    </button>
                @endif
                <button class="cp-btn cp-btn-ghost cp-btn-sm" wire:click="nextPage"
                        @disabled(!$paginator->hasMorePages())>
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        @endif
    </div>
@endif
