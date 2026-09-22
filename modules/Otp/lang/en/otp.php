<?php

declare(strict_types=1);

return [
    'whitelist' => [
        // Shared module label: used by the layout and as the breadcrumb link
        // back to the whitelist index from create/edit/show.
        'label' => 'OTP Whitelist',
    ],

    'whitelist_index' => [
        'breadcrumb' => 'OTP Whitelist List',
        'empty' => 'No whitelist entries found',
        'add_new' => 'Add New Entry',
        'col_id' => 'ID',
        'col_type' => 'Type',
        'col_recipient' => 'Recipient',
        'col_fixed_otp' => 'Fixed OTP',
        'view_entry' => 'View Entry',
        'edit_entry' => 'Edit Entry',
        'delete_entry' => 'Delete Entry',
        'delete_confirm' => 'Are you sure you want to delete this whitelist entry?',
    ],

    'whitelist_create' => [
        'breadcrumb' => 'Create Entry',
        'title' => 'New Whitelist Entry',
        'subtitle' => 'Assign a fixed OTP to a specific recipient',
        'back_label' => 'Back to List',
        'section_title' => 'Entry Details',
        'recipient_type_label' => 'Recipient Type',
        'option_email' => 'Email',
        'option_phone' => 'Phone',
        'select_type_placeholder' => 'Select Type',
        'recipient_label' => 'Recipient',
        'recipient_placeholder' => 'Email address or phone number',
        'fixed_otp_label' => 'Fixed OTP',
        'fixed_otp_placeholder' => ':digits-digit OTP',
        'fixed_otp_help' => 'Must be exactly :digits digits (0–9)',
        'select_status_placeholder' => 'Select Status',
        'description_placeholder' => 'Optional note…',
        'save_button' => 'Save Entry',
    ],

    'whitelist_edit' => [
        'breadcrumb' => 'Edit Entry #:id',
        'title' => 'Edit Whitelist Entry #:id',
        'subtitle' => 'Update the fixed OTP or status for this recipient',
        'back_label' => 'Back to List',
        'section_title' => 'Entry Details',
        'recipient_type_label' => 'Recipient Type',
        'option_email' => 'Email',
        'option_phone' => 'Phone',
        'select_type_placeholder' => 'Select Type',
        'recipient_label' => 'Recipient',
        'recipient_placeholder' => 'Email address or phone number',
        'fixed_otp_label' => 'Fixed OTP',
        'fixed_otp_placeholder' => ':digits-digit OTP',
        'fixed_otp_help' => 'Must be exactly :digits digits (0–9)',
        'select_status_placeholder' => 'Select Status',
        'description_placeholder' => 'Optional note…',
        'update_button' => 'Update Entry',
    ],

    'whitelist_show' => [
        'breadcrumb' => 'Entry #:id',
        'title' => 'Whitelist Entry #:id',
        'back_label' => 'Back to List',
        'section_details' => 'Entry Details',
        'section_timestamps' => 'Timestamps',
        'recipient_type_label' => 'Recipient Type',
        'recipient_label' => 'Recipient',
        'fixed_otp_label' => 'Fixed OTP',
        'last_updated_label' => 'Last Updated',
        'no_description' => '—',
        'delete_confirm' => 'Delete this whitelist entry?',
    ],

    'history_index' => [
        'breadcrumb' => 'Verification Code History',
        'search_placeholder' => 'Email / phone…',
        'contact_type_label' => 'Contact Type',
        'all_types_placeholder' => 'All Types',
        'date_from_label' => 'Date From',
        'date_to_label' => 'Date To',
        'status_all' => 'All',
        'status_verified' => 'Verified',
        'status_not_verified' => 'Not Verified',
        'empty' => 'No verification codes found',
        'col_id' => 'ID',
        'col_contact_type' => 'Contact Type',
        'col_contact' => 'Contact',
        'col_code' => 'Code',
        'col_expires_at' => 'Expires At',
        'col_sent_at' => 'Sent At',
        'badge_verified' => 'Verified',
        'badge_expired' => 'Expired',
        'badge_pending' => 'Pending',
    ],

    'widget' => [
        'title' => 'OTP',
        'verified_today' => 'Verified Today',
        'whitelist_label' => 'Whitelist',
    ],

    'email_verification' => [
        'title' => 'Verification Code',
        'heading' => 'Verification Code',
        'intro' => 'Thank you for using :app. Please use the following verification code to complete your verification:',
        'validity' => 'This code is valid for 30 minutes.',
        'ignore_notice' => 'If you did not request this code, please ignore this email.',
        'rights_reserved' => 'All rights reserved.',
    ],

    'notifications' => [
        'subject' => 'Verification Code',
        'sms_body' => ':app: Your verification code is: :code',
    ],

    'flash' => [
        'whitelist_duplicate' => 'This recipient is already whitelisted.',
        'whitelist_created' => 'Whitelist entry created successfully.',
        'whitelist_update_conflict' => 'Another entry already exists for this recipient.',
        'whitelist_updated' => 'Whitelist entry updated successfully.',
        'whitelist_deleted' => 'Whitelist entry deleted successfully.',
    ],

    'errors' => [
        'account_exists' => 'An account with this :type already exists.',
        'wait_before_retry' => 'Please wait 60 seconds before requesting another code.',
        'max_attempts_reached' => 'Maximum verification code limit reached. Please try again later.',
        'invalid_or_expired_code' => 'Invalid or expired verification code.',
    ],

    'success' => [
        'code_sent' => 'Verification code sent.',
        'code_valid' => 'Verification code is valid.',
    ],

    'validation' => [
        'contact_type_required' => 'The contact type is required.',
        'contact_type_in' => 'The contact type must be email or phone.',
        'contact_required' => 'The contact (email or phone) is required.',
        'email_invalid' => 'Please provide a valid email address.',
        'code_required' => 'The verification code is required.',
        'code_size' => 'The verification code must be :digits digits.',
        'recipient_type_required' => 'The recipient type is required.',
        'recipient_type_in' => 'The recipient type must be email or phone.',
        'recipient_required' => 'The recipient is required.',
        'fixed_otp_required' => 'The fixed OTP is required.',
        'fixed_otp_size' => 'The fixed OTP must be exactly :digits digits.',
        'fixed_otp_regex' => 'The fixed OTP must contain only digits.',
    ],
];
