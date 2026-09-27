@props(['paginator'])

@php
    $currentPage = $paginator->currentPage();
    $lastPage = max(1, $paginator->lastPage());
    $firstItem = $paginator->firstItem() ?: 0;
    $lastItem = $paginator->lastItem() ?: 0;
@endphp

<nav aria-label="Pagination">
    <div class="d-flex justify-content-between align-items-center flex-wrap">
        <small class="text-muted mb-2 mb-sm-0">
            Showing {{ $firstItem }} to {{ $lastItem }} of {{ $paginator->total() }} records
        </small>
        <ul class="pagination mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $paginator->previousPageUrl() ?: '#' }}" aria-label="Previous">Previous</a>
            </li>
            <li class="page-item active" aria-current="page">
                <span class="page-link">Page {{ $currentPage }} of {{ $lastPage }}</span>
            </li>
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                <a class="page-link" href="{{ $paginator->nextPageUrl() ?: '#' }}" aria-label="Next">Next</a>
            </li>
        </ul>
    </div>
</nav>