<?php
  register_rest_route('store/v1', '/login', array(
    'methods' => 'POST',
    'callback' => 'store_api_login_func',
  ));

function store_api_login_func( WP_REST_Request $request ) {
  $body = $request->get_body();
  $body = json_decode($body, true);
  $login = $body['login'];
  $password = $body['password'];
  $user = false;
  // $user = get_user_by( 'email', $email );
  if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
    $user = get_user_by('email', $login);
  } else {
    $user = get_user_by('login', $login);
  }
  if ( !$user || !wp_check_password( $password, $user->data->user_pass, $user->ID ) ) {
    wp_send_json([
      'success'=>false,
      'error'=>'Invalid Credentials'
    ]);
  }
  $application_password = WP_Application_Passwords::create_new_application_password($user->ID, array('name' => 'mobile-app'));
  $user = gnetwork_get_user_object_from_user_id($user->ID);
  wp_send_json([
    'success' => true,
    'user' => $user,
    'token' => $application_password[0],
    'application_password' => $application_password[0],
  ]);
}