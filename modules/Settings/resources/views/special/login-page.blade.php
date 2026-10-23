{{-- Settings → Login page: the brand panel beside the sign-in form, with a live preview of the real panel. --}}
@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_login_page.breadcrumb') }}</span>
@endsection

@section('content')
    @php
        $rows = array_values((array) old('highlights', $highlights));
        $mode = old('highlights_mode', $highlightsMode);
    @endphp

    <form action="{{ route('admin.settings.special.update_login_page') }}" method="POST" data-fd-login-settings>
        @csrf

    <x-page-header title="{{ __('settings::settings.special_login_page.title') }}"
        subtitle="{{ __('settings::settings.special_login_page.subtitle') }}"
        icon="ph ph-sign-in">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-6">
                <i class="ph ph-floppy-disk"></i>{{ __('settings::settings.special_login_page.save_changes') }}
            </button>
        </x-slot>
    </x-page-header>

    <div class="grid grid-cols-12 gap-x-6">
        <div class="col-span-12 xl:col-span-7">

            {{-- Text --}}
            <x-form-section :title="__('settings::settings.special_login_page.text_header')" icon="ph ph-text-aa">
                <div class="mb-4">
                    <x-form.input name="headline" id="headline" :label="__('settings::settings.special_login_page.headline_label')"
                        :value="$headline" :placeholder="$defaultHeadline" maxlength="120" data-preview="headline" />
                </div>
                <x-form.input name="subheadline" id="subheadline" :label="__('settings::settings.special_login_page.subheadline_label')"
                    :value="$subheadline" :placeholder="$defaultSubheadline" maxlength="200" data-preview="subheadline"
                    :help="__('settings::settings.special_login_page.text_help')" />
            </x-form-section>

            {{-- Feature bullets --}}
            <x-form-section :title="__('settings::settings.special_login_page.highlights_header')" icon="ph ph-list-checks">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="highlights_mode" id="highlights_default" value="default"
                        data-highlights-mode @checked($mode === 'default')>
                    <label class="form-check-label" for="highlights_default">{{ __('settings::settings.special_login_page.highlights_default') }}</label>
                </div>
                <div class="form-check mb-4">
                    <input class="form-check-input" type="radio" name="highlights_mode" id="highlights_custom" value="custom"
                        data-highlights-mode @checked($mode === 'custom')>
                    <label class="form-check-label" for="highlights_custom">{{ __('settings::settings.special_login_page.highlights_custom') }}</label>
                </div>

                <div data-highlights-editor @if ($mode !== 'custom') hidden @endif>
                    <div class="fd-highlight-rows" data-highlight-rows data-max="{{ $maxHighlights }}">
                        @foreach ($rows as $i => $row)
                            <div class="fd-highlight-row" data-highlight-row>
                                <span class="fd-icon-tile" aria-hidden="true"><i class="{{ $row['icon'] ?? 'ph ph-check' }}" data-icon-preview></i></span>
                                <div class="fd-highlight-icon">
                                    <x-form.input name="highlights[{{ $i }}][icon]" :value="$row['icon'] ?? ''" placeholder="ph ph-check"
                                        data-field="icon" :aria-label="__('settings::settings.special_login_page.icon_label')" />
                                </div>
                                <div class="fd-highlight-text">
                                    <x-form.input name="highlights[{{ $i }}][text]" :value="$row['text'] ?? ''" maxlength="120"
                                        data-field="text" :aria-label="__('settings::settings.special_login_page.text_label')" />
                                </div>
                                <button type="button" class="btn btn-ghost btn-icon" data-remove-highlight
                                    aria-label="{{ __('settings::settings.special_login_page.remove_bullet') }}"><i class="ph ph-trash"></i></button>
                            </div>
                        @endforeach
                    </div>
                    @error('highlights')
                        <div class="invalid-feedback block mb-2">{{ $message }}</div>
                    @enderror

                    <button type="button" class="btn btn-light btn-sm" data-add-highlight>
                        <i class="ph ph-plus"></i>{{ __('settings::settings.special_login_page.add_bullet') }}
                    </button>
                    <div class="form-text mt-2">{{ __('settings::settings.special_login_page.highlights_help', ['max' => $maxHighlights]) }}</div>
                    <div class="form-text">{{ __('settings::settings.special_login_page.icon_help') }}</div>

                    <template data-highlight-template>
                        <div class="fd-highlight-row" data-highlight-row>
                            <span class="fd-icon-tile" aria-hidden="true"><i class="ph ph-check" data-icon-preview></i></span>
                            <div class="fd-highlight-icon">
                                <input type="text" class="form-control form-control-sm" name="" value="ph ph-check" placeholder="ph ph-check"
                                    data-field="icon" aria-label="{{ __('settings::settings.special_login_page.icon_label') }}">
                            </div>
                            <div class="fd-highlight-text">
                                <input type="text" class="form-control form-control-sm" name="" value="" maxlength="120"
                                    data-field="text" aria-label="{{ __('settings::settings.special_login_page.text_label') }}">
                            </div>
                            <button type="button" class="btn btn-ghost btn-icon" data-remove-highlight
                                aria-label="{{ __('settings::settings.special_login_page.remove_bullet') }}"><i class="ph ph-trash"></i></button>
                        </div>
                    </template>
                </div>
            </x-form-section>

            {{-- Brand and graphic --}}
            <x-form-section :title="__('settings::settings.special_login_page.look_header')" icon="ph ph-paint-brush">
                <div class="grid grid-cols-12 gap-4 mb-4">
                    <div class="col-span-12 md:col-span-6">
                        <x-form.select name="mark" id="mark" :label="__('settings::settings.special_login_page.mark_label')" :selected="$mark" :options="[
                            'auto' => __('settings::settings.special_login_page.mark_auto'),
                            'monogram' => __('settings::settings.special_login_page.mark_monogram'),
                            'none' => __('settings::settings.special_login_page.mark_none'),
                        ]" />
                    </div>
                    <div class="col-span-12 md:col-span-6">
                        <x-form.select name="graphic" id="graphic" :label="__('settings::settings.special_login_page.graphic_label')" :selected="$graphic"
                            data-preview="graphic" :options="[
                            'bars' => __('settings::settings.special_login_page.graphic_bars'),
                            'glow' => __('settings::settings.special_login_page.graphic_glow'),
                            'none' => __('settings::settings.special_login_page.graphic_none'),
                        ]" />
                    </div>
                </div>

                @include('settings::special.partials.switch', [
                    'name' => 'show_name',
                    'checked' => $showName,
                    'label' => __('settings::settings.special_login_page.show_name_label'),
                    'help' => __('settings::settings.special_login_page.look_help'),
                ])
            </x-form-section>
        </div>

        {{-- Preview: the real panel partial, as it stands now --}}
        <div class="col-span-12 xl:col-span-5">
            <div class="fd-login-preview-sticky">
                <x-form-section :title="__('settings::settings.special_login_page.preview_header')" icon="ph ph-eye">
                    <div class="fd-login-preview" data-login-preview
                        data-default-headline="{{ $defaultHeadline }}" data-default-subheadline="{{ $defaultSubheadline }}">
                        @include('layouts.partials.auth-aside')
                    </div>
                    <div class="form-text mt-2">{{ __('settings::settings.special_login_page.preview_help') }}</div>
                </x-form-section>
            </div>
        </div>
    </div>

    </form>

    @push('scripts')
    <script>
        (function () {
            const form = document.querySelector('[data-fd-login-settings]');
            if (!form) return;

            const editor = form.querySelector('[data-highlights-editor]');
            const list = form.querySelector('[data-highlight-rows]');
            const template = form.querySelector('[data-highlight-template]');
            const addButton = form.querySelector('[data-add-highlight]');
            const preview = form.querySelector('[data-login-preview]');
            const aside = preview.querySelector('.fd-auth-aside');
            const textBlock = aside.querySelector('.fd-auth-quote').parentElement;
            const appName = @json(mailAppName());
            const defaultBullets = @json($defaultHighlights);
            const max = parseInt(list.dataset.max, 10);

            const withApp = (text) => text.replaceAll(':app', appName);
            const rows = () => Array.from(list.querySelectorAll('[data-highlight-row]'));
            const mode = () => form.querySelector('[data-highlights-mode]:checked')?.value;

            // Rows are named highlights[0][icon], highlights[0][text], … in order, after every change
            function renumber() {
                rows().forEach((row, i) => {
                    row.querySelectorAll('[data-field]').forEach((input) => {
                        input.name = `highlights[${i}][${input.dataset.field}]`;
                        input.id = `highlights${i}${input.dataset.field}`;
                    });
                });
                addButton.disabled = rows().length >= max;
            }

            // Draws the bullets in the preview with textContent only: what an admin types is never parsed as HTML
            function drawBullets() {
                const items = mode() === 'custom'
                    ? rows().map((row) => ({
                        icon: row.querySelector('[data-field="icon"]').value.trim() || 'ph ph-check',
                        text: row.querySelector('[data-field="text"]').value.trim(),
                    })).filter((item) => item.text !== '')
                    : defaultBullets;

                textBlock.querySelector('.fd-auth-points')?.remove();
                if (!items.length) return;

                const ul = document.createElement('ul');
                ul.className = 'fd-auth-points';
                items.forEach((item) => {
                    const li = document.createElement('li');
                    const chip = document.createElement('span');
                    chip.className = 'fd-auth-point-icon';
                    chip.setAttribute('aria-hidden', 'true');
                    const icon = document.createElement('i');
                    icon.className = item.icon;
                    chip.appendChild(icon);
                    const label = document.createElement('span');
                    label.textContent = withApp(item.text);
                    li.append(chip, label);
                    ul.appendChild(li);
                });
                textBlock.appendChild(ul);
            }

            form.querySelectorAll('[data-highlights-mode]').forEach((radio) => radio.addEventListener('change', () => {
                editor.hidden = mode() !== 'custom';
                drawBullets();
            }));

            addButton.addEventListener('click', () => {
                if (rows().length >= max) return;
                list.appendChild(template.content.cloneNode(true));
                renumber();
                rows().at(-1).querySelector('[data-field="text"]').focus();
                drawBullets();
            });

            list.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-highlight]');
                if (!remove) return;
                remove.closest('[data-highlight-row]').remove();
                renumber();
                drawBullets();
            });

            list.addEventListener('input', (event) => {
                if (event.target.dataset.field === 'icon') {
                    const value = event.target.value.trim();
                    event.target.closest('[data-highlight-row]').querySelector('[data-icon-preview]').className = value || 'ph ph-check';
                }
                drawBullets();
            });

            form.querySelector('[data-preview="headline"]').addEventListener('input', (event) => {
                aside.querySelector('.fd-auth-quote').textContent = withApp(event.target.value.trim() || preview.dataset.defaultHeadline);
            });
            form.querySelector('[data-preview="subheadline"]').addEventListener('input', (event) => {
                aside.querySelector('.fd-auth-quote-meta').textContent = withApp(event.target.value.trim() || preview.dataset.defaultSubheadline);
            });
            form.querySelector('[data-preview="graphic"]').addEventListener('change', (event) => {
                aside.dataset.graphic = event.target.value;
            });

            renumber();
        })();
    </script>
    @endpush
@endsection
