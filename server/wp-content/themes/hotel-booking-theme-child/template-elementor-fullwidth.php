<?php
/**
 * Template Name: Elementor Full Width Hotel
 * Template Post Type: page
 */

get_header();

while (have_posts()) :
    the_post();
    ?>
    <main id="primary" class="site-main hotel-elementor-fullwidth">
        <?php the_content(); ?>
    </main>
    <?php
endwhile;

get_footer();
