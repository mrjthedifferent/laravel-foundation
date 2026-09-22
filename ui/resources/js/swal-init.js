/**
 * Toast, modal, swal-confirm/delete/post, formPost, and flash messages.
 * Requires: jQuery, SweetAlert (loaded in layout before this).
 */
const LOADING_HTML = '<i class="ph-spinner spinner"></i>';

function initSwalHelpers() {
    if (typeof window.Swal === 'undefined') return;
    // The toast's look comes from .fd-toast-* in foundation.css, not from inline styles.
    const toastIcons = {
        success: 'ph-check-circle',
        error: 'ph-x-circle',
        warning: 'ph-warning-circle',
        info: 'ph-info',
    };

    window.toast = (icon, title, text) => {
        const tone = toastIcons[icon] ? icon : 'info';

        window.Swal.fire({
            timer: 8000,
            showConfirmButton: false,
            position: 'top-end',
            toast: true,
            buttonsStyling: false,
            customClass: { popup: `fd-toast toast-${tone}` },
            html: `<i class="${toastIcons[tone]} fd-toast-icon is-${tone}"></i>
                <span class="min-width-0">
                    <span class="fd-toast-title">${escapeHtml(title || '')}</span>
                    ${text ? `<span class="fd-toast-text d-block">${escapeHtml(text)}</span>` : ''}
                </span>`,
        });
    };

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    window.showConfirm = showConfirm;
}

function showConfirm(opts) {
    const {
        title = 'Are you sure?',
        text = '',
        icon = 'warning',
        confirmText = 'Yes',
        confirmClass = 'btn btn-primary',
        cancelText = 'Cancel',
        showCancelButton = true,
        url,
        onConfirm,
    } = opts;
    return window.Swal.fire({
        title,
        text,
        icon,
        showCancelButton,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        customClass: { confirmButton: confirmClass, cancelButton: 'btn btn-secondary' },
        buttonsStyling: false,
    }).then((result) => {
        if (result.isConfirmed && onConfirm) onConfirm(url);
        return result;
    });
}

function submitFormPost($, url, method, csrfToken) {
    const form = $('<form>', { method: 'POST', action: url });
    form.append($('<input>', { type: 'hidden', name: '_token', value: csrfToken }));
    if (method !== 'POST') {
        form.append($('<input>', { type: 'hidden', name: '_method', value: method }));
    }
    $('body').append(form);
    form.submit();
}

function initSwalConfirm() {
    const $ = window.jQuery || window.$;
    if (!$ || typeof window.Swal === 'undefined') return;
    const csrfToken = $('meta[name="csrf-token"]').attr('content');

    $(document).on('click', '.swal-confirm', function (e) {
        e.preventDefault();
        const $el = $(this);
        const url = $el.data('url') || $el.attr('href');
        showConfirm({
            url,
            text: $el.data('text'),
            confirmText: 'Yes, do it!',
            onConfirm: (actionUrl) => {
                const parentForm = $el.closest('form');
                if (parentForm.length) {
                    parentForm.submit();
                    return;
                }

                // For link-based actions, prefer explicit data-method and
                // default destructive styled links to DELETE.
                const explicitMethod = ($el.data('method') || '').toString().toUpperCase();
                const inferredMethod = $el.hasClass('text-danger') ? 'DELETE' : '';
                const method = explicitMethod || inferredMethod;

                if (method) {
                    submitFormPost($, actionUrl, method, csrfToken);
                } else {
                    window.location.href = actionUrl;
                }
            },
        });
    });

    $(document).on('click', '.swal-delete', function (e) {
        e.preventDefault();
        const $el = $(this);
        const url = $el.data('url') || $el.attr('href');
        showConfirm({
            url,
            text: $el.data('text'),
            confirmClass: 'btn btn-danger',
            confirmText: 'Yes, delete it!',
            onConfirm: (actionUrl) => submitFormPost($, actionUrl, 'DELETE', csrfToken),
        });
    });

    $(document).on('click', '.swal-post', function (e) {
        e.preventDefault();
        const $el = $(this);
        const url = $el.data('url') || $el.attr('href');
        const method = ($el.data('method') || 'POST').toUpperCase();
        showConfirm({
            url,
            text: $el.data('text'),
            onConfirm: (actionUrl) => submitFormPost($, actionUrl, method, csrfToken),
        });
    });
}

function initFormPost() {
    const $ = window.jQuery || window.$;
    if (!$) return;
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    window.formPost = function (formId, submitButtonId, postUrl, redirectUrl) {
        $('.custom-error-p').remove();
        $('.is-invalid').removeClass('is-invalid');
        const formSelector = formId.startsWith('#') ? formId : '#' + formId;
        const btnSelector = submitButtonId.startsWith('#') ? submitButtonId : '#' + submitButtonId;
        const form = $(formSelector);
        const $btn = $(btnSelector);
        const submitButtonHtml = $btn.html();

        $.ajax({
            url: postUrl,
            type: form.attr('method') || 'POST',
            data: new FormData(form[0]),
            contentType: false,
            processData: false,
            dataType: 'json',
            beforeSend: () => $btn.html(LOADING_HTML).prop('disabled', true),
            success: (data) => {
                if (data.code === 200) {
                    window.toast?.('success', 'Success!', data.message);
                    setTimeout(() => { window.location.href = (redirectUrl || '').replace(/&amp;/g, '&'); }, 1000);
                } else {
                    window.toast?.('error', 'Error!', data.message || 'An error occurred.');
                }
            },
            error: (xhr) => {
                const json = xhr.responseJSON;
                const message = (json?.message) || xhr.statusText || 'An error occurred.';
                window.toast?.('error', 'Error!', message);
                if (json?.code === 422 && json?.errors) {
                    Object.keys(json.errors).forEach((key) => {
                        const err = json.errors[key].join(' ');
                        const inputEl = $(`${formSelector} input[name="${key}"]`);
                        const classEl = $(`.${key}`);
                        inputEl.addClass('is-invalid').after(`<label class="custom-error-p error invalid-feedback d-block">${err}</label>`);
                        classEl.addClass('is-invalid').append(`<small class="custom-error-p text-danger d-block">${err}</small>`);
                    });
                }
            },
            complete: () => $btn.html(submitButtonHtml).prop('disabled', false),
        });
    };

    $(document).on('submit', '.ajaxFormSubmit', function (e) {
        e.preventDefault();
        const form = $(this);
        const formId = form.attr('id') || 'ajaxForm' + Date.now();
        if (!form.attr('id')) form.attr('id', formId);
        let $btn = form.find('button[type="submit"]');
        if (!$btn.length) $btn = form.find('input[type="submit"]');
        if (!$btn.length) return;
        let btnId = $btn.attr('id');
        if (!btnId) { btnId = 'ajaxFormSubmitBtn' + Date.now(); $btn.attr('id', btnId); }
        formPost(formId, btnId, form.attr('action'), form.attr('data-redirect') || window.location.href);
    });
}

function showFlashMessages() {
    const flash = window.__FLASH__ || {};
    if (flash.success) window.showConfirm?.({ icon: 'success', title: 'Success!', text: flash.success, confirmClass: 'btn btn-success', showCancelButton: false, confirmText: 'OK' });
    if (flash.error) window.showConfirm?.({ icon: 'error', title: 'Error!', text: flash.error, confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' });
    if (flash.info) window.toast?.('info', 'Info!', flash.info);
    if (flash.message) window.toast?.('success', 'Message', flash.message);
    if (flash.status) window.toast?.('info', 'Status', flash.status);
    if (flash.warning) window.showConfirm?.({ icon: 'warning', title: 'Warning!', text: flash.warning, confirmClass: 'btn btn-warning', showCancelButton: false, confirmText: 'OK' });
    if (flash.errors) window.showConfirm?.({ icon: 'error', title: 'Validation Error', text: flash.errors, confirmClass: 'btn btn-danger', showCancelButton: false, confirmText: 'OK' });
}

export function initSwal() {
    initSwalHelpers();
    initSwalConfirm();
    initFormPost();
    showFlashMessages();
}
