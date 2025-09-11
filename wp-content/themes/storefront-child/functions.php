<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// BEGIN ENQUEUE PARENT ACTION
// AUTO GENERATED - Do not modify or remove comment markers above or below:

if ( !function_exists( 'chld_thm_cfg_locale_css' ) ):
    function chld_thm_cfg_locale_css( $uri ){
        if ( empty( $uri ) && is_rtl() && file_exists( get_template_directory() . '/rtl.css' ) )
            $uri = get_template_directory_uri() . '/rtl.css';
        return $uri;
    }
endif;
add_filter( 'locale_stylesheet_uri', 'chld_thm_cfg_locale_css' );

// END ENQUEUE PARENT ACTION

require get_stylesheet_directory() . '/public/api/auth.php';

// Redirect wp-login.php to /login, but allow POST for login
function custom_login_redirect() {
    global $pagenow;

    // Only redirect if user is on wp-login.php via GET (not POST) and not already logged in
    if ( $pagenow === 'wp-login.php' && $_SERVER['REQUEST_METHOD'] === 'GET' && !is_user_logged_in() ) {
        wp_redirect( home_url('/login') );
        exit;
    }
}
add_action( 'init', 'custom_login_redirect' );


// After login redirect
function custom_login_redirect_after( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        // If admin, take to dashboard
        if ( in_array( 'administrator', $user->roles ) ) {
            return admin_url();
        } 
        // Otherwise, send to homepage (or any page)
        else {
            return home_url('/');
        }
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'custom_login_redirect_after', 10, 3 );


// After logout redirect
function custom_logout_redirect() {
    wp_redirect( home_url('/login') );
    exit;
}
add_action( 'wp_logout', 'custom_logout_redirect' );


add_action('wp_ajax_nopriv_custom_register', 'handle_custom_register');
add_action('wp_ajax_custom_register', 'handle_custom_register');

function handle_custom_register() {
    check_ajax_referer('custom_register_action','custom_register_nonce');

    $email      = sanitize_email($_POST['email'] ?? '');
    $first_name = sanitize_text_field($_POST['first_name'] ?? '');
    $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
    $password   = sanitize_text_field($_POST['password'] ?? '');
    $gender     = sanitize_text_field($_POST['gender'] ?? '');
    $dob        = sanitize_text_field($_POST['dob'] ?? '');

    if (!$email || !$password || !$first_name || !$gender || !$dob) {
        wp_send_json(['success' => false, 'message' => 'All required fields must be filled.']);
    }
    if (email_exists($email)) {
        wp_send_json(['success' => false, 'message' => 'Email already exists.']);
    }

    $username = strtolower($first_name . '.' . $last_name . rand(100,999));
    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json(['success' => false, 'message' => $user_id->get_error_message()]);
    }

    // Save user meta
    wp_update_user([
        'ID' => $user_id,
        'first_name' => $first_name,
        'last_name'  => $last_name,
    ]);
    update_user_meta($user_id, 'gender', $gender);
    update_user_meta($user_id, 'dob', $dob);

    // Call prediction API
    $age = 20;
    if ($dob) {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $age = $birthDate->diff($today)->y;
    }

    $predict_data = [
        'Age' => $age,
        'Annual Income (k$)' => "50",
        'Genre' => $gender,
        'Spending Score (1-100)' => "81"
    ];

    $predict_response = wp_remote_post('http://127.0.0.1:5000/predict', [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($predict_data),
        'timeout' => 5
    ]);

    $prediction = null;
    if (!is_wp_error($predict_response)) {
        $body_pred = wp_remote_retrieve_body($predict_response);
        $json_pred = json_decode($body_pred, true);
        $prediction = $json_pred;
        if (isset($json_pred['predicted_segment'][0])) {
            update_user_meta($user_id, 'customer-type', $json_pred['predicted_segment'][0]);
        }
    }

    wp_send_json([
        'success' => true,
        'message' => 'Registration successful!',
        'prediction' => $prediction
    ]);
}

// ----------------------
// 1. Apply discount if meta matches
// ----------------------
add_action('woocommerce_cart_calculate_fees', function($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    $user_id = get_current_user_id();
    if (!$user_id) return;

    // get meta
    $customer_type = get_user_meta($user_id, 'customer-type', true);

    // check exact match
    if ($customer_type === 'High Income, High Spend → Premium Spenders') {
        $discount = $cart->get_subtotal() * 0.15; // 15% discount for premium spenders
        $cart->add_fee(__('Premium Spender Discount', 'your-textdomain'), -$discount);

        // add WC notice so frontend can detect it
        wc_add_notice(__('Congrats! As a Premium Spender you got 15% off 🎉', 'your-textdomain'));
    }
}, 20, 1);


// ----------------------
// 2. Popup HTML + JS
// ----------------------
add_action('wp_footer', function() {
    if (is_cart() || is_checkout()) { ?>
        <div id="discount-popup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:25px; border-radius:12px; max-width:400px; text-align:center; box-shadow:0 4px 10px rgba(0,0,0,0.2);">
                <h2>🎉 Discount Applied!</h2>
                <p>You’re a <strong>Premium Spender</strong> — enjoy 15% off your order!</p>
                <a href="<?php echo wc_get_page_permalink('shop'); ?>" 
                   style="display:inline-block; margin-top:15px; padding:12px 24px; background:#0058A3; color:#fff; border-radius:8px; text-decoration:none;">
                   Continue Shopping
                </a>
            </div>
        </div>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            // detect WooCommerce notice text
            let notice = document.querySelector(".woocommerce-message, .woocommerce-error, .woocommerce-info");
            if (notice && notice.textContent.includes("Premium Spender")) {
                document.getElementById("discount-popup").style.display = "flex";
            }
        });
        </script>
    <?php }
});


// ===============================
// Shortcode: Recommended Products (empty initially)
// ===============================
add_shortcode('recommended_products', function() {
    ob_start(); ?>
    <div id="recommended-products">
        <h3>🔥 Recommended Products</h3>
        <div id="recommend-list">Loading...</div>
    </div>
    <?php
    return ob_get_clean();
});

// ===============================
// Enqueue JS + Localize Cart Data
// ===============================
add_action('wp_enqueue_scripts', function() {
    // Prepare cart items (names only)
    $cart_items = [];
    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $item) {
            $cart_items[] = $item['data']->get_name();
        }
    }

});

// ===============================
// AJAX: Find Product by Name
// ===============================
add_action('wp_ajax_find_product_by_name', 'find_product_by_name');
add_action('wp_ajax_nopriv_find_product_by_name', 'find_product_by_name');

function find_product_by_name() {
    $name = sanitize_text_field($_POST['name']);
    $id   = wc_get_product_id_by_name($name);

    if ($id) {
        $product = wc_get_product($id);
        wp_send_json_success([
            'id'   => $id,
            'name' => $product->get_name(),
            'url'  => $product->add_to_cart_url(),
        ]);
    } else {
        wp_send_json_error(['name' => $name]);
    }
}

// ===============================
// Helper: Get Product ID by Name
// ===============================
function wc_get_product_id_by_name($name) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("
        SELECT ID FROM {$wpdb->posts}
        WHERE post_title = %s
        AND post_type = 'product'
        AND post_status = 'publish'
        LIMIT 1
    ", $name));
}
