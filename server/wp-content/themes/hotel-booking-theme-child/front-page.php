<?php
get_header();
?>

<?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
        <?php
        $elementor_data = get_post_meta(get_the_ID(), '_elementor_data', true);
        $has_content    = trim((string) get_the_content()) !== '';
        ?>
        <?php if ($has_content || !empty($elementor_data)) : ?>
            <main id="primary" class="site-main hotel-homepage-content">
                <?php the_content(); ?>
            </main>
        <?php else : ?>
            <?php echo do_shortcode('[hotel_homepage_layout]'); ?>
        <?php endif; ?>
    <?php endwhile; ?>
<?php else : ?>
    <?php echo do_shortcode('[hotel_homepage_layout]'); ?>
<?php endif; ?>

<?php get_footer(); ?>
