{{--
    The package's own paginator: Bootstrap 5 markup without the "Showing x to y
    of z results" summary, which <x-table-view-pagination> already renders beside
    it. Owning this view also means Laravel renaming its built-in pagination
    views cannot change how a project's tables look.

    Override it by creating resources/views/pagination/links.blade.php.
--}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="pagination mb-0">
            {{-- Previous --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">&lsaquo;</a>
                </li>
            @endif

            {{-- Numbers (hidden on the narrowest screens, where prev/next is enough) --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled d-none d-sm-block" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active d-none d-sm-block" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item d-none d-sm-block"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
