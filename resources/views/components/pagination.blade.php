@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $windowStart = max(1, $current - 2);
        $windowEnd = min($last, $current + 2);
    @endphp

    <div class="pagination">
        @if (! $paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-secondary small">Previous</a>
        @endif

        @if ($windowStart > 1)
            <a href="{{ $paginator->url(1) }}" class="btn btn-secondary small {{ $current === 1 ? 'active-page' : '' }}">1</a>
            @if ($windowStart > 2)
                <span class="rank-text">…</span>
            @endif
        @endif

        @for ($page = $windowStart; $page <= $windowEnd; $page++)
            <a href="{{ $paginator->url($page) }}"
               class="btn btn-secondary small {{ $page === $current ? 'active-page' : '' }}">{{ $page }}</a>
        @endfor

        @if ($windowEnd < $last)
            @if ($windowEnd < $last - 1)
                <span class="rank-text">…</span>
            @endif
            <a href="{{ $paginator->url($last) }}" class="btn btn-secondary small {{ $current === $last ? 'active-page' : '' }}">{{ $last }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-secondary small">Next</a>
        @endif
    </div>
@endif
