<div class="card h-full">
    <div class="card-header">
        <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph ph-lightning"></i></span>
        <h2 class="card-title">{{ __('foundation::foundation.dashboard.widget_quick_actions') }}</h2>
    </div>
    <div class="card-body">
        <ul class="fd-actions">
            @foreach ($actions as $action)
                <li>
                    <a href="{{ $action['href'] }}" class="fd-action">
                        <span class="fd-action-icon"><i class="{{ $action['icon'] }}"></i></span>
                        <span class="fd-action-label">{{ $action['label'] }}</span>
                        <i class="ph ph-caret-right fd-action-go" aria-hidden="true"></i>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
