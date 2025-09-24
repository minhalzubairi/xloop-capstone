<?php
/**
 * Template Name: Home Page
 */
get_header();
?>

<div class="container-fluid bg-light py-5">

  <!-- Hero Section -->
  <section class="text-center mb-5">
    <h1 class="display-4 fw-bold mb-3">Fresh Groceries Delivered to Your Door</h1>
    <p class="lead mb-4">
      Shop the best in organic produce, bakery, and pantry essentials. Healthy,
      local, and always fresh.
    </p>
    <a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="btn btn-green btn-lg px-5">
      Start Shopping
    </a>
  </section>

  <!-- Featured Products -->
  <section class="container mb-5" id="section-featured-products">
    <h2 class="h3 fw-semibold text-center mb-4">Featured Products</h2>
    <div class="row g-4">
      <?php
      $featured_products = wc_get_products( [
        'limit'   => 6,
        'status'  => 'publish',
        'featured'=> true,
      ] );

      if ( $featured_products ) :
        foreach ( $featured_products as $product ) : ?>
          <div class="col-sm-6 col-lg-4">
            <div class="feature-products card h-100 shadow-xs p-3 rounded">
              <img src="<?php echo get_the_post_thumbnail_url( $product->get_id(), 'medium' ); ?>" class="card-img-top" alt="<?php echo esc_attr( $product->get_name() ); ?>">
              <div class="card-body text-center p-0 d-grid">
                <h5 class="card-title"><?php echo esc_html( $product->get_name() ); ?></h5>
                <p class="card-text fw-bold mb-3"><?php echo $product->get_price_html(); ?></p>
                <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" 
                   data-quantity="1" 
                   class="align-self-end btn btn-green add_to_cart_button ajax_add_to_cart"
                   data-product_id="<?php echo esc_attr( $product->get_id() ); ?>"
                   rel="nofollow">
                  Add to Cart
                </a>
              </div>
            </div>
          </div>
        <?php endforeach;
      else: ?>
        <p class="text-center">No featured products found.</p>
      <?php endif; ?>
    </div>
  </section>

  <!-- Categories Section -->
  <section class="container mb-5">
    <h2 class="h3 fw-semibold text-center mb-4">Shop by Category</h2>
    <div class="row g-4 justify-content-center">
      <?php
      $categories = get_terms( [
        'taxonomy'   => 'product_cat',
        'hide_empty' => true,
        'number'     => 8,
      ] );

      if ( !empty($categories) && !is_wp_error($categories) ) :
        foreach ( $categories as $category ) :
          $thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
          $image_url = wp_get_attachment_url( $thumbnail_id ); ?>
          <div class="col-6 col-sm-4 col-md-3">
            <a href="<?php echo get_term_link( $category ); ?>" class="text-decoration-none">
              <div class="feature-products card h-100 shadow-sm p-3 text-center">
                <?php if ( $image_url ) : ?>
                  <img src="<?php echo esc_url( $image_url ); ?>" class="card-img-top" alt="<?php echo esc_attr( $category->name ); ?>">
                <?php endif; ?>
                <div class="card-body">
                  <h5 class="card-title text-dark"><?php echo esc_html( $category->name ); ?></h5>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach;
      else: ?>
        <p class="text-center">No categories found.</p>
      <?php endif; ?>
    </div>
  </section>
  
<?php echo do_shortcode('[recommended_from_last_order]'); ?>

  <!-- Testimonials (static for now) -->
  <section class="container mb-5">
    <h2 class="h3 fw-semibold text-center mb-4">What Our Customers Say</h2>
    <div class="row g-4">
      <?php
      $testimonials = [
        ['name'=>'Sam Patel','text'=>'The produce is always fresh and the delivery is quick. My go-to grocery store!'],
        ['name'=>'Maria Gomez','text'=>'Love the organic options and friendly service. Highly recommended!'],
        ['name'=>'Chris Lee','text'=>'Easy to order and great selection. The bakery items are delicious!'],
      ];

      foreach ( $testimonials as $t ) : ?>
        <div class="col-md-4">
          <div class="feature-products card h-100 shadow-sm p-3 text-center p-4">
            <div class="card-body">
              <h5 class="card-title fw-bold"><?php echo esc_html( $t['name'] ); ?></h5>
              <p class="card-text"><?php echo esc_html( $t['text'] ); ?></p>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

</div>

<?php get_footer(); ?>
