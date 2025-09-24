<?php
// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


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

// // Redirect wp-login.php to /login, but allow POST for login
// function custom_login_redirect() {
//     global $pagenow;

//     // Only redirect if user is on wp-login.php via GET (not POST) and not already logged in
//     if ( $pagenow === 'wp-login.php' && $_SERVER['REQUEST_METHOD'] === 'GET' && !is_user_logged_in() ) {
//         wp_redirect( home_url('/login') );
//         exit;
//     }
// }
// add_action( 'init', 'custom_login_redirect' );


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
    if ($customer_type === 'High Spenders - Loyal & Responsive') {
        $discount = $cart->get_subtotal() * 0.15; // 15% discount for premium spenders
        $cart->add_fee(__('Premium Spender Discount', 'your-textdomain'), -$discount);

        // add WC notice so frontend can detect it
        wc_add_notice(__('Congrats! As a Premium Spender you got 15% off 🎉', 'your-textdomain'));
    }
}, 20, 1);

add_action('wp_footer', function() {
    if (is_front_page() || is_shop()) {
        $show_popup = false;

        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $customer_type = get_user_meta($user_id, 'customer-type', true);

            if ($customer_type === 'Mid-Life Singles - High Spenders - Loyal & Responsive') {
                $show_popup = true;
            }
        }

        if ($show_popup) { ?>
            <div id="discount-popup" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; align-items:center; justify-content:center;">
                <div style="background:#fff; padding:25px; border-radius:12px; max-width:400px; text-align:center; box-shadow:0 4px 10px rgba(0,0,0,0.2); position:relative;">
                    <button id="close-popup" style="position:absolute; top:10px; right:10px; background:none; border:none; font-size:20px; cursor:pointer;">✖</button>
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
                // Show popup only if not previously closed
                if (!localStorage.getItem("premiumPopupClosed")) {
                    document.getElementById("discount-popup").style.display = "flex";
                }

                // Close button handler
                document.getElementById("close-popup").addEventListener("click", function() {
                    document.getElementById("discount-popup").style.display = "none";
                    localStorage.setItem("premiumPopupClosed", "true");
                });
            });
            </script>
        <?php }
    }
});





// ===============================
// Shortcode: Recommended Products (empty initially)
// ===============================
add_shortcode('recommended_products', function() {
    ob_start(); ?>
    <section class="container mb-5" id="section-recommended-products">
        <h2 class="h3 fw-semibold text-center mb-4">Recommended Products</h2>
        <div class="row g-4" id="recommended-list">
            <p class="text-center">Loading...</p>
        </div>
    </section>
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
    if (!isset($_POST['name'])) {
        wp_send_json_error(['message' => 'No product name provided']);
    }

    $name = sanitize_text_field($_POST['name']);
    $product_id = wc_get_product_id_by_sku($name); // optional if you search by SKU
    $args = [
        'name' => $name,
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => 1,
    ];
    $products = get_posts($args);

    if (empty($products)) {
        wp_send_json_error(['message' => 'Product not found']);
    }

    $product = wc_get_product($products[0]->ID);

    $data = [
        'id' => $product->get_id(),
        'name' => $product->get_name(),
        'url' => $product->add_to_cart_url(),
        'price_html' => $product->get_price_html(),
        'image' => wp_get_attachment_image_url($product->get_image_id(), 'medium'),
    ];

    wp_send_json_success($data);
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



// ===============================
// Shortcode: Recommended Products (based on past orders)
// ===============================
add_shortcode('recommended_products_orders', function() {
    ob_start(); ?>
    <section class="container mb-5" id="section-recommended-products">
        <h2 class="h3 fw-semibold text-center mb-4">Recommended Products Based on Your Orders</h2>
        <div class="row g-4" id="recommended-list">
            <p class="text-center">Loading...</p>
        </div>
    </section>
    <?php
    return ob_get_clean();
});


add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $orders = wc_get_orders([
            'customer' => $user_id,
            'status' => ['completed', 'processing'],
            'limit' => -1,
        ]);

        $purchased_items = [];

        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                $purchased_items[] = $item->get_name();
            }
        }

        wp_localize_script('my-recommendations', 'recommendData', [
            'api_url' => 'http://127.0.0.1:5000/recommend-2',
            'purchased' => $purchased_items,
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
});

// ===============================
// 1. Cart-based Recommendations
// ===============================
add_shortcode('cart_recommended_products', function() {
    ob_start();
    $cart_items = [];

    if (WC()->cart) {
        foreach (WC()->cart->get_cart() as $item) {
            $cart_items[] = $item['data']->get_name();
        }
    }
    ?>
    <section class="container mb-5">
        <h2 class="h3 fw-semibold text-center mb-4">🛒 Cart-based Recommendations</h2>
        <div id="cart-recommendations" class="row"></div>
    </section>
    <script>
    jQuery(document).ready(function($) {
        $.ajax({
            url: "http://127.0.0.1:5000/recommend-cart",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({cart: <?php echo json_encode($cart_items); ?>}),
            success: function(res) {
                if(res && res.length){
                    let html = '';
                    res.forEach(function(p){
                        html += '<div class="col-md-3">'+p+'</div>';
                    });
                    $("#cart-recommendations").html(html);
                }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
});

// ===============================
// 2. Order-based Recommendations
// ===============================
add_shortcode('order_recommended_products', function() {
    ob_start();

    $user_id = get_current_user_id();
    $orders = wc_get_orders([
        'customer_id' => $user_id,
        'limit' => -1,
        'status' => 'completed'
    ]);

    $order_items = [];
    foreach ($orders as $order) {
        foreach ($order->get_items() as $item) {
            $order_items[] = $item->get_name();
        }
    }
    ?>
    <section class="container mb-5">
        <h2 class="h3 fw-semibold text-center mb-4">📦 Order-based Recommendations</h2>
        <div id="order-recommendations" class="row"></div>
    </section>
    <script>
    jQuery(document).ready(function($) {
        $.ajax({
            url: "http://127.0.0.1:5000/recommend-orders",
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({orders: <?php echo json_encode($order_items); ?>}),
            success: function(res) {
                if(res && res.length){
                    let html = '';
                    res.forEach(function(p){
                        html += '<div class="col-md-3">'+p+'</div>';
                    });
                    $("#order-recommendations").html(html);
                }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
});

add_shortcode('recommended_from_last_order', function() {
    ob_start(); ?>
    <section class="container mb-5" id="section-recommended-last-order">
        <h2 class="h3 fw-semibold text-center mb-4">Recommended Products</h2>
        <div class="row g-4" id="recommended-last-order-list">
            <p class="text-center">Loading...</p>
        </div>
    </section>
    <?php
    return ob_get_clean();
});

// ===============================
// Enqueue JS + Localize Last Order Data
// ===============================
add_action('wp_enqueue_scripts', function() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $orders = wc_get_orders([
            'limit'   => 1,
            'orderby' => 'date',
            'order'   => 'DESC',
            'customer_id' => $user_id,
        ]);

        $last_order_items = [];
        if (!empty($orders)) {
            $order = $orders[0];
            foreach ($order->get_items() as $item) {
                $last_order_items[] = $item->get_name();
            }
        }
    } else {
        $last_order_items = [];
    }

    wp_localize_script('jquery', 'recommendLastOrderData', [
        'api_url'  => "http://127.0.0.1:5000/recommend-2",
        'items'    => $last_order_items,
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});
