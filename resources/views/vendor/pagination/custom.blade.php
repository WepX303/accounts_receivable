@if ($paginator->hasPages())
    <nav class="d-flex justify-content-between align-items-center" aria-label="@lang('Pagination Navigation')">

        {{-- Mobile (sm and down) --}}
        <ul class="pagination mb-0 d-flex d-sm-none">
            {{-- Previous --}}
            <li class="page-item @if($paginator->onFirstPage()) disabled @endif" @if($paginator->onFirstPage()) aria-disabled="true" @endif>
                @if ($paginator->onFirstPage())
                    <span class="page-link">@lang('pagination.previous')</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        @lang('pagination.previous')
                    </a>
                @endif
            </li>

            {{-- Next --}}
            <li class="page-item @if(!$paginator->hasMorePages()) disabled @endif" @if(!$paginator->hasMorePages()) aria-disabled="true" @endif>
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        @lang('pagination.next')
                    </a>
                @else
                    <span class="page-link">@lang('pagination.next')</span>
                @endif
            </li>
        </ul>

        {{-- Desktop (sm and up) --}}
        <ul class="pagination mb-0 d-none d-sm-flex ms-auto">

            {{-- Previous --}}
            <li class="page-item @if($paginator->onFirstPage()) disabled @endif"
                @if($paginator->onFirstPage()) aria-disabled="true" @endif
                aria-label="@lang('pagination.previous')">
                @if ($paginator->onFirstPage())
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                @else
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                        &lsaquo;
                    </a>
                @endif
            </li>

            {{-- Elements --}}
            @foreach ($elements as $element)
                {{-- Dots --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true">
                        <span class="page-link">{{ $element }}</span>
                    </li>
                @endif

                {{-- Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next --}}
            <li class="page-item @if(!$paginator->hasMorePages()) disabled @endif"
                @if(!$paginator->hasMorePages()) aria-disabled="true" @endif
                aria-label="@lang('pagination.next')">
                @if ($paginator->hasMorePages())
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                        &rsaquo;
                    </a>
                @else
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                @endif
            </li>
        </ul>

    </nav>
@endif
