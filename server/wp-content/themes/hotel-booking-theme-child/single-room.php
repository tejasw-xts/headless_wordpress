<?php
get_header();

while (have_posts()) :
    the_post();

    $post_id    = get_the_ID();
    $price      = get_post_meta($post_id, '_room_price', true);
    $max_guests = get_post_meta($post_id, '_room_max_guests', true);
    $size       = get_post_meta($post_id, '_room_size', true);
    $beds       = get_post_meta($post_id, '_room_beds', true);
    $hotel_name = get_post_meta($post_id, '_room_hotel_name', true);
    $location   = get_post_meta($post_id, '_room_location', true);
    $gallery    = get_post_meta($post_id, '_room_gallery_ids', true);
    $gallery_ids = array_filter(array_map('absint', explode(',', (string) $gallery)));
    $image_ids   = array();

    if (has_post_thumbnail()) {
        $image_ids[] = get_post_thumbnail_id($post_id);
    }

    foreach ($gallery_ids as $gallery_id) {
        if (!in_array($gallery_id, $image_ids, true)) {
            $image_ids[] = $gallery_id;
        }
    }

    $room_images = array();

    foreach ($image_ids as $image_id) {
        $large_url = wp_get_attachment_image_url($image_id, 'large');
        $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail');

        if (!$large_url) {
            continue;
        }

        $room_images[] = array(
            'large' => $large_url,
            'thumb' => $thumb_url ? $thumb_url : $large_url,
            'alt'   => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id),
        );
    }

    $primary_image = !empty($room_images) ? $room_images[0] : null;

    $elementor_document = null;
    $elementor_active   = false;

    if (did_action('elementor/loaded')) {
        $elementor_document = \Elementor\Plugin::$instance->documents->get($post_id);
        $elementor_active   = $elementor_document && $elementor_document->is_built_with_elementor();
    }
    ?>

    <main id="primary" class="site-main hotel-room-single-page">
        <?php if ($elementor_active) : ?>
            <div class="hotel-shell hotel-room-elementor-content">
                <?php the_content(); ?>
            </div>
        <?php else : ?>
            <article <?php post_class('hotel-room-single'); ?>>
                <section class="hotel-room-hero">
                    <div class="hotel-shell hotel-room-hero__grid">
                        <div class="hotel-room-gallery-card">
                            <div class="hotel-room-gallery-main">
                                <?php if ($primary_image) : ?>
                                    <img
                                        id="hotel-room-main-image"
                                        src="<?php echo esc_url($primary_image['large']); ?>"
                                        alt="<?php echo esc_attr($primary_image['alt']); ?>"
                                    >
                                <?php else : ?>
                                    <div class="hotel-room-hero__placeholder">Room Preview</div>
                                <?php endif; ?>
                            </div>

                            <?php if (count($room_images) > 1) : ?>
                                <div class="hotel-room-thumbnails">
                                    <?php foreach ($room_images as $index => $room_image) : ?>
                                        <button
                                            type="button"
                                            class="hotel-room-thumb<?php echo 0 === $index ? ' is-active' : ''; ?>"
                                            data-full-image="<?php echo esc_url($room_image['large']); ?>"
                                            data-alt="<?php echo esc_attr($room_image['alt']); ?>"
                                        >
                                            <img src="<?php echo esc_url($room_image['thumb']); ?>" alt="<?php echo esc_attr($room_image['alt']); ?>">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <aside class="hotel-room-summary-card">
                            <span class="hotel-kicker">Room Detail</span>
                            <p class="hotel-room-summary-card__hotel"><?php echo esc_html($hotel_name ? $hotel_name : get_bloginfo('name')); ?></p>
                            <h1><?php the_title(); ?></h1>
                            <p class="hotel-room-summary-card__location"><?php echo esc_html($location ? $location : 'Add location in Room Presentation'); ?></p>

                            <div class="hotel-room-summary-card__price">
                                <?php echo esc_html($price ? '$' . $price . ' / night' : 'Price on request'); ?>
                            </div>

                            <div class="hotel-room-summary-card__meta">
                                <span><?php echo esc_html($max_guests ? $max_guests . ' guests' : 'N/A guests'); ?></span>
                                <span><?php echo esc_html($beds ? $beds . ' beds' : 'N/A beds'); ?></span>
                                <span><?php echo esc_html($size ? $size . ' sq ft' : 'N/A sq ft'); ?></span>
                            </div>

                            <p class="hotel-room-summary-card__excerpt">
                                <?php echo esc_html(get_the_excerpt() ? get_the_excerpt() : 'Update the excerpt to show a short room summary here.'); ?>
                            </p>

                            <a class="btn-primary hotel-room-summary-card__cta" href="#room-booking-panel">Book this room</a>
                        </aside>
                    </div>
                </section>

                <section class="hotel-section">
                    <div class="hotel-shell hotel-room-layout">
                        <div class="hotel-room-content-card">
                            <div class="hotel-heading">
                                <span class="hotel-kicker">Room Overview</span>
                                <h2>Crafted for a premium guest experience.</h2>
                            </div>

                            <div class="hotel-room-richtext">
                                <?php the_content(); ?>
                            </div>

                            <div class="hotel-room-amenities">
                                <div class="hotel-room-amenity">Private bath</div>
                                <div class="hotel-room-amenity">Fast WiFi</div>
                                <div class="hotel-room-amenity">Air conditioning</div>
                                <div class="hotel-room-amenity">Breakfast access</div>
                                <div class="hotel-room-amenity">Smart TV</div>
                                <div class="hotel-room-amenity">Daily housekeeping</div>
                            </div>
                        </div>

                        <aside class="hotel-room-booking-card" id="room-booking-panel">
                            <span class="hotel-kicker">Reserve This Room</span>
                            <h3>Send a booking request</h3>
                            <form class="hotel-room-booking-form" id="room-booking-form">
                                <input type="hidden" name="room_id" value="<?php echo esc_attr($post_id); ?>">

                                <label>
                                    Check in
                                    <input type="date" name="check_in" required>
                                </label>

                                <label>
                                    Check out
                                    <input type="date" name="check_out" required>
                                </label>

                                <label>
                                    Guests
                                    <select name="guests" required>
                                        <option value="1">1 Guest</option>
                                        <option value="2">2 Guests</option>
                                        <option value="3">3 Guests</option>
                                        <option value="4">4 Guests</option>
                                        <option value="5">5 Guests</option>
                                    </select>
                                </label>

                                <label>
                                    Full name
                                    <input type="text" name="name" required>
                                </label>

                                <label>
                                    Email
                                    <input type="email" name="email" required>
                                </label>

                                <label>
                                    Phone
                                    <input type="tel" name="phone" required>
                                </label>

                                <button class="btn-primary" type="submit">Complete booking</button>
                            </form>
                            <div id="booking-message"></div>
                        </aside>
                    </div>
                </section>
            </article>
        <?php endif; ?>
    </main>
    <?php
endwhile;

get_footer();
