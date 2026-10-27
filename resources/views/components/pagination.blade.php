<nav class="list-pagination" aria-label="Pagination">
    <p class="pagination-summary">
        @if ($paginator->count() > 0)
            Showing <strong>{{ number_format($paginator->firstItem()) }}–{{ number_format($paginator->lastItem()) }}</strong>
            of <strong>{{ number_format($paginator->total()) }}</strong>
        @elseif ($paginator->total() > 0)
            No results on this page
        @else
            No results
        @endif
    </p>
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $windowStart = max(1, $current - 1);
        $windowEnd = min($last, $current + 1);
    @endphp

    <div class="pagination">
        @if (! $paginator->onFirstPage())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-control">Previous</a>
        @else
            <span class="pagination-control is-disabled" aria-disabled="true">Previous</span>
        @endif
        <div class="pagination-pages">
        @if ($windowStart > 1)
            <a href="{{ $paginator->url(1) }}" class="pagination-control" aria-label="Page 1">1</a>
            @if ($windowStart > 2)
                <span class="rank-text">…</span>
            @endif
        @endif

        @for ($page = $windowStart; $page <= $windowEnd; $page++)
            @if ($page === $current)
                <span class="pagination-control active-page" aria-current="page" aria-label="Page {{ $page }}">{{ $page }}</span>
            @else
                <a href="{{ $paginator->url($page) }}" class="pagination-control" aria-label="Page {{ $page }}">{{ $page }}</a>
            @endif
        @endfor

        @if ($windowEnd < $last)
            @if ($windowEnd < $last - 1)
                <span class="rank-text">…</span>
            @endif
            <a href="{{ $paginator->url($last) }}" class="pagination-control" aria-label="Page {{ $last }}">{{ $last }}</a>
        @endif
        </div>
        <span class="pagination-position">{{ $current }} / {{ $last }}</span>
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-control">Next</a>
        @else
            <span class="pagination-control is-disabled" aria-disabled="true">Next</span>
        @endif
    </div>
@endif
</nav>
