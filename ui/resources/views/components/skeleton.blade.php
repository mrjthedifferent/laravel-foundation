@props([
    'rows' => 3,          {{-- how many placeholder rows --}}
    'avatar' => true,     {{-- a round placeholder before each row --}}
])

{{-- A shimmering stand-in for a list that is still loading. Purely visual: the status line is
     what a screen reader gets. --}}
<div class="fd-skeleton-list" role="status" aria-live="polite">
    <span class="sr-only">{{ __('foundation::foundation.notification.loading') }}</span>
    @for ($i = 0; $i < (int) $rows; $i++)
        <div class="fd-skeleton-row" aria-hidden="true">
            @if ($avatar)
                <span class="fd-skeleton fd-skeleton-avatar"></span>
            @endif
            <div class="fd-skeleton-lines">
                <span class="fd-skeleton fd-skeleton-line" style="width: {{ [72, 58, 84, 66][$i % 4] }}%"></span>
                <span class="fd-skeleton fd-skeleton-line is-short"></span>
            </div>
        </div>
    @endfor
</div>
