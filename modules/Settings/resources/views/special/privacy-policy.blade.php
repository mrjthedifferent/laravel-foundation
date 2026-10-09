@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_privacy_policy.breadcrumb') }}</span>
@endsection

@section('content')
<x-page-header title="{{ __('settings::settings.special_privacy_policy.title') }}" subtitle="{{ __('settings::settings.special_privacy_policy.subtitle') }}" icon="ph ph-file-text">
    @if (Route::has('legal.privacy_policy'))
        <x-slot name="actions">
            <a href="{{ route('legal.privacy_policy') }}" target="_blank" rel="noopener" class="btn btn-light">
                <i class="ph ph-arrow-square-out"></i>{{ __('settings::settings.legal.public_page') }}
            </a>
        </x-slot>
    @endif
</x-page-header>

<div class="card">
    <div class="card-body p-6">
        <form action="{{ route('admin.settings.special.update_privacy_policy') }}" method="POST">
            @csrf
            <div class="mb-6">
                <x-form.textarea name="privacy_policy" id="privacy_policy" label="{{ __('settings::settings.special_privacy_policy.content_label') }}" required :value="$setting->value ?? ''" :rows="20" />
            </div>
            <div class="flex justify-end">
                <button type="submit" class="btn btn-primary px-6">
                    <i class="ph ph-floppy-disk"></i>{{ __('settings::settings.special_privacy_policy.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    <link href="{{ foundation_asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet" type="text/css">
@endpush

@push('scripts')
    <script src="{{ foundation_asset('assets/vendor/quill/quill.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var field = document.querySelector('#privacy_policy');
            var holder = document.createElement('div');
            holder.style.minHeight = '20rem';
            field.insertAdjacentElement('afterend', holder);
            field.classList.add('hidden');

            var quill = new Quill(holder, {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ header: [1, 2, 3, 4, 5, 6, false] }],
                        ['bold', 'italic', 'underline', 'strike'],
                        [{ list: 'ordered' }, { list: 'bullet' }, { indent: '-1' }, { indent: '+1' }],
                        ['link', 'blockquote'],
                        ['clean'],
                    ],
                },
            });

            quill.clipboard.dangerouslyPasteHTML(field.value || '');
            quill.on('text-change', function () {
                field.value = quill.getLength() > 1 ? quill.root.innerHTML : '';
            });
        });
    </script>
@endpush
