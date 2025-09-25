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


// // After login redirect
// function custom_login_redirect_after( $redirect_to, $request, $user ) {
//     if ( isset( $user->roles ) && is_array( $user->roles ) ) {
//         // If admin, take to dashboard
//         if ( in_array( 'administrator', $user->roles ) ) {
//             return admin_url();
//         } 
//         // Otherwise, send to homepage (or any page)
//         else {
//             return home_url('/');
//         }
//     }
//     return $redirect_to;
// }
// add_filter( 'login_redirect', 'custom_login_redirect_after', 10, 3 );


// // After logout redirect
// function custom_logout_redirect() {
//     wp_redirect( home_url('/login') );
//     exit;
// }
// add_action( 'wp_logout', 'custom_logout_redirect' );


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




// add_action('wp_enqueue_scripts', function() {
//     if (is_user_logged_in()) {
//         $user_id = get_current_user_id();
//         $orders = wc_get_orders([
//             'customer' => $user_id,
//             'status' => ['completed', 'processing'],
//             'limit' => -1,
//         ]);

//         $purchased_items = [];

//         foreach ($orders as $order) {
//             foreach ($order->get_items() as $item) {
//                 $purchased_items[] = $item->get_name();
//             }
//         }

//         wp_localize_script('my-recommendations', 'recommendData', [
//             'api_url' => 'http://127.0.0.1:5000/recommend-2',
//             'purchased' => $purchased_items,
//             'ajax_url' => admin_url('admin-ajax.php'),
//         ]);
//     }
// });

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


add_shortcode('recommended_from_last_order', function() {
    if (!is_user_logged_in()) return '';

    global $wpdb;
    $user_id = get_current_user_id();
    $table   = $wpdb->prefix . 'user_recommendations';

    // Get top 3 products by total occurrences in the table
    $top_products = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT product_id, product_name, COUNT(*) as cnt
             FROM $table
             WHERE user_id = %d
             GROUP BY product_id
             ORDER BY cnt DESC, MAX(created_at) DESC
             LIMIT 3",
            $user_id
        ),
        ARRAY_A
    );

    $shown_ids = [];

    ob_start(); ?>
    <section class="container mb-5" id="section-recommended-last-order">
        <h2 class="h3 fw-semibold text-center mb-4">Recommended Products</h2>
        <div class="row g-4" id="recommended-last-order-list">
            <?php
            if ($top_products) {
                foreach ($top_products as $rec) {
                    $product = wc_get_product($rec['product_id']);
                    if (!$product || in_array($product->get_id(), $shown_ids)) continue;

                    $shown_ids[] = $product->get_id(); ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="feature-products card h-100 shadow-xs p-3 rounded">
                            <img src="<?php echo wp_get_attachment_image_url($product->get_image_id(), 'medium'); ?>" 
                                 class="card-img-top" alt="<?php echo esc_attr($product->get_name()); ?>">
                            <div class="card-body text-center p-0 d-grid">
                                <h5 class="card-title"><?php echo esc_html($product->get_name()); ?></h5>
                                <p class="card-text fw-bold mb-1"><?php echo $product->get_price_html(); ?></p>
                                <small class="text-muted mb-2"><?php echo esc_html($rec['product_name']); ?></small>
                                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                                   data-quantity="1" 
                                   class="align-self-end btn btn-green add_to_cart_button ajax_add_to_cart"
                                   data-product_id="<?php echo $product->get_id(); ?>" rel="nofollow">
                                   Add to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                <?php }
            }

            // fallback: show last 3 published products
            if (empty($shown_ids)) {
                $latest_products = wc_get_products([
                    'limit'   => 3,
                    'orderby' => 'date',
                    'order'   => 'DESC',
                    'status'  => 'publish',
                ]);
                foreach ($latest_products as $product) { ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="feature-products card h-100 shadow-xs p-3 rounded">
                            <img src="<?php echo wp_get_attachment_image_url($product->get_image_id(), 'medium'); ?>" 
                                 class="card-img-top" alt="<?php echo esc_attr($product->get_name()); ?>">
                            <div class="card-body text-center p-0 d-grid">
                                <h5 class="card-title"><?php echo esc_html($product->get_name()); ?></h5>
                                <p class="card-text fw-bold mb-1"><?php echo $product->get_price_html(); ?></p>
                                <a href="<?php echo esc_url($product->add_to_cart_url()); ?>" 
                                   data-quantity="1" 
                                   class="align-self-end btn btn-green add_to_cart_button ajax_add_to_cart"
                                   data-product_id="<?php echo $product->get_id(); ?>" rel="nofollow">
                                   Add to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                <?php }
            }
            ?>
        </div>
    </section>
    <?php
    return ob_get_clean();
});


add_action('woocommerce_thankyou', 'tpa_hit_api_on_new_order', 10, 1);

function tpa_hit_api_on_new_order($order_id) {
    if (!$order_id) return;

    $order = wc_get_order($order_id);
    if (!$order) return;

    $user_id = $order->get_user_id();
    if (!$user_id) return;

    $items = [];
    foreach ($order->get_items() as $item) {
        $items[] = $item->get_name();
    }
    if (empty($items)) return;

    $api_url = 'http://127.0.0.1:5000/recommend';
    $response = wp_remote_post($api_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode(['cart' => $items])
    ]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) == 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['product_recommendations'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'user_recommendations';

            foreach ($body['product_recommendations'] as $rec) {
                $product_name = sanitize_text_field($rec['item'] ?? '');
                $reason = sanitize_text_field($rec['reason'] ?? 'Recommended for you');
                if (!$product_name) continue;

                $product_id = wc_get_product_id_by_name($product_name);
                if (!$product_id) {
                    error_log("Product not found for recommendation: $product_name");
                    continue;
                }

                // Insert directly — no need to call AJAX
                $wpdb->insert(
                    $table,
                    [
                        'user_id' => $user_id,
                        'order_id' => $order_id,
                        'product_id' => $product_id,
                        'product_name' => $product_name,
                        'reason' => $reason,
                        'created_at' => current_time('mysql'),
                    ],
                    ['%d','%d','%d','%s','%s','%s']
                );
            }
        }
    }
}

#recommendation table

function tpa_create_recommendations_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'user_recommendations';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) NOT NULL AUTO_INCREMENT,
        user_id BIGINT(20) NOT NULL,
        order_id BIGINT(20) DEFAULT 0,
        product_id BIGINT(20) NOT NULL,
        product_name VARCHAR(255),
        reason TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('after_switch_theme', 'tpa_create_recommendations_table');


add_action('wp_ajax_tpa_get_recommendations', 'tpa_get_recommendations');
add_action('wp_ajax_nopriv_tpa_get_recommendations', 'tpa_get_recommendations');

function tpa_get_recommendations() {
    global $wpdb;
    $table = $wpdb->prefix . 'user_recommendations';

    $user_id = intval($_POST['user_id'] ?? 0);
    $order_id = intval($_POST['order_id'] ?? 0);

    $recommendations = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND order_id = %d ORDER BY created_at DESC",
            $user_id, $order_id
        ),
        ARRAY_A
    );

    wp_send_json_success($recommendations);
}

add_action('wp_ajax_tpa_save_recommendations', 'tpa_save_recommendations');
add_action('wp_ajax_nopriv_tpa_save_recommendations', 'tpa_save_recommendations');

function tpa_save_recommendations() {
    global $wpdb;
    $table = $wpdb->prefix . 'user_recommendations';

    $input = json_decode(file_get_contents('php://input'), true); // <- decode JSON
    $user_id = intval($input['user_id'] ?? 0);
    $order_id = intval($input['order_id'] ?? 0);
    $recommendations = $input['recommendations'] ?? [];

    if (!$user_id || !$recommendations) {
        wp_send_json_error('Invalid data');
    }

    foreach ($recommendations as $rec) {
        $product_name = sanitize_text_field($rec['item'] ?? '');
        $reason = sanitize_text_field($rec['reason'] ?? '');

        if (!$product_name) continue;

        // Try to get product ID by name
        $product_id = wc_get_product_id_by_name($product_name);

        // Log if product not found
        if (!$product_id) {
            error_log("Product not found for recommendation: $product_name");
            continue;
        }

        // Skip duplicates for same user/order/product
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table WHERE user_id = %d AND order_id = %d AND product_id = %d",
                $user_id, $order_id, $product_id
            )
        );
        if ($exists) continue;

        $wpdb->insert(
            $table,
            [
                'user_id' => $user_id,
                'order_id' => $order_id,
                'product_id' => $product_id,
                'product_name' => $product_name,
                'reason' => $reason,
                'created_at' => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%s', '%s']
        );
    }

    wp_send_json_success('Saved recommendations');
}




#Sentiment analysis
// Add "Sentiments" submenu under Products
add_action('admin_menu', function () {
    add_submenu_page(
        'edit.php?post_type=product',
        'Sentiments',
        'Sentiments',
        'manage_woocommerce',
        'product-sentiments',
        'render_product_sentiments'
    );
});

add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook === 'product_page_product-sentiments') {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
        wp_enqueue_script('datatables', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', ['jquery'], null, true);
        wp_enqueue_style('datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css');
    }
});

function render_product_sentiments() {
    $products = get_posts([
        'post_type'   => 'product',
        'post_status' => 'publish',
        'numberposts' => -1,
    ]);

    $categories = get_terms([
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
    ]);

    echo '<div class="wrap"><h1>Product Sentiments</h1>';

    // Category filter
    echo '<label for="categoryFilter"><strong>Filter by Category: </strong></label>';
    echo '<select id="categoryFilter" style="margin-bottom:15px;">';
    echo '<option value="">All Categories</option>';
    foreach ($categories as $cat) {
        echo '<option value="' . esc_attr($cat->name) . '">' . esc_html($cat->name) . '</option>';
    }
    echo '</select>';

    if ($products) {
        echo '<table id="sentimentsTable" class="display" style="width:100%;margin-top:10px;">';
        echo '<thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Sentiment Comparison</th>
                    <th>Overall Score</th>
                </tr>
              </thead><tbody>';

        foreach ($products as $i => $product) {
            // ✅ Get actual sentiment counts from postmeta
            $positive = (int) get_post_meta($product->ID, '_sentiment_positive', true);
            $neutral  = (int) get_post_meta($product->ID, '_sentiment_neutral', true);
            $negative = (int) get_post_meta($product->ID, '_sentiment_negative', true);

            // Overall score example: positives minus negatives
            $overall = $positive - $negative;

            $cats = wp_get_post_terms($product->ID, 'product_cat', ['fields' => 'names']);
            $cat_names = implode(', ', $cats);

            $canvas_id = 'sentimentChart_' . $i;

            echo '<tr>
                    <td><a href="' . get_edit_post_link($product->ID) . '">' . esc_html($product->post_title) . '</a></td>
                    <td>' . esc_html($cat_names) . '</td>
                    <td data-order="' . esc_attr($positive + $neutral + $negative) . '">
                        <canvas id="' . esc_attr($canvas_id) . '" height="50"></canvas>
                        <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            var ctx = document.getElementById("' . $canvas_id . '").getContext("2d");
                            new Chart(ctx, {
                                type: "bar",
                                data: {
                                    labels: ["Positive", "Neutral", "Negative"],
                                    datasets: [{
                                        data: [' . $positive . ',' . $neutral . ',' . $negative . '],
                                        backgroundColor: ["#4caf50","#9e9e9e","#f44336"]
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    plugins: { legend: { display: false } },
                                    scales: { y: { beginAtZero: true } }
                                }
                            });
                        });
                        </script>
                    </td>
                    <td data-order="' . esc_attr($overall) . '">' . esc_html($overall) . '</td>
                </tr>';
        }


        echo '</tbody></table>';
    } else {
        echo '<p>No products found.</p>';
    }

    echo '<script>
    jQuery(document).ready(function($) {
        var table = $("#sentimentsTable").DataTable({
            pageLength: 10,
            order: [[3, "desc"]] // default sort by overall score, highest first
        });

        $("#categoryFilter").on("change", function() {
            table.column(1).search(this.value).draw();
        });
    });
    </script>';

    echo '</div>';
}



#post to sentiment api when review is given
// Hook when a comment (review) is approved
add_action('comment_post', function ($comment_ID, $comment_approved, $commentdata) {
    if ($comment_approved != 1) return; // only process approved reviews

    $comment = get_comment($comment_ID);

    // Ensure it's a WooCommerce product review
    if (get_post_type($comment->comment_post_ID) !== 'product') return;

    $product_id  = $comment->comment_post_ID;
    $review_text = $comment->comment_content;

    // Send review text to sentiment API
    $response = wp_remote_post('http://127.0.0.1:5000/sentiment', [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode(['text' => $review_text]),
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) return; // bail if API failed

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['sentiment'])) return;

    $sentiment = strtolower($body['sentiment']); // "positive" | "neutral" | "negative"

    // Map sentiments to product meta keys
    $meta_key_map = [
        'positive' => '_sentiment_positive',
        'neutral'  => '_sentiment_neutral',
        'negative' => '_sentiment_negative',
    ];

    if (isset($meta_key_map[$sentiment])) {
        $meta_key = $meta_key_map[$sentiment];
        $current  = (int) get_post_meta($product_id, $meta_key, true);
        update_post_meta($product_id, $meta_key, $current + 1);
    }
}, 10, 3);

// Add submenu page
add_action('admin_menu', function() {
    add_submenu_page(
        'users.php',
        'Customer Segmentation',
        'Customer Segmentation',
        'manage_options',
        'customer-segmentation',
        'render_customer_segmentation_page'
    );
});

// Enqueue Chart.js in admin
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook === 'users_page_customer-segmentation') {
        wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true);
    }
});

function render_customer_segmentation_page() {
    // --- Cluster definitions ---
    $clusters = [
        [
            'label' => 'Wealthy, Family-Focused',
            'description' => 'Affluent customers, family-oriented, respond well to premium offers.',
            'discount' => 5,
            'color' => '#4caf50'
        ],
        [
            'label' => 'Budget-Savvy Family',
            'description' => 'Price-conscious families looking for value, good with bundles and discounts.',
            'discount' => 10,
            'color' => '#ff9800'
        ],
        [
            'label' => 'Young Trend Seekers',
            'description' => 'Young, trend-conscious customers, respond well to new and trendy products.',
            'discount' => 8,
            'color' => '#2196f3'
        ],
        [
            'label' => 'Loyal, Family-Oriented',
            'description' => 'Long-term loyal customers, family-oriented, appreciate special offers and rewards.',
            'discount' => 12,
            'color' => '#f44336'
        ]
    ];

    echo '<div class="wrap">';
    echo '<h1 style="font-size: 24px; font-weight: bold; margin-bottom: 20px;">Customer Segmentation Overview</h1>';

    // --- Cluster cards ---
    echo '<div style="display:flex; gap:15px; margin-bottom:30px;">';
    foreach ($clusters as $cluster) {
        echo '<div style="flex:1; border-radius:8px; padding:15px; background:'.$cluster['color'].'; color:#fff;">';
        echo '<h2>'.esc_html($cluster['label']).'</h2>';
        echo '<p>'.esc_html($cluster['description']).'</p>';
        echo '<p><strong>Suggested Discount: '.$cluster['discount'].'%</strong></p>';
        echo '</div>';
    }
    echo '</div>';

    // --- Users assignment from customer_type meta ---
    global $wpdb;
    $users = $wpdb->get_results("
        SELECT u.ID, u.user_login, u.user_email, m.meta_value AS segment
        FROM {$wpdb->users} u
        LEFT JOIN {$wpdb->usermeta} m ON u.ID = m.user_id AND m.meta_key = 'customer_type'
    ");

    $user_data = [];
    $segment_counts = [];
    $dummy_engagement = [];

    foreach ($users as $user) {
        $segment_label = $user->segment ?: 'Unassigned';
        $discount = 0;

        foreach ($clusters as $c) {
            if ($c['label'] === $segment_label) {
                $discount = $c['discount'];
                break;
            }
        }

        $user_data[] = [
            'ID' => $user->ID,
            'login' => $user->user_login,
            'email' => $user->user_email,
            'segment' => $segment_label,
            'discount' => $discount
        ];

        if (!isset($segment_counts[$segment_label])) $segment_counts[$segment_label] = 0;
        $segment_counts[$segment_label]++;

        if (!isset($dummy_engagement[$segment_label])) $dummy_engagement[$segment_label] = 0;
        $dummy_engagement[$segment_label] += rand(1, 5);
    }

    // --- Chart container with two per row ---
    echo '<div style="display:flex; flex-wrap:wrap; gap:30px;">';

    $charts = [
        ['id' => 'clusterChart', 'title' => 'Suggested Discount per Segment'],
        ['id' => 'discountChart', 'title' => 'Customer Distribution by Segment'],
        ['id' => 'usersCountChart', 'title' => 'Number of Users per Segment'],
        ['id' => 'engagementChart', 'title' => 'Segment Engagement Metric (Dummy)'],
    ];

    foreach ($charts as $chart) {
        echo '<div style="flex:1 1 48%; max-width:48%;">';
        echo '<h2>'.esc_html($chart['title']).'</h2>';
        echo '<canvas id="'.esc_attr($chart['id']).'" style="height:250px;"></canvas>';
        echo '</div>';
    }

    echo '</div>'; // close chart flex container

    // --- Users table ---
    echo '<h2>Users with Assigned Discounts</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>User ID</th><th>Login</th><th>Email</th><th>Segment</th><th>Discount</th></tr></thead><tbody>';
    foreach ($user_data as $u) {
        echo '<tr><td>'.esc_html($u['ID']).'</td><td>'.esc_html($u['login']).'</td><td>'.esc_html($u['email']).'</td><td>'.esc_html($u['segment']).'</td><td>'.esc_html($u['discount'].'%').'</td></tr>';
    }
    echo '</tbody></table>';

    // --- Chart JS ---
    ?>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const clusters = <?php echo json_encode($clusters); ?>;
        const segmentCounts = <?php echo json_encode($segment_counts); ?>;
        const engagementData = <?php echo json_encode($dummy_engagement); ?>;

        new Chart(document.getElementById('clusterChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: clusters.map(c => c.label),
                datasets: [{ label: 'Suggested Discount (%)', data: clusters.map(c => c.discount), backgroundColor: clusters.map(c => c.color) }]
            },
            options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true, max:15}} }
        });

        new Chart(document.getElementById('discountChart').getContext('2d'), {
            type: 'pie',
            data: { labels: Object.keys(segmentCounts), datasets: [{ data: Object.values(segmentCounts), backgroundColor: clusters.map(c => c.color) }] },
            options: { responsive:true, plugins:{legend:{position:'right'}} }
        });

        new Chart(document.getElementById('usersCountChart').getContext('2d'), {
            type: 'bar',
            data: { labels: Object.keys(segmentCounts), datasets: [{ label: 'Number of Users', data: Object.values(segmentCounts), backgroundColor: clusters.map(c => c.color) }] },
            options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });

        new Chart(document.getElementById('engagementChart').getContext('2d'), {
            type: 'bar',
            data: { labels: Object.keys(engagementData), datasets: [{ label: 'Engagement Score', data: Object.values(engagementData), backgroundColor: clusters.map(c => c.color) }] },
            options: { responsive:true, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}} }
        });
    });
    </script>
    <?php
    echo '</div>';
}


#segmentation form

// Shortcode to show the "Get Personalized Recommendations" form
add_shortcode('personalized_recommendations_form', function() {
    if (!is_user_logged_in()) return 'Please login to get personalized recommendations.';

    $user_id = get_current_user_id();
    $existing_segment = get_user_meta($user_id, 'customer_type', true);
    if ($existing_segment) return '<p>Your personalized recommendations have already been generated.</p>';

    ob_start(); ?>
    
    <button id="open-recommendation-popup" class="popup-btn">Get Personalized Recommendations</button>

    <div id="recommendation-popup" class="popup-modal">
        <div class="popup-content">
            <span class="close-popup">&times;</span>
            <h2>Get Personalized Recommendations</h2>

            <form id="multi-step-form" method="post">
                <input type="hidden" name="action" value="generate_recommendation">
                <?php wp_nonce_field('generate_recommendation'); ?>

                <!-- Step 1 -->
                <div class="step step-1 active">
                    <p>Welcome! Let's get to know you better.</p>
                    <p>Your Age: <span id="user-age"></span></p>
                    <button type="button" class="next-btn">Next</button>
                </div>

                <!-- Step 2 -->
                <div class="step step-2">
                    <label>Education:
                        <select name="Education">
                            <option value="">Select</option>
                            <option value="High School">High School</option>
                            <option value="Bachelor">Bachelor</option>
                            <option value="Master">Master</option>
                            <option value="PhD">PhD</option>
                        </select>
                    </label>
                    <label>Marital Status:
                        <select name="Marital_Status">
                            <option value="">Select</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                        </select>
                    </label>
                    <label>Do you have children?
                        <select name="Has_Children">
                            <option value="0">No</option>
                            <option value="1">Yes</option>
                        </select>
                    </label>
                    <button type="button" class="prev-btn">Previous</button>
                    <button type="button" class="next-btn">Next</button>
                </div>

                <!-- Step 3 -->
                <div class="step step-3">
                    <label>Monthly Spending ($):
                        <input type="number" name="Spending" value="1200">
                    </label>
                    <label>Purchases per year:
                        <input type="number" name="Purchases" value="35">
                    </label>
                    <label>Recency (months):
                        <input type="number" name="Recency" value="12">
                    </label>
                    <label>Response (0/1):
                        <input type="number" name="Response" value="1" min="0" max="1">
                    </label>
                    <button type="button" class="prev-btn">Previous</button>
                    <button type="submit">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <style>
        .popup-modal { display:none; position:fixed; z-index:999; left:0; top:0; width:100%; height:100%; overflow:auto; background:rgba(0,0,0,0.6); font-family:sans-serif; }
        .popup-content { background:#fff; margin:5% auto; padding:30px; border-radius:12px; width:90%; max-width:500px; position:relative; transition: all 0.3s; }
        .close-popup { position:absolute; top:10px; right:15px; font-size:28px; cursor:pointer; }
        .popup-btn { padding:12px 25px; font-size:16px; cursor:pointer; border:none; border-radius:6px; background:#0073aa; color:#fff; }
        .step { display:none; animation:fadeIn 0.4s; }
        .step.active { display:block; }
        button.next-btn, button.prev-btn, button[type="submit"] { padding:8px 18px; margin:10px 5px; border:none; border-radius:5px; background:#0073aa; color:#fff; cursor:pointer; }
        button.prev-btn { background:#555; }
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
        label { display:block; margin:10px 0; }
        input, select { width:100%; padding:8px; margin-top:5px; border:1px solid #ccc; border-radius:4px; }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('recommendation-popup');
            const btn = document.getElementById('open-recommendation-popup');
            const close = document.querySelector('.close-popup');

            btn.addEventListener('click', () => {
                modal.style.display = 'block';

                // Set user age from dob
                const dob = '<?php echo get_user_meta($user_id, 'dob', true); ?>';
                let age = 20;
                if(dob) {
                    const birth = new Date(dob);
                    const today = new Date();
                    age = today.getFullYear() - birth.getFullYear();
                    if(today.getMonth() < birth.getMonth() || (today.getMonth() === birth.getMonth() && today.getDate() < birth.getDate())) age--;
                }
                document.getElementById('user-age').innerText = age;
            });
            close.addEventListener('click', () => modal.style.display = 'none');
            window.addEventListener('click', (e) => { if(e.target === modal) modal.style.display='none'; });

            // Multi-step logic
            const steps = document.querySelectorAll('.step');
            const nextBtns = document.querySelectorAll('.next-btn');
            const prevBtns = document.querySelectorAll('.prev-btn');
            let currentStep = 0;

            nextBtns.forEach(btn => btn.addEventListener('click', () => {
                steps[currentStep].classList.remove('active');
                currentStep++;
                steps[currentStep].classList.add('active');
            }));

            prevBtns.forEach(btn => btn.addEventListener('click', () => {
                steps[currentStep].classList.remove('active');
                currentStep--;
                steps[currentStep].classList.add('active');
            }));
        });
    </script>
    <?php
    return ob_get_clean();
});

// Handle form submission
add_action('admin_post_nopriv_generate_recommendation', 'handle_recommendation_form');
add_action('admin_post_generate_recommendation', 'handle_recommendation_form');

function handle_recommendation_form() {
    if (!is_user_logged_in() || !check_admin_referer('generate_recommendation')) {
        wp_die('Unauthorized');
    }

    $user_id = get_current_user_id();

    // If segment already exists, stop
    if (get_user_meta($user_id, 'customer_type', true)) {
        wp_redirect(wp_get_referer());
        exit;
    }

    // Collect user meta for API
    $dob        = get_user_meta($user_id, 'dob', true);
    $education  = get_user_meta($user_id, 'Education', true);
    $marital    = get_user_meta($user_id, 'Marital_Status', true);
    $income     = get_user_meta($user_id, 'Income', true);
    $has_child  = get_user_meta($user_id, 'Has_Children', true);
    $purchases  = get_user_meta($user_id, 'Purchases', true);
    $spending   = get_user_meta($user_id, 'Spending', true);
    $recency    = get_user_meta($user_id, 'Recency', true);
    $response   = get_user_meta($user_id, 'Response', true);

    // Calculate age from dob
    $age = 20;
    if ($dob) {
        $birthDate = new DateTime($dob);
        $today = new DateTime('today');
        $age = $birthDate->diff($today)->y;
    }

    $features = [
        [
            "AgeGroup"         => $age,
            "Education_Encoded"=> $education,
            "Marital_Status"   => $marital,
            "Income"           => (float)$income ?: 72000,
            "Has_Children"     => (int)$has_child ?: 0,
            "Purchases"        => (int)$purchases ?: 35,
            "Spending"         => (float)$spending ?: 1200,
            "Recency"          => (int)$recency ?: 12,
            "Response"         => (int)$response ?: 1,
        ]
    ];

    // Call segmentation API
    $response = wp_remote_post('http://127.0.0.1:5000//sentiment', [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => json_encode($features),
        'timeout' => 5
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $json = json_decode($body, true);
        if (isset($json[0]['Predicted_Segment'])) {
            $segment = $json[0]['Predicted_Segment'];
            update_user_meta($user_id, 'customer_type', $segment);
            wp_redirect(add_query_arg('success', '1', wp_get_referer()));
            exit;
        }
    }

    wp_redirect(add_query_arg('error', '1', wp_get_referer()));
    exit;
}

