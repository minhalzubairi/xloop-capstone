<?php
$namespace = 'store';

add_action('rest_api_init', function() use ($namespace) {
    register_rest_route($namespace . '/v1', '/login', [
        'methods'  => 'POST',
        'callback' => 'store_api_login_func',
    ]);

    register_rest_route($namespace . '/v1', '/register', [
        'methods'  => 'POST',
        'callback' => 'store_api_register',
    ]);
});

function store_api_register(WP_REST_Request $request) {
    $body = json_decode($request->get_body(), true);

    // Basic user info
    $email      = sanitize_email($body['email'] ?? '');
    $first_name = sanitize_text_field($body['first_name'] ?? '');
    $last_name  = sanitize_text_field($body['last_name'] ?? '');
    $password   = sanitize_text_field($body['password'] ?? '');
    $gender     = sanitize_text_field($body['gender'] ?? '');
    $dob        = sanitize_text_field($body['dob'] ?? ''); // date of birth

    if (!$email || !$password || !$first_name) {
        return ['success' => false, 'error' => 'Email, first name, and password are required'];
    }

    if (email_exists($email)) {
        return ['success' => false, 'error' => 'Email already exists'];
    }

    $username = strtolower($first_name . '.' . $last_name . rand(100,999));

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        return ['success' => false, 'error' => $user_id->get_error_message()];
    }

    wp_update_user([
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name' => $last_name,
    ]);

    if ($gender) update_user_meta($user_id, 'gender', $gender);
    if ($dob)    update_user_meta($user_id, 'dob', $dob);

    $user = new WP_User($user_id);
    $user->set_role('customer');
    // Auto login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id);
        do_action('wp_login', $username, $user);
    $app_pass = WP_Application_Passwords::create_new_application_password($user_id, ['name' => 'mobile-app']);

    // Calculate Age fallback
    $age = 20; // default
    if ($dob) {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $age = $birthDate->diff($today)->y;
    }

    // Additional fallback/default fields
    $predict_data = [
        [
            "Age"            => (int)($body['Age'] ?? $age),
            "Education"      => $body['Education'] ?? null,
            "Marital_Status" => $body['Marital_Status'] ?? null,
            "Spending"       => (float)($body['Spending'] ?? 1200),
            "Purchases"      => (int)($body['Purchases'] ?? 35),
            "Complain"       => (int)($body['Complain'] ?? 0),
            "Response"       => (int)($body['Response'] ?? 1),
            "Recency"        => (int)($body['Recency'] ?? 12),
            "Income"         => (float)($body['Income'] ?? 72000),
        ]
    ];

    // Call prediction API
    $predict_response = wp_remote_post('http://127.0.0.1:5000/predict-segment', [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($predict_data),
        'timeout' => 5
    ]);

    $prediction = null;
    if (!is_wp_error($predict_response)) {
        $body_pred = wp_remote_retrieve_body($predict_response);
        $json_pred = json_decode($body_pred, true);

        if (is_array($json_pred) && isset($json_pred[0]['Predicted_Segment'])) {
            $prediction = $json_pred[0]['Predicted_Segment'];
            update_user_meta($user_id, 'customer-type', $prediction);
            error_log('Predicted customer-type for user ' . $user_id . ': ' . $prediction);
        }
    }

    return [
        'success' => true,
        'user' => [
            'ID'         => $user_id,
            'username'   => $username,
            'email'      => $email,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'gender'     => $gender,
            'dob'        => $dob,
        ],
        'token'      => $app_pass[0],
        'prediction' => $prediction
    ];
}




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
