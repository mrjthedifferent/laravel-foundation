<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Common
    |--------------------------------------------------------------------------
    |
    | Words reused verbatim across nearly every module's views. A module view
    | uses one of these instead of a local key when — and only when — its own
    | text matches exactly; anything even slightly different (e.g. "Delete
    | Role" instead of "Delete") stays local to the module's own lang file.
    */
    'common' => [
        'action' => 'Action',
        'actions' => 'Actions',
        'name' => 'Name',
        'status' => 'Status',
        'description' => 'Description',
        'created_at' => 'Created At',
        'updated_at' => 'Updated At',
        'view' => 'View',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'create' => 'Create',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'close' => 'Close',
        'search' => 'Search',
        'filter' => 'Filter',
        'reset' => 'Reset',
        'export' => 'Export',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'yes' => 'Yes',
        'no' => 'No',
        'confirm_delete' => 'Are you sure you want to delete this? This action cannot be undone.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Layout
    |--------------------------------------------------------------------------
    |
    | Text shared across more than one layout/partial (e.g. the "Home" link
    | that appears in several breadcrumbs and the "Dashboard" label that
    | appears in both the dashboard breadcrumb and the sidebar nav item).
    */
    'layout' => [
        'home' => 'Home',
        'dashboard' => 'Dashboard',
        'logout' => 'Logout',
        'logout_confirm' => 'Are you sure you want to logout?',
        'return_to_my_account' => 'Return to my account',
        'return_to_own_account_confirm' => 'Return to your own account?',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'breadcrumb' => 'Dashboard',
        'greeting_morning' => 'Good morning',
        'greeting_afternoon' => 'Good afternoon',
        'greeting_evening' => 'Good evening',
    ],

    /*
    |--------------------------------------------------------------------------
    | Navbar
    |--------------------------------------------------------------------------
    */
    'navbar' => [
        'search_placeholder' => 'Search',
        'search_everywhere' => 'Search everywhere',
        'search_options' => 'Search options',
        'category' => 'Category',
        'all' => 'All',
        'users' => 'Users',
        'apply' => 'Apply',
        'users_online' => 'Users online',
        'online_now' => 'Online now',
        'no_users_online' => 'No users online',
        'my_profile' => 'My profile',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sidebar
    |--------------------------------------------------------------------------
    */
    'sidebar' => [
        'search_shortcut' => 'Search (Ctrl+K)',
        'navigation' => 'Navigation',
        'main' => 'Main',
    ],

    /*
    |--------------------------------------------------------------------------
    | Footer
    |--------------------------------------------------------------------------
    */
    'footer' => [
        'privacy_policy' => 'Privacy Policy',
        'account_deletion' => 'Account Deletion',
        'support' => 'Support',
    ],

    /*
    |--------------------------------------------------------------------------
    | Impersonation banner
    |--------------------------------------------------------------------------
    */
    'impersonation' => [
        'banner' => 'You are signed in as :name (impersonated by :impersonator).',
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification offcanvas
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'title' => 'Notifications',
        'new_notifications' => 'New notifications',
        'loading' => 'Loading...',
        'mark_all_read' => 'Mark All as Read',
        'mark_all_read_confirm' => 'Are you sure you want to mark all as read?',
        'view_all' => 'View All',
        'failed_to_load' => 'Failed to load notifications',
        'empty' => 'No new notifications',
        'default_title' => 'Notification',
        'default_body' => 'No message',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme configuration (right sidebar)
    |--------------------------------------------------------------------------
    */
    'theme_config' => [
        'title' => 'Theme configuration',
        'layout_badge' => 'Layout :layout',
        'sidebar_badge' => 'Sidebar: :color/:type',
        'navbar_badge' => 'Navbar: :color',
        'font_badge' => 'Font: :font',
        'color_mode' => 'Color mode',
        'session_override' => '(session override)',
        'light_theme' => 'Light theme',
        'light_theme_desc' => 'Set light theme or reset to default',
        'dark_theme' => 'Dark theme',
        'dark_theme_desc' => 'Switch to dark theme',
        'auto_theme' => 'Auto theme',
        'auto_theme_desc' => 'Set theme based on system mode',
        'direction' => 'Direction',
        'rtl_direction' => 'RTL direction',
        'rtl_direction_desc' => 'Toggle between LTR and RTL',
        'persistent_settings' => 'Persistent settings',
        'persistent_settings_note' => 'Changes below are saved to the database and apply to all users.',
        'open_theme_settings' => 'Open Theme Settings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search everywhere (component)
    |--------------------------------------------------------------------------
    */
    'search' => [
        'placeholder' => 'Search',
        'everywhere' => 'Search <span class="fw-bold">"in"</span> everywhere',
        'searching' => 'Searching...',
        'min_chars' => 'Please write at least 3 characters...',
        'users' => 'Users',
        'see_all' => 'See all',
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared component defaults
    |--------------------------------------------------------------------------
    */
    'components' => [
        'back' => 'Back',
        'modal_title' => 'Modal Title',
        'image_alt' => 'image',
    ],

    /*
    |--------------------------------------------------------------------------
    | Table view / pagination (component)
    |--------------------------------------------------------------------------
    */
    'table' => [
        'empty_default' => 'No data available',
        'showing' => 'Showing :first–:last of :total',
        'per_page' => 'Per page',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error pages
    |--------------------------------------------------------------------------
    */
    'errors' => [
        'unauthorized' => 'Unauthorized',
        'payment_required' => 'Payment Required',
        'forbidden' => 'Forbidden',
        'not_found' => 'Not Found',
        'page_expired' => 'Page Expired',
        'too_many_requests' => 'Too Many Requests',
        'server_error' => 'Server Error',
        'service_unavailable' => 'Service Unavailable',
        'go_back' => 'Go Back',
        'return_home' => 'Return Home',
        'generic_failure' => 'Something went wrong. Please try again.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth pages
    |--------------------------------------------------------------------------
    */
    'auth' => [
        'confirm_password' => 'Confirm Password',
        'confirm_password_notice' => 'Please confirm your password before continuing.',
        'password' => 'Password',
        'forgot_password_link' => 'Forgot Your Password?',
        'password_recovery' => 'Password recovery',
        'password_recovery_desc' => 'We will email you a link to reset your password',
        'email' => 'Email',
        'send_reset_link' => 'Send Password Reset Link',
        'back_to_sign_in' => 'Back to sign in',
        'login_heading' => 'Login to your account',
        'login_subheading' => 'Enter your credentials below',
        'email_or_phone' => 'Email or Phone',
        'remember_me' => 'Remember Me',
        'login_button' => 'Login',
        'or_login_with' => 'Or login with',
        'set_new_password' => 'Set new password',
        'enter_account_details' => 'Enter your account details below',
        'new_password' => 'New Password',
        'reset_password_button' => 'Reset Password',
        'back_to_login' => 'Back to login',
        'verify_contact' => 'Verify your contact',
        'verification_required' => 'A verification step is required',
        'verification_link_sent' => 'A fresh verification link has been sent to your email address.',
        'check_email_before' => 'Before proceeding, please check your email',
        'for_verification_link' => 'for a verification link.',
        'resend_verification' => 'Resend Verification Email',
        'no_email_address' => 'Your account has no email address. Please contact support or verify via phone.',
        'log_out' => 'Log out',
        'password_change_required' => 'You must set a new password before continuing.',
        'impersonation_ended' => 'Impersonation ended: :name can no longer access the system.',
        'inactive' => 'Your account is not active.',
        'contact_not_verified' => 'Your contact is not verified.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Profile pages
    |--------------------------------------------------------------------------
    */
    'profile' => [
        'breadcrumb' => 'Profile',
        'delete_account' => 'Delete Account',
        'delete_account_notice' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.',
        'delete_account_confirm_heading' => 'Are you sure you want to delete your account?',
        'delete_account_confirm_notice' => 'Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.',
        'password' => 'Password',
        'update_password' => 'Update Password',
        'update_password_notice' => 'Ensure your account is using a long, random password to stay secure.',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'confirm_password' => 'Confirm Password',
        'saved' => 'Saved.',
        'information' => 'Profile Information',
        'information_notice' => "Update your account's profile information and email address.",
        'full_name' => 'Full Name',
        'mobile_no' => 'Mobile No',
        'email' => 'Email',
        'email_unverified' => 'Your email address is unverified.',
        'resend_verification_link' => 'Click here to re-send the verification email.',
        'verification_link_sent' => 'A new verification link has been sent to your email address.',
        'image' => 'Image',
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF exports
    |--------------------------------------------------------------------------
    */
    'exports' => [
        'logo_alt' => 'logo',
        'footer' => 'Generated :date &nbsp; Page {PAGENO} of {nbpg}',
        'exported_on' => 'Exported on: :date',
        'footer_page' => 'Page {PAGENO} of {nbpg}',
        'no_data' => 'No data found for the selected filters.',
    ],

    /*
    |--------------------------------------------------------------------------
    | API responses
    |--------------------------------------------------------------------------
    |
    | Messages the exception handler and JsonResponseFactory put in the JSON
    | body of an API error when nothing more specific was given.
    */
    'api' => [
        'validation_error' => 'Validation Error: :message',
        'resource_not_found' => 'Resource not found',
        'unauthorized' => 'Unauthorized',
        'forbidden' => 'Forbidden',
        'channel_forbidden' => 'You are not authorized to subscribe to this channel.',
        'not_found' => 'Not found.',
        'error' => 'Error.',
        'broadcasting_unavailable' => 'Broadcasting service unavailable.',
        'server_error' => 'Internal server error',
        'error_occurred' => 'An error occurred.',
        'unexpected_error' => 'An unexpected error occurred. Please try again later.',
    ],
];
