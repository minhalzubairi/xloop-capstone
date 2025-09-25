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
<?php wp_footer(); ?>

</body>
</html>
