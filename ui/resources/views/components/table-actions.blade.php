@props(['limit' => 2])

<div class="table-actions-wrapper d-flex align-items-center gap-1" data-limit="{{ $limit }}">
    {{ $slot }}
</div>

@once
@push('scripts')
<script>
    $(document).ready(function() {
        function processTableActions() {
            $('.table-actions-wrapper').each(function() {
                const $wrapper = $(this);
                // Prevent double processing
                if ($wrapper.hasClass('processed')) return;
                $wrapper.addClass('processed');

                const limit = parseInt($wrapper.data('limit')) || 3;
                const $actions = $wrapper.children().filter(function() {
                    // Only count visible static elements (not hidden by d-none or templates)
                    return $(this).is(':visible') || $(this).attr('id') === 'bulk-delete-btn';
                });
                
                if ($actions.length > limit) {
                    const $visible = $actions.slice(0, limit);
                    const $toDropdown = $actions.slice(limit);
                    
                    const $dropdown = $(`
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-sm btn-light border dropdown-toggle px-1" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="ph-dots-three-vertical"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end"></div>
                        </div>
                    `);
                    
                    const $menu = $dropdown.find('.dropdown-menu');
                    
                    $toDropdown.each(function() {
                        const $btn = $(this);
                        const title = $btn.attr('title') || $btn.attr('data-bs-original-title') || $btn.text().trim() || 'Action';
                        
                        // We move the element to preserve data attributes and listeners
                        const $item = $btn; 
                        
                        // Re-style as dropdown item
                        $item.removeClass('btn btn-sm btn-primary btn-info btn-success btn-danger btn-warning btn-outline-secondary')
                             .addClass('dropdown-item flex-fill')
                             .css({'display': 'block', 'width': '100%'});
                        
                        // Remove tooltip popup since it's in a menu
                        $item.removeAttr('data-bs-popup');
                        
                        // Add text if it only had an icon
                        if ($item.find('i').length && !$item.text().trim()) {
                            $item.find('i').addClass('me-2');
                            $item.append($('<span>').text(title));
                        }
                        
                        $menu.append($item);
                    });
                    
                    $wrapper.append($dropdown);
                }
            });
        }

        processTableActions();
        
        // Some pages might load data via AJAX or have dynamic transitions
        $(document).ajaxComplete(processTableActions);
    });
</script>
@endpush
@endonce
