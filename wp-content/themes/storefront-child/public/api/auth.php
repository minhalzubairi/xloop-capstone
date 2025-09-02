<?php
  add_action('rest_api_init', function() {
    register_rest_route('store/v1', '/login', array(
        'methods' => 'POST',
        'callback' => 'store_api_login_func',
    ));
});

function store_api_login_func( WP_REST_Request $request ) {
    $login = $request->get_param('login');
    $password = $request->get_param('password');

    if (!$login || !$password) {
        return [
            'success' => false,
            'error' => 'Login and password required'
        ];
    }

    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $user = get_user_by('email', $login);
    } else {
        $user = get_user_by('login', $login);
    }

    if (!$user || !wp_check_password($password, $user->data->user_pass, $user->ID)) {
        return [
            'success'=>false,
            'error'=>'Invalid Credentials'
        ];
    }

    if (!class_exists('WP_Application_Passwords')) {
        return [
            'success' => false,
            'error' => 'Application passwords not supported'
        ];
    }

    $application_password = WP_Application_Passwords::create_new_application_password($user->ID, ['name' => 'mobile-app']);

    $user_data = [
        'ID' => $user->ID,
        'user_login' => $user->user_login,
        'user_email' => $user->user_email,
        'display_name' => $user->display_name,
    ];

    return [
        'success' => true,
        'user' => $user_data,
        'token' => $application_password[0],
    ];
}
