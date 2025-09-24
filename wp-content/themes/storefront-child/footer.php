<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package storefront
 */

?>

		</div><!-- .col-full -->
	</div><!-- #content -->

	<?php do_action( 'storefront_before_footer' ); ?>

    <footer class="w-100 py-3 border-top bg-white text-center">
    <div class="d-flex flex-wrap justify-content-center gap-3 mb-3">
        <a href="/about" class="text-dark text-decoration-none mx-2">About</a>
        <a href="/contact" class="text-dark text-decoration-none mx-2">Contact</a>
        <a href="/privacy" class="text-dark text-decoration-none mx-2">Privacy</a>
    </div>
    <div class="d-flex justify-content-center gap-3 mb-3">
        <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="text-decoration-none mx-2">Instagram</a>
        <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="text-decoration-none mx-2">Twitter</a>
        <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="text-decoration-none mx-2">Facebook</a>
    </div>
    <span class="small">&copy; <?php echo date('Y'); ?> FreshMart. All rights reserved.</span>
    </footer>

	<?php do_action( 'storefront_after_footer' ); ?>

</div><!-- #page -->

<script>
    var recommendData = {
    api_url: "http://127.0.0.1:5000/recommend-2",
    cart: <?php
        $cart_items = [];
        if (WC()->cart) {
            foreach (WC()->cart->get_cart() as $item) {
                $cart_items[] = $item['data']->get_name();
            }
        }
        echo json_encode($cart_items);
    ?>,
    ajax_url: "<?php echo admin_url('admin-ajax.php'); ?>"
};
    jQuery(document).ready(function ($) {
        function fetchRecommendations() {
            var cartItems = recommendData.cart;
            console.log("🛒 Sending cart to API:", cartItems);

            $.ajax({
                url: recommendData.api_url,
                type: "POST",
                contentType: "application/json",
                data: JSON.stringify({ cart: cartItems }),
                success: function (res) {
                    console.log("📡 API responded with:", res);

            var container = $("#recommended-list"); // match the shortcode
                    container.empty();

                    if (!res.product_recommendations || res.product_recommendations.length === 0) {
                        container.html("<p class='text-center'>No recommendations from API</p>");
                        return;
                    }

                    res.product_recommendations.forEach(function (rec) {
                        var name = rec.item || rec;
                        var reason = rec.reason || "";

                        // Use the AJAX call if you have product info, otherwise fallback
                        $.post(
                            recommendData.ajax_url,
                            { action: "find_product_by_name", name: name },
                            function (resp) {
                                if (resp.success) {
                                    container.append(`
                                        <div class="col-sm-6 col-lg-4">
                                            <div class="feature-products card h-100 shadow-xs p-3 rounded">
                                                <img src="${resp.data.image}" class="card-img-top" alt="${resp.data.name}">
                                                <div class="card-body text-center p-0 d-grid">
                                                    <h5 class="card-title">${resp.data.name}</h5>
                                                    <p class="card-text fw-bold mb-1">${resp.data.price_html}</p>
                                                    <small class="text-muted mb-2">${reason}</small>
                                                    <a href="${resp.data.url}" 
                                                    data-quantity="1" 
                                                    class="align-self-end btn btn-green add_to_cart_button ajax_add_to_cart"
                                                    data-product_id="${resp.data.id}"
                                                    rel="nofollow">
                                                    Add to Cart
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    `);
                                } else {
                                    // Fallback if product not found
                                    container.append(`
                                        <div class="col-sm-6 col-lg-4">
                                            <div class="feature-products card h-100 shadow-xs p-3 rounded text-center">
                                                <h5 class="card-title">${name}</h5>
                                                <p class="text-muted mb-2">${reason}</p>
                                                <a href="/shop" class="btn btn-secondary mt-2">View More</a>
                                            </div>
                                        </div>
                                    `);
                                }
                            }
                        );
                    });
                },
                error: function (xhr, status, error) {
                    console.error("❌ API request failed:", status, error);
                    $("#recommended-list").html("<p class='text-center'>API request failed</p>");
                },
            });
        }

        // Fetch on load
        fetchRecommendations();

        // Refetch whenever item added to cart
        $(document.body).on("added_to_cart", function () {
            fetchRecommendations();
        });
    });
</script>

<script>
jQuery(document).ready(function ($) {
    function fetchLastOrderRecommendations() {
        var orderItems = recommendLastOrderData.items;
        console.log("📦 Sending last order to API:", orderItems);

        $.ajax({
            url: recommendLastOrderData.api_url,
            type: "POST",
            contentType: "application/json",
            data: JSON.stringify({ cart: orderItems }), // API expects same key
            success: function (res) {
                console.log("📡 API (last order) responded:", res);

                var container = $("#recommended-last-order-list");
                container.empty();

                if (!res.product_recommendations || res.product_recommendations.length === 0) {
                    container.html("<p class='text-center'>No recommendations from API</p>");
                    return;
                }

                res.product_recommendations.forEach(function (rec) {
                    var name = rec.item || rec;
                    var reason = rec.reason || "";

                    $.post(
                        recommendLastOrderData.ajax_url,
                        { action: "find_product_by_name", name: name },
                        function (resp) {
                            if (resp.success) {
                                container.append(`
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="feature-products card h-100 shadow-xs p-3 rounded">
                                            <img src="${resp.data.image}" class="card-img-top" alt="${resp.data.name}">
                                            <div class="card-body text-center p-0 d-grid">
                                                <h5 class="card-title">${resp.data.name}</h5>
                                                <p class="card-text fw-bold mb-1">${resp.data.price_html}</p>
                                                <small class="text-muted mb-2">${reason}</small>
                                                <a href="${resp.data.url}" 
                                                data-quantity="1" 
                                                class="align-self-end btn btn-green add_to_cart_button ajax_add_to_cart"
                                                data-product_id="${resp.data.id}"
                                                rel="nofollow">
                                                Add to Cart
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                `);
                            } else {
                                container.append(`
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="feature-products card h-100 shadow-xs p-3 rounded text-center">
                                            <h5 class="card-title">${name}</h5>
                                            <p class="text-muted mb-2">${reason}</p>
                                            <a href="/shop" class="btn btn-secondary mt-2">View More</a>
                                        </div>
                                    </div>
                                `);
                            }
                        }
                    );
                });
            },
            error: function (xhr, status, error) {
                console.error("❌ API request failed:", status, error);
                $("#recommended-last-order-list").html("<p class='text-center'>API request failed</p>");
            },
        });
    }

    // Fetch only once on load
    fetchLastOrderRecommendations();
});
</script>

<?php wp_footer(); ?>

</body>
</html>
