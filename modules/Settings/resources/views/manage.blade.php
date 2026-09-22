@extends('settings::layouts.master')

@section('breadcrumb')
<span class="breadcrumb-item active">{{ __('settings::settings.manage.breadcrumb_active') }}</span>
@endsection

@section('content')

{{-- Import errors --}}
@if(session('import_errors'))
<x-alert type="warning" icon="ph-warning">
    <strong>{{ __('settings::settings.manage.import_errors_title') }}</strong>
    <ul class="mb-0 mt-1 ps-3 fs-sm">
        @foreach(session('import_errors') as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</x-alert>
@endif

{{-- Table card --}}
<div class="card">
    <div class="card-header">
        <h6 class="card-title">{{ __('settings::settings.manage.title') }}</h6>
        <div class="d-flex flex-wrap gap-2 ms-auto">
            <a href="{{ route('admin.settings.sync') }}" class="btn btn-light swal-post"
                data-text="{{ __('settings::settings.manage.sync_confirm') }}">
                <i class="ph-eject"></i>{{ __('settings::settings.manage.sync') }}
            </a>
            <a href="{{ route('admin.settings.export') }}" class="btn btn-light">
                <i class="ph-download"></i>{{ __('foundation::foundation.common.export') }}
            </a>
            <a href="{{ route('admin.settings.import_form') }}" class="btn btn-light">
                <i class="ph-upload"></i>{{ __('settings::settings.manage.import') }}
            </a>
            <a href="{{ route('admin.settings.create') }}" class="btn btn-primary">
                <i class="ph-plus"></i>{{ __('settings::settings.manage.new_setting') }}
            </a>
        </div>
    </div>

    {{-- Filters --}}
    <div class="fd-toolbar">
        <div class="fd-toolbar-search">
            <i class="ph-magnifying-glass"></i>
            <input type="text" id="settings-search" class="form-control"
                aria-label="{{ __('settings::settings.manage.search_label') }}"
                placeholder="{{ __('settings::settings.manage.search_placeholder') }}">
        </div>
        <select id="group-filter" class="form-select" aria-label="{{ __('settings::settings.manage.group_label') }}">
            <option value="">{{ __('settings::settings.manage.all_groups') }}</option>
            @foreach($settings->pluck('group')->unique()->sort()->values() as $group)
            <option value="{{ $group }}">{{ display_label($group) }}</option>
            @endforeach
        </select>
        <select id="type-filter" class="form-select" aria-label="{{ __('settings::settings.manage.type_label') }}">
            <option value="">{{ __('settings::settings.manage.all_types') }}</option>
            @foreach($settings->pluck('type')->unique()->sort()->values() as $type)
            <option value="{{ $type }}">{{ ucfirst($type) }}</option>
            @endforeach
        </select>
        <button type="button" id="clear-search" class="btn btn-ghost btn-icon" title="{{ __('settings::settings.manage.clear') }}">
            <i class="ph-x"></i>
        </button>
        <button type="button" id="reset-filters-btn" class="btn btn-light ms-auto">
            <i class="ph-arrow-counter-clockwise"></i>{{ __('foundation::foundation.common.reset') }}
        </button>
    </div>

    {{-- Bulk action bar --}}
    <div id="bulk-bar" class="fd-toolbar d-none">
        <span class="fw-medium fs-sm">
            <i class="ph-check-square"></i> <span id="selected-count">0</span> {{ __('settings::settings.manage.selected') }}
        </span>
        <select id="bulk-action" class="form-select" aria-label="{{ __('settings::settings.manage.choose_action') }}">
            <option value="">{{ __('settings::settings.manage.choose_action') }}</option>
            <option value="visibility">{{ __('settings::settings.manage.action_visibility') }}</option>
            <option value="group">{{ __('settings::settings.manage.action_group') }}</option>
            <option value="delete">{{ __('settings::settings.manage.action_delete') }}</option>
        </select>
        <select id="bulk-visibility" class="form-select d-none" aria-label="{{ __('settings::settings.manage.action_visibility') }}">
            <option value="1">{{ __('settings::settings.manage.make_visible') }}</option>
            <option value="0">{{ __('settings::settings.manage.make_hidden') }}</option>
        </select>
        <select id="bulk-group" class="form-select d-none" aria-label="{{ __('settings::settings.manage.action_group') }}">
            @foreach($settings->pluck('group')->unique()->sort()->values() as $group)
            <option value="{{ $group }}">{{ display_label($group) }}</option>
            @endforeach
            <option value="new">{{ __('settings::settings.manage.new_group_option') }}</option>
        </select>
        <input type="text" id="bulk-new-group" class="form-control d-none"
            aria-label="{{ __('settings::settings.manage.new_group_placeholder') }}"
            placeholder="{{ __('settings::settings.manage.new_group_placeholder') }}">
        <button type="button" id="apply-bulk-action" class="btn btn-primary btn-sm">{{ __('settings::settings.manage.apply') }}</button>
        <button type="button" id="deselect-all" class="btn btn-ghost btn-sm ms-auto">
            <i class="ph-x"></i>{{ __('settings::settings.manage.clear') }}
        </button>
    </div>

    {{-- Select-all row --}}
    <div id="select-bar" class="fd-toolbar">
        <button type="button" id="select-all" class="btn btn-light btn-sm">
            <i class="ph-check-square"></i>{{ __('settings::settings.manage.select_all_visible') }}
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive custom-scrollbar">
        <table class="table table-hover table-nowrap align-middle mb-0" id="settings-table">
            <thead>
                <tr>
                    <th><input class="form-check-input" type="checkbox" id="check-all" aria-label="{{ __('settings::settings.manage.select_all_visible') }}"></th>
                    <th>{{ __('settings::settings.manage.col_key') }}</th>
                    <th>{{ __('settings::settings.manage.col_group') }}</th>
                    <th>{{ __('settings::settings.manage.col_type') }}</th>
                    <th>{{ __('settings::settings.manage.col_value') }}</th>
                    <th>{{ __('foundation::foundation.common.description') }}</th>
                    <th class="text-center">{{ __('settings::settings.manage.col_visible') }}</th>
                    <th class="text-center">{{ __('settings::settings.manage.col_required') }}</th>
                    <th class="text-end">{{ __('foundation::foundation.common.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @php
                $typeColors = [
                'text' => 'secondary', 'textarea' => 'secondary',
                'integer' => 'info', 'float' => 'info',
                'boolean' => 'success',
                'select' => 'primary', 'multi-select' => 'primary',
                'image' => 'warning', 'file' => 'warning',
                'json' => 'danger', 'array' => 'danger',
                ];
                @endphp
                @forelse($settings as $setting)
                @php $c = $typeColors[$setting->type] ?? 'secondary'; @endphp
                <tr class="setting-row"
                    data-key="{{ $setting->key }}"
                    data-group="{{ $setting->group }}"
                    data-type="{{ $setting->type }}"
                    data-desc="{{ $setting->description }}"
                    data-id="{{ $setting->id }}">
                    <td><input class="form-check-input setting-checkbox" type="checkbox" value="{{ $setting->id }}"></td>
                    <td>
                        <code class="text-body fs-sm">{{ $setting->key }}</code>
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ display_label($setting->group) }}</span>
                    </td>
                    <td>
                        <span class="badge bg-{{ $c }} text-uppercase">{{ $setting->type }}</span>
                    </td>
                    <td>
                        @if($setting->type === 'image' && $setting->value)
                        <img src="{{ $setting->value }}" class="rounded h-24px" alt="{{ $setting->key }}">
                        @elseif($setting->type === 'file' && $setting->value)
                        <a href="{{ $setting->value }}" target="_blank" class="fs-sm">
                            <i class="ph-file"></i> {{ __('settings::settings.index.view_current_file') }}
                        </a>
                        @elseif($setting->type === 'boolean')
                        @if($setting->value)
                        <span class="fd-status is-success">{{ __('settings::settings.manage.enabled') }}</span>
                        @else
                        <span class="fd-status is-danger">{{ __('settings::settings.manage.disabled') }}</span>
                        @endif
                        @elseif(in_array($setting->type, ['json','array']))
                        <span class="badge bg-{{ $c }} text-uppercase">{{ strtoupper($setting->type) }}</span>
                        @else
                        <span class="text-muted fs-sm">{{ Str::limit(is_array($setting->value) ? implode(', ', $setting->value) : $setting->value, 35) }}</span>
                        @endif
                    </td>
                    <td>
                        <span class="text-muted fs-sm">{{ Str::limit(display_label($setting->description), 40) }}</span>
                    </td>
                    <td class="text-center">
                        @if($setting->is_visible)
                        <i class="ph-eye text-success" title="{{ __('settings::settings.manage.visible_tooltip') }}"></i>
                        @else
                        <i class="ph-eye-slash text-muted" title="{{ __('settings::settings.manage.hidden_tooltip') }}"></i>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($setting->required)
                        <i class="ph-asterisk text-warning" title="{{ __('settings::settings.manage.required_tooltip') }}"></i>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-end">
                        <x-dropdown-menu>
                            <x-dropdown-link :url="route('admin.settings.edit', $setting)">
                                <i class="ph-pencil-simple"></i>{{ __('foundation::foundation.common.edit') }}
                            </x-dropdown-link>
                            <div class="dropdown-divider"></div>
                            <button type="button" class="dropdown-item text-danger swal-delete"
                                data-url="{{ route('admin.settings.destroy', $setting) }}"
                                data-text="{{ __('settings::settings.manage.delete_confirm', ['key' => $setting->key]) }}">
                                <i class="ph-trash"></i>{{ __('foundation::foundation.common.delete') }}
                            </button>
                        </x-dropdown-menu>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4 text-muted">
                        {{ __('settings::settings.manage.no_settings_found') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="no-results" class="fd-empty d-none">
        <span class="fd-empty-icon"><i class="ph-magnifying-glass"></i></span>
        <div class="fd-empty-title">{{ __('settings::settings.manage.no_results') }}</div>
    </div>

    <div class="fd-table-foot">
        <span>
            {{ __('settings::settings.manage.showing_pre') }}
            <span id="visible-count">{{ $settings->count() }}</span>
            {{ __('settings::settings.manage.showing_of') }} {{ $settings->count() }}
        </span>
    </div>

    {{-- Hidden bulk forms --}}
    <form id="bulk-visibility-form" action="{{ route('admin.settings.bulk_update') }}" method="POST" class="d-none">
        @csrf @method('PUT')
        <input type="hidden" name="action" value="visibility">
        <input type="hidden" name="visibility" id="visibility-value">
        <div id="visibility-ids-container"></div>
    </form>
    <form id="bulk-group-form" action="{{ route('admin.settings.bulk_update') }}" method="POST" class="d-none">
        @csrf @method('PUT')
        <input type="hidden" name="action" value="group">
        <input type="hidden" name="group" id="group-value">
        <div id="group-ids-container"></div>
    </form>
    <form id="bulk-delete-form" action="{{ route('admin.settings.bulk_delete') }}" method="POST" class="d-none">
        @csrf @method('DELETE')
        <div id="delete-ids-container"></div>
    </form>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var searchInput = document.getElementById('settings-search');
        var groupFilter = document.getElementById('group-filter');
        var typeFilter = document.getElementById('type-filter');
        var visibleCountEl = document.getElementById('visible-count');
        var settingRows = document.querySelectorAll('.setting-row');
        var noResults = document.getElementById('no-results');
        var table = document.getElementById('settings-table');
        var checkAll = document.getElementById('check-all');
        var bulkBar = document.getElementById('bulk-bar');
        var selectBar = document.getElementById('select-bar');
        var selectedCountEl = document.getElementById('selected-count');
        var bulkAction = document.getElementById('bulk-action');
        var bulkVisibility = document.getElementById('bulk-visibility');
        var bulkGroup = document.getElementById('bulk-group');
        var bulkNewGroup = document.getElementById('bulk-new-group');

        function filter() {
            var term = searchInput.value.toLowerCase();
            var group = groupFilter.value;
            var type = typeFilter.value;
            var count = 0;
            settingRows.forEach(function(row) {
                var ok = (row.dataset.key.includes(term) || (row.dataset.desc || '').toLowerCase().includes(term)) &&
                    (!group || row.dataset.group === group) &&
                    (!type || row.dataset.type === type);
                row.style.display = ok ? '' : 'none';
                if (ok) count++;
            });
            visibleCountEl.textContent = count;
            table.closest('.table-responsive').style.display = count ? '' : 'none';
            noResults.classList.toggle('d-none', count > 0);
            syncCheckAll();
        }

        searchInput.addEventListener('input', filter);
        groupFilter.addEventListener('change', filter);
        typeFilter.addEventListener('change', filter);
        document.getElementById('clear-search').addEventListener('click', function() {
            searchInput.value = '';
            filter();
        });
        document.getElementById('reset-filters-btn').addEventListener('click', function() {
            searchInput.value = '';
            groupFilter.value = '';
            typeFilter.value = '';
            filter();
        });

        // Wire up swal-confirm buttons that submit a named form — handled globally by _form_submit.blade.php

        function visibleRows() {
            return Array.from(settingRows).filter(r => r.style.display !== 'none');
        }

        function checkedRows() {
            return visibleRows().filter(r => r.querySelector('.setting-checkbox').checked);
        }

        function syncCheckAll() {
            var vr = visibleRows(),
                cr = checkedRows();
            checkAll.checked = vr.length && cr.length === vr.length;
            checkAll.indeterminate = cr.length > 0 && cr.length < vr.length;
            selectedCountEl.textContent = cr.length;
            if (cr.length > 0) {
                bulkBar.classList.remove('d-none');
                bulkBar.classList.add('d-flex');
                selectBar.style.display = 'none';
            } else {
                bulkBar.classList.add('d-none');
                bulkBar.classList.remove('d-flex');
                selectBar.style.display = '';
            }
        }

        checkAll.addEventListener('change', function() {
            visibleRows().forEach(r => r.querySelector('.setting-checkbox').checked = checkAll.checked);
            syncCheckAll();
        });
        document.querySelectorAll('.setting-checkbox').forEach(cb => cb.addEventListener('change', syncCheckAll));
        document.getElementById('select-all').addEventListener('click', function() {
            visibleRows().forEach(r => r.querySelector('.setting-checkbox').checked = true);
            syncCheckAll();
        });
        document.getElementById('deselect-all').addEventListener('click', function() {
            document.querySelectorAll('.setting-checkbox').forEach(cb => cb.checked = false);
            syncCheckAll();
        });

        bulkAction.addEventListener('change', function() {
            bulkVisibility.classList.toggle('d-none', this.value !== 'visibility');
            bulkGroup.classList.toggle('d-none', this.value !== 'group');
            bulkNewGroup.classList.add('d-none');
        });
        bulkGroup.addEventListener('change', function() {
            bulkNewGroup.classList.toggle('d-none', this.value !== 'new');
        });

        document.getElementById('apply-bulk-action').addEventListener('click', function() {
            var action = bulkAction.value;
            if (!action) {
                window.toast('warning', '{{ __('settings::settings.manage.toast_choose_action') }}', '');
                return;
            }
            var ids = checkedRows().map(r => r.querySelector('.setting-checkbox').value);
            if (!ids.length) {
                window.toast('warning', '{{ __('settings::settings.manage.toast_select_one') }}', '');
                return;
            }

            function fill(cid) {
                var c = document.getElementById(cid);
                c.innerHTML = '';
                ids.forEach(function(id) {
                    var i = document.createElement('input');
                    i.type = 'hidden';
                    i.name = 'ids[]';
                    i.value = id;
                    c.appendChild(i);
                });
            }

            if (action === 'visibility') {
                window.showConfirm({
                    title: '{{ __('settings::settings.manage.confirm_title') }}',
                    text: '{{ __('settings::settings.manage.confirm_visibility') }}'.replace('{count}', ids.length),
                    icon: 'question',
                    confirmText: '{{ __('settings::settings.manage.confirm_yes') }}',
                    onConfirm: function() {
                        document.getElementById('visibility-value').value = bulkVisibility.value;
                        fill('visibility-ids-container');
                        document.getElementById('bulk-visibility-form').submit();
                    }
                });
            } else if (action === 'group') {
                var grp = bulkGroup.value === 'new' ? bulkNewGroup.value.trim() : bulkGroup.value;
                if (!grp) {
                    window.toast('warning', '{{ __('settings::settings.manage.toast_enter_group') }}', '');
                    return;
                }
                window.showConfirm({
                    title: '{{ __('settings::settings.manage.confirm_title') }}',
                    text: '{{ __('settings::settings.manage.confirm_move_group') }}'.replace('{count}', ids.length).replace('{group}', grp),
                    icon: 'question',
                    confirmText: '{{ __('settings::settings.manage.confirm_yes') }}',
                    onConfirm: function() {
                        document.getElementById('group-value').value = grp;
                        fill('group-ids-container');
                        document.getElementById('bulk-group-form').submit();
                    }
                });
            } else if (action === 'delete') {
                window.showConfirm({
                    title: '{{ __('settings::settings.manage.confirm_delete_title') }}'.replace('{count}', ids.length),
                    text: '{{ __('settings::settings.manage.confirm_delete_text') }}',
                    icon: 'warning',
                    confirmText: '{{ __('foundation::foundation.common.delete') }}',
                    confirmClass: 'btn btn-danger',
                    onConfirm: function() {
                        fill('delete-ids-container');
                        document.getElementById('bulk-delete-form').submit();
                    }
                });
            }
        });
    });
</script>
@endpush