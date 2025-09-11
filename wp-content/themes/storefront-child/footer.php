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
    api_url: "http://127.0.0.1:5000/recommend",
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

                var container = $("#recommend-list");
                container.empty();

                if (!res.recommendations || res.recommendations.length === 0) {
                    container.html("<p>No recommendations from API</p>");
                    return;
                }

                res.recommendations.forEach(function (rec) {
                    var name = rec.item || rec; // handle both object or string
                    $.post(
                        recommendData.ajax_url,
                        {
                            action: "find_product_by_name",
                            name: name,
                        },
                        function (resp) {
                            if (resp.success) {
                                container.append(
                                    `<div class="recommended-item">
                                        <span>${resp.data.name}</span>
                                        <a href="${resp.data.url}" 
                                           class="button add_to_cart_button ajax_add_to_cart" 
                                           data-product_id="${resp.data.id}">
                                           Add to Cart
                                        </a>
                                    </div>`
                                );
                            } else {
                                container.append(
                                    `<div class="recommended-item">
                                        <span>${name}</span>
                                        <a href="/shop" class="button">View More</a>
                                    </div>`
                                );
                            }
                        }
                    );
                });
            },
            error: function (xhr, status, error) {
                console.error("❌ API request failed:", status, error);
                $("#recommend-list").html("<p>API request failed</p>");
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
<?php wp_footer(); ?>

</body>
</html>
