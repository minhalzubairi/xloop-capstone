<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package storefront
 */
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>

<?php do_action( 'storefront_before_site' ); ?>

<div id="page" class="hfeed site">
	<?php do_action( 'storefront_before_header' ); ?>

	<!-- Bootstrap Navbar -->
<!-- Bootstrap Navbar -->
<header class="shadow-sm">
	<nav class="navbar navbar-expand-lg navbar-light align-items-center py-3">
		<div class="container">

			<!-- Logo -->
			<div class="navbar-brand">
				<?php 
				if ( function_exists( 'the_custom_logo' ) && has_custom_logo() ) {
					the_custom_logo();
				} else {
					bloginfo('name'); // fallback to site title
				}
				?>
			</div>

			<!-- Toggler -->
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>

			<!-- Menu -->
			<div class="collapse navbar-collapse" id="mainNavbar">
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'container'      => false,
					'menu_class'     => 'navbar-nav ms-auto mb-2 mb-lg-0',
					'fallback_cb'    => false,
				) );
				?>
								   <!-- Cart Icon -->
				   <a href="<?php echo esc_url( home_url('/cart') ); ?>" class=" ms-lg-3 px-3 position-relative">
					  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
							<circle cx="9" cy="21" r="1"></circle>
							<circle cx="20" cy="21" r="1"></circle>
							<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
						</svg>
				   </a>

				   <!-- Login/My Account Button -->
				   <?php if ( is_user_logged_in() ) : ?>
					   <a href="<?php echo esc_url( home_url('/my-account') ); ?>" class="btn btn-green rounded-pill ms-lg-3 px-4">
						   My Account
					   </a>
				   <?php else : ?>
					   <a href="<?php echo esc_url( home_url('/login') ); ?>" class="btn btn-green rounded-pill ms-lg-3 px-4">
						   Login
					   </a>
				   <?php endif; ?>
			</div>
		</div>
	</nav>
</header>


	<?php
	/**
	 * Functions hooked in to storefront_before_content
	 *
	 * @hooked storefront_header_widget_region - 10
	 * @hooked woocommerce_breadcrumb - 10
	 */
	do_action( 'storefront_before_content' );
	?>

	<div id="content" class="site-content" tabindex="-1">
		<div class="main">
		<?php do_action( 'storefront_content_top' ); ?>
