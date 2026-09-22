@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_terms_conditions.breadcrumb') }}</span>
@endsection

@section('content')
<div class="card">
    <div class="card-header d-flex align-items-center gap-2 py-2">
        <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0" style="width:32px;height:32px;">
            <i class="ph-scroll"></i>
        </div>
        <div>
            <div class="fw-bold">{{ __('settings::settings.special_terms_conditions.title') }}</div>
            <div class="text-muted fs-xs">{{ __('settings::settings.special_terms_conditions.subtitle') }}</div>
        </div>
    </div>
    <div class="card-body p-4">
        <form action="{{ route('admin.settings.special.update_terms_conditions') }}" method="POST">
            @csrf
            <div class="mb-4">
                <x-form.textarea name="terms_conditions" id="terms_conditions" label="{{ __('settings::settings.special_terms_conditions.content_label') }}" required :value="$setting->value ?? ''" :rows="20" />
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="ph-floppy-disk me-1"></i>{{ __('settings::settings.special_terms_conditions.submit') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
    <link href="{{ asset('assets/vendor/quill/quill.snow.css') }}" rel="stylesheet" type="text/css">
@endpush

@push('scripts')
    <script src="{{ asset('assets/vendor/quill/quill.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var field = document.querySelector('#terms_conditions');
            var holder = document.createElement('div');
            holder.style.minHeight = '20rem';
            field.insertAdjacentElement('afterend', holder);
            field.classList.add('d-none');

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
