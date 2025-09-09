<?php
/**
 * Template Name: Home Page
 */

get_header(); ?>

<div class="font-sans min-h-screen bg-[#E6F0FA] flex flex-col items-center justify-center">

    <!-- Hero Section -->
    <section class="w-full flex flex-col items-center justify-center py-16 px-4 sm:px-8 bg-white rounded-xl shadow-sm mb-12">
        <h1 class="text-4xl sm:text-5xl font-bold text-black mb-4 text-center">
            Fresh Groceries Delivered to Your Door
        </h1>
        <p class="text-lg sm:text-xl text-black mb-8 text-center max-w-xl">
            Shop the best in organic produce, bakery, and pantry essentials. Healthy,
            local, and always fresh.
        </p>
        <a href="/shop" class="bg-[#0058A3] text-white hover:bg-[#003B64] px-8 py-6 text-lg rounded-full shadow transition-all duration-200 inline-block">
            Start Shopping
        </a>
    </section>

    <!-- Featured Products -->
    <section class="w-full mb-16">
        <h2 class="text-2xl font-semibold text-black mb-8 text-center">Featured Products</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-8">
            <?php 
            // Sample products array
            $products = [
                ['name'=>'Fresh Avocados','price'=>'$2.99 / lb','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['name'=>'Organic Bananas','price'=>'$1.29 / lb','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['name'=>'Cherry Tomatoes','price'=>'$3.49 / box','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['name'=>'Baby Spinach','price'=>'$2.49 / bag','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['name'=>'Whole Wheat Bread','price'=>'$2.99','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['name'=>'Almond Milk','price'=>'$3.99','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
            ];

            foreach ($products as $product): ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-200 border-0">
                    <div class="relative w-full h-48 rounded-t-xl overflow-hidden">
                        <img src="<?php echo esc_url(get_template_directory_uri() . $product['image']); ?>" alt="<?php echo esc_attr($product['name']); ?>" class="object-cover w-full h-full">
                    </div>
                    <div class="pt-4 pb-2 px-4">
                        <h3 class="text-lg font-medium text-black"><?php echo esc_html($product['name']); ?></h3>
                        <p class="text-md text-black"><?php echo esc_html($product['price']); ?></p>
                    </div>
                    <div class="px-4 pb-4">
                        <a href="/cart" class="bg-[#0058A3] text-white hover:bg-[#003B64] w-full rounded-md transition-all duration-200 inline-block text-center py-2">
                            Add to Cart
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="w-full mb-16">
        <h2 class="text-2xl font-semibold text-black mb-8 text-center">Shop by Category</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 justify-center">
            <?php
            $categories = [
                ['title'=>'Fresh Produce','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['title'=>'Bakery','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['title'=>'Dairy & Eggs','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
                ['title'=>'Pantry Staples','image'=>'/wp-content/themes/your-theme/assets/product-img.jpg'],
            ];

            foreach ($categories as $cat): ?>
                <div class="group bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-200 border-0 cursor-pointer">
                    <div class="relative w-full h-32 rounded-t-xl overflow-hidden">
                        <img src="<?php echo esc_url(get_template_directory_uri() . $cat['image']); ?>" alt="<?php echo esc_attr($cat['title']); ?>" class="object-cover w-full h-full group-hover:scale-105 transition-transform duration-200">
                    </div>
                    <div class="pt-3 pb-4 px-4 flex items-center justify-center">
                        <span class="text-md font-medium text-black"><?php echo esc_html($cat['title']); ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="w-full mb-16">
        <h2 class="text-2xl font-semibold text-black mb-8 text-center">What Our Customers Say</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <?php
            $testimonials = [
                ['name'=>'Sam Patel','avatar'=>'/wp-content/themes/your-theme/assets/product-img.jpg','text'=>'The produce is always fresh and the delivery is quick. My go-to grocery store!'],
                ['name'=>'Maria Gomez','avatar'=>'/wp-content/themes/your-theme/assets/product-img.jpg','text'=>'Love the organic options and friendly service. Highly recommended!'],
                ['name'=>'Chris Lee','avatar'=>'/wp-content/themes/your-theme/assets/product-img.jpg','text'=>'Easy to order and great selection. The bakery items are delicious!'],
            ];

            foreach ($testimonials as $t): ?>
                <div class="bg-white rounded-xl shadow-md border-0 p-6 flex flex-col items-center text-center hover:shadow-lg transition-shadow duration-200">
                    <img src="<?php echo esc_url(get_template_directory_uri() . $t['avatar']); ?>" alt="<?php echo esc_attr($t['name']); ?>" class="mb-4 w-16 h-16 rounded-full object-cover">
                    <h4 class="font-semibold text-black mb-2"><?php echo esc_html($t['name']); ?></h4>
                    <p class="text-black text-md"><?php echo esc_html($t['text']); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Footer Section -->
    <footer class="w-full py-10 mt-12 border-t border-green-100 bg-white flex flex-col items-center">
        <div class="flex flex-wrap gap-8 mb-6">
            <a href="/about" class="text-black hover:text-[#0058A3] transition-colors">About</a>
            <a href="/contact" class="text-black hover:text-[#0058A3] transition-colors">Contact</a>
            <a href="/privacy" class="text-black hover:text-[#0058A3] transition-colors">Privacy</a>
        </div>
        <div class="flex gap-6 mb-4">
            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" class="text-[#0058A3] hover:text-[#0058A3] transition-colors">Instagram</a>
            <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" class="text-[#0058A3] hover:text-[#0058A3] transition-colors">Twitter</a>
            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" class="text-[#0058A3] hover:text-[#0058A3] transition-colors">Facebook</a>
        </div>
        <span class="text-xs text-[#0058A3]">&copy; <?php echo date('Y'); ?> FreshMart. All rights reserved.</span>
    </footer>

</div>

<?php get_footer(); ?>
