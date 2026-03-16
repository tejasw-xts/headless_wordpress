<?php
/**
 * Hotel Booking child theme functions.
 */

function hotel_booking_child_enqueue_assets()
{
    wp_enqueue_style(
        'hotel-booking-parent-style',
        get_template_directory_uri() . '/style.css',
        array(),
        wp_get_theme(get_template())->get('Version')
    );

    wp_enqueue_style(
        'hotel-booking-parent-responsive',
        get_template_directory_uri() . '/css/responsive.css',
        array('hotel-booking-parent-style'),
        wp_get_theme(get_template())->get('Version')
    );

    wp_enqueue_style(
        'hotel-booking-child-style',
        get_stylesheet_uri(),
        array('hotel-booking-parent-style', 'hotel-booking-parent-responsive'),
        wp_get_theme()->get('Version')
    );

    wp_enqueue_style(
        'hotel-booking-child-custom',
        get_stylesheet_directory_uri() . '/css/custom.css',
        array('hotel-booking-child-style'),
        filemtime(get_stylesheet_directory() . '/css/custom.css')
    );

    wp_enqueue_script(
        'hotel-booking-child-custom',
        get_stylesheet_directory_uri() . '/js/custom.js',
        array('jquery', 'hotel-booking-script'),
        filemtime(get_stylesheet_directory() . '/js/custom.js'),
        true
    );

    wp_localize_script(
        'hotel-booking-child-custom',
        'hotelBookingChild',
        array(
            'roomsUrl' => get_post_type_archive_link('room') ? get_post_type_archive_link('room') : home_url('/?post_type=room'),
        )
    );
}
add_action('wp_enqueue_scripts', 'hotel_booking_child_enqueue_assets', 20);

function hotel_booking_child_enqueue_room_admin_assets($hook)
{
    global $post;

    if (!in_array($hook, array('post-new.php', 'post.php'), true))
    {
        return;
    }

    if (!$post || 'room' !== $post->post_type)
    {
        return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
        'hotel-booking-room-admin',
        get_stylesheet_directory_uri() . '/js/room-admin.js',
        array('jquery'),
        filemtime(get_stylesheet_directory() . '/js/room-admin.js'),
        true
    );
}
add_action('admin_enqueue_scripts', 'hotel_booking_child_enqueue_room_admin_assets');

function hotel_booking_child_flush_rewrites_once()
{
    if (get_option('hotel_booking_child_rewrites_flushed'))
    {
        return;
    }

    flush_rewrite_rules(false);
    update_option('hotel_booking_child_rewrites_flushed', 1);
}
add_action('init', 'hotel_booking_child_flush_rewrites_once', 20);

function hotel_booking_child_elementor_support()
{
    add_theme_support('elementor');
    add_theme_support('elementor-pro');
    add_theme_support('elementor-post-thumbnails');
}
add_action('after_setup_theme', 'hotel_booking_child_elementor_support');

function hotel_booking_child_enable_elementor_for_rooms()
{
    add_post_type_support('room', 'elementor');
}
add_action('init', 'hotel_booking_child_enable_elementor_for_rooms', 35);

function hotel_booking_child_add_room_presentation_meta_box()
{
    add_meta_box(
        'hotel_booking_room_presentation',
        __('Room Presentation', 'hotel-booking-child'),
        'hotel_booking_child_room_presentation_meta_box',
        'room',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'hotel_booking_child_add_room_presentation_meta_box');

function hotel_booking_child_room_presentation_meta_box($post)
{
    wp_nonce_field('hotel_booking_child_room_presentation', 'hotel_booking_child_room_presentation_nonce');

    $hotel_name  = get_post_meta($post->ID, '_room_hotel_name', true);
    $location    = get_post_meta($post->ID, '_room_location', true);
    $gallery_ids = get_post_meta($post->ID, '_room_gallery_ids', true);
    $ids         = array_filter(array_map('absint', explode(',', (string) $gallery_ids)));
    ?>
    <p>
        <label for="hotel-room-hotel-name"><?php esc_html_e('Hotel Name', 'hotel-booking-child'); ?></label>
        <input type="text" id="hotel-room-hotel-name" name="room_hotel_name" value="<?php echo esc_attr($hotel_name); ?>"
            style="width:100%;">
    </p>
    <p>
        <label for="hotel-room-location"><?php esc_html_e('Location', 'hotel-booking-child'); ?></label>
        <input type="text" id="hotel-room-location" name="room_location" value="<?php echo esc_attr($location); ?>"
            style="width:100%;">
    </p>
    <p>
        <label><?php esc_html_e('Room Gallery', 'hotel-booking-child'); ?></label>
        <input type="hidden" id="hotel-room-gallery-ids" name="room_gallery_ids"
            value="<?php echo esc_attr($gallery_ids); ?>">
    </p>
    <div id="hotel-room-gallery-preview"
        style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin:12px 0;">
        <?php foreach ($ids as $image_id): ?>
            <?php $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail'); ?>
            <?php if ($thumb_url): ?>
                <img src="<?php echo esc_url($thumb_url); ?>" alt=""
                    style="width:100%;height:72px;object-fit:cover;border-radius:8px;">
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <p style="display:flex;gap:8px;margin:0 0 8px;">
        <button type="button" class="button"
            id="hotel-room-gallery-select"><?php esc_html_e('Select Gallery Images', 'hotel-booking-child'); ?></button>
        <button type="button" class="button"
            id="hotel-room-gallery-clear"><?php esc_html_e('Clear', 'hotel-booking-child'); ?></button>
    </p>
    <p style="margin:0;color:#666;font-size:12px;">
        <?php esc_html_e('Select multiple room images. Featured image stays the main hero image and selected images appear as thumbnails.', 'hotel-booking-child'); ?>
    </p>
    <?php
}

function hotel_booking_child_save_room_presentation_meta($post_id)
{
    if (!isset($_POST['hotel_booking_child_room_presentation_nonce']))
    {
        return;
    }

    if (!wp_verify_nonce($_POST['hotel_booking_child_room_presentation_nonce'], 'hotel_booking_child_room_presentation'))
    {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
    {
        return;
    }

    if (!current_user_can('edit_post', $post_id))
    {
        return;
    }

    if (isset($_POST['room_hotel_name']))
    {
        update_post_meta($post_id, '_room_hotel_name', sanitize_text_field(wp_unslash($_POST['room_hotel_name'])));
    }

    if (isset($_POST['room_location']))
    {
        update_post_meta($post_id, '_room_location', sanitize_text_field(wp_unslash($_POST['room_location'])));
    }

    if (isset($_POST['room_gallery_ids']))
    {
        $raw_ids = explode(',', wp_unslash($_POST['room_gallery_ids']));
        $ids     = array_filter(array_map('absint', $raw_ids));
        update_post_meta($post_id, '_room_gallery_ids', implode(',', $ids));
    }
}
add_action('save_post_room', 'hotel_booking_child_save_room_presentation_meta');

function hotel_booking_child_elementor_page_data()
{
    $layouts = array(
        'hotel-hero-booking',
        'hotel-story-section',
        'hotel-featured-rooms',
        'hotel-offers-section',
        'hotel-services-section',
        'hotel-testimonials-section',
        'hotel-experiences-section',
        'hotel-cta-section',
    );

    $data = array();

    foreach ($layouts as $widget_type)
    {
        $data[] = array(
            'id' => wp_generate_uuid4(),
            'elType' => 'section',
            'settings' => array(
                'layout' => 'full_width',
            ),
            'elements' => array(
                array(
                    'id' => wp_generate_uuid4(),
                    'elType' => 'column',
                    'settings' => array(
                        '_column_size' => 100,
                    ),
                    'elements' => array(
                        array(
                            'id' => wp_generate_uuid4(),
                            'elType' => 'widget',
                            'widgetType' => $widget_type,
                            'settings' => array(),
                            'elements' => array(),
                        ),
                    ),
                    'isInner' => false,
                ),
            ),
            'isInner' => false,
        );
    }

    return $data;
}

function hotel_booking_child_ensure_elementor_homepage()
{
    $home_version = (int) get_option('hotel_booking_child_elementor_home_version', 0);

    if ($home_version >= 2)
    {
        return;
    }

    $home_page = get_page_by_path('home');
    $page_id   = $home_page ? (int) $home_page->ID : 0;

    if (!$page_id)
    {
        $page_id = wp_insert_post(
            array(
                'post_title' => 'Home',
                'post_name' => 'home',
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_content' => '',
            )
        );
    }

    if (!$page_id || is_wp_error($page_id))
    {
        return;
    }

    update_post_meta($page_id, '_wp_page_template', 'template-elementor-fullwidth.php');
    update_option('show_on_front', 'page');
    update_option('page_on_front', $page_id);

    if (did_action('elementor/loaded'))
    {
        update_post_meta($page_id, '_elementor_edit_mode', 'builder');
        update_post_meta($page_id, '_elementor_template_type', 'wp-page');
        update_post_meta($page_id, '_elementor_data', wp_slash(wp_json_encode(hotel_booking_child_elementor_page_data())));
        delete_post_meta($page_id, '_elementor_page_settings');
    }

    update_option('hotel_booking_child_elementor_home_ready', 1);
    update_option('hotel_booking_child_elementor_home_version', 2);
}
add_action('init', 'hotel_booking_child_ensure_elementor_homepage', 30);

function hotel_booking_child_homepage_data()
{
    return array(
        'offer_cards' => array(
            array(
                'icon' => '01',
                'title' => 'Luxury Rooms',
                'copy' => 'Spacious suites and beautifully designed rooms equipped with modern 
facilities, premium bedding, and stylish interiors.',
            ),
            array(
                'icon' => '02',
                'title' => 'Fine Dining',
                'copy' => 'Taste delicious meals prepared by expert chefs featuring local flavors 
and international cuisine in a warm and elegant dining environment.',
            ),
            array(
                'icon' => '03',
                'title' => 'Spa & Wellness',
                'copy' => 'Relax and rejuvenate with our luxury spa services, wellness treatments, 
and calming spaces designed to refresh your body and mind.',
            ),
        ),
        'service_cards' => array(
            array(
                'icon' => 'A',
                'title' => 'Airport Pickup',
                'copy' => 'Convenient airport transfer services ensuring a smooth and comfortable 
arrival experience for all our guests.',
            ),
            array(
                'icon' => 'B',
                'title' => 'Private Experiences',
                'copy' => 'Personalized city tours, curated travel recommendations, and exclusive 
activities designed for memorable experiences.',
            ),
            array(
                'icon' => 'C',
                'title' => 'Family Stay Options',
                'copy' => 'Flexible room layouts, extra beds, and family-friendly services for 
comfortable and enjoyable family vacations.',
            ),
        ),
        'experience_cards' => array(
            array(
                'title' => 'Rooftop Evenings',
                'copy' => 'Enjoy breathtaking sunset views, relaxing lounge seating, and a calm 
atmosphere perfect for unwinding after a long day.',
            ),
            array(
                'title' => 'Local Discovery',
                'copy' => 'Explore nearby attractions, local culture, shopping areas, and 
signature destinations located just minutes away from the hotel.',
            ),
            array(
                'title' => 'Wellness Mornings',
                'copy' => 'Start your day with healthy breakfast options, peaceful balcony views, 
and wellness activities designed to refresh your mind and body.',
            ),
        ),
        'testimonials' => array(
            array(
                'quote' => 'The homepage now feels like a real premium hotel website. The rooms looked stronger and the availability section was much clearer.',
                'name' => 'Priya Sharma',
                'role' => 'Recent guest',
            ),
            array(
                'quote' => 'Much better structure than before. The hero section, booking form, and room presentation finally feel polished.',
                'name' => 'Daniel Joseph',
                'role' => 'Weekend traveller',
            ),
        ),
    );
}

function hotel_booking_child_render_booking_bar()
{
    $rooms = get_posts(
        array(
            'post_type' => 'room',
            'posts_per_page' => -1,
        )
    );

    ob_start();
    ?>
    <section class="hotel-hero">
        <div class="hotel-shell">
            <div class="hero-booking-card" id="availability">
                <form id="availability-form">
                    <div class="booking-form-grid">
                        <div class="form-group">
                            <label for="check-in">Check In</label>
                            <input type="date" id="check-in" name="check_in" required>
                        </div>
                        <div class="form-group">
                            <label for="check-out">Check Out</label>
                            <input type="date" id="check-out" name="check_out" required>
                        </div>
                        <div class="form-group">
                            <label for="guests">Guests</label>
                            <select id="guests" name="guests" required>
                                <option value="1">1 Guest</option>
                                <option value="2">2 Guests</option>
                                <option value="3">3 Guests</option>
                                <option value="4">4 Guests</option>
                                <option value="5">5+ Guests</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="room-type">Room Type</label>
                            <select id="room-type" name="room_type">
                                <option value="">All Rooms</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?php echo esc_attr($room->ID); ?>">
                                        <?php echo esc_html($room->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group form-group--submit">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn-primary">Check Availability</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function hotel_booking_child_render_story_section()
{
    ob_start();
    ?>
    <section class="hotel-section">
        <div class="hotel-shell">
            <div class="hotel-story-grid">
                <div class="hotel-story-panel">
                    <span class="hotel-kicker">Stay Story</span>
                    <div class="hotel-heading">
                        <h2>A proper hotel booking homepage with stronger first impression.</h2>
                        <p>The layout now leads with a real banner and in-hero booking form, then moves into room
                            presentation, services, guest trust, and destination-focused content like a modern hotel website
                            should.</p>
                    </div>

                    <div class="hotel-story-highlights">
                        <div class="hotel-story-highlight">
                            <strong>24/7</strong>
                            <p>Guest support and check-in assistance.</p>
                        </div>
                        <div class="hotel-story-highlight">
                            <strong>5 Star</strong>
                            <p>Luxury hospitality tone across the homepage.</p>
                        </div>
                        <div class="hotel-story-highlight">
                            <strong>Top Rated</strong>
                            <p>Built to showcase rooms in a premium way.</p>
                        </div>
                    </div>
                </div>

                <aside class="hotel-story-card">
                    <span class="hotel-kicker">Why It Works</span>
                    <h3>Better visual hierarchy and clearer conversion path.</h3>
                    <p>Guests see the value proposition first, then the date selector, then the featured rooms and hotel
                        services. That is much closer to a real booking website than the previous basic homepage.</p>
                </aside>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function hotel_booking_child_render_featured_rooms()
{
    $homepage_rooms = new WP_Query(
        array(
            'post_type' => 'room',
            'posts_per_page' => 3,
        )
    );

    ob_start();
    ?>
    <section class="hotel-section" id="rooms">
        <div class="hotel-shell">
            <div class="hotel-heading">
                <span class="hotel-kicker">Featured Rooms</span>
                <h2>Elegant spaces designed for short and extended stays.</h2>
                <p>These cards use your existing room post type, so the homepage stays connected to your current room and
                    booking setup.</p>
            </div>

            <div class="hotel-room-grid">
                <?php if ($homepage_rooms->have_posts()): ?>
                    <?php while ($homepage_rooms->have_posts()):
                        $homepage_rooms->the_post(); ?>
                        <?php
                        $price      = get_post_meta(get_the_ID(), '_room_price', true);
                        $max_guests = get_post_meta(get_the_ID(), '_room_max_guests', true);
                        $size       = get_post_meta(get_the_ID(), '_room_size', true);
                        ?>
                        <article class="hotel-room-card">
                            <a class="hotel-room-media" href="<?php the_permalink(); ?>">
                                <?php if (has_post_thumbnail()): ?>
                                    <?php the_post_thumbnail('large'); ?>
                                <?php else: ?>
                                    <span class="hotel-room-placeholder">Suite</span>
                                <?php endif; ?>
                            </a>
                            <div class="hotel-room-body">
                                <div class="hotel-room-meta">
                                    <?php if ($price): ?>
                                        <span><?php echo esc_html('$' . $price . ' /night'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($max_guests): ?>
                                        <span><?php echo esc_html($max_guests . ' guests'); ?></span>
                                    <?php endif; ?>
                                    <?php if ($size): ?>
                                        <span><?php echo esc_html($size . ' sq ft'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php the_title(); ?></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                                <a class="hotel-room-link" href="<?php the_permalink(); ?>">View room</a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else: ?>
                    <?php foreach (array('Deluxe Suite', 'Family Apartment', 'Executive Residence') as $fallback_room): ?>
                        <article class="hotel-room-card">
                            <div class="hotel-room-media">
                                <span class="hotel-room-placeholder">Suite</span>
                            </div>
                            <div class="hotel-room-body">
                                <div class="hotel-room-meta">
                                    <span>Premium stay</span>
                                    <span>Flexible booking</span>
                                </div>
                                <h3><?php echo esc_html($fallback_room); ?></h3>
                                <p>Add room posts with featured images and pricing to fill this section dynamically.</p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function hotel_booking_child_render_card_section($section_key, $kicker, $title, $copy = '')
{
    $data  = hotel_booking_child_homepage_data();
    $cards = isset($data[$section_key]) ? $data[$section_key] : array();

    if (empty($cards))
    {
        return '';
    }

    $grid_class = 'hotel-offer-grid';
    $card_class = 'hotel-offer-card';

    if ('service_cards' === $section_key)
    {
        $grid_class = 'hotel-service-grid';
        $card_class = 'hotel-service-card';
    } elseif ('experience_cards' === $section_key)
    {
        $grid_class = 'hotel-experience-grid';
        $card_class = 'hotel-experience-card';
    }

    ob_start();
    ?>
    <section class="hotel-section">
        <div class="hotel-shell">
            <div class="hotel-heading">
                <span class="hotel-kicker"><?php echo esc_html($kicker); ?></span>
                <h2><?php echo esc_html($title); ?></h2>
                <?php if ($copy): ?>
                    <p><?php echo esc_html($copy); ?></p>
                <?php endif; ?>
            </div>

            <div class="<?php echo esc_attr($grid_class); ?>">
                <?php foreach ($cards as $card): ?>
                    <article class="<?php echo esc_attr($card_class); ?>">
                        <?php if (!empty($card['icon'])): ?>
                            <span class="hotel-offer-icon"><?php echo esc_html($card['icon']); ?></span>
                        <?php endif; ?>
                        <h3><?php echo esc_html($card['title']); ?></h3>
                        <p><?php echo esc_html($card['copy']); ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function hotel_booking_child_render_testimonials()
{
    $data         = hotel_booking_child_homepage_data();
    $testimonials = $data['testimonials'];

    ob_start();
    ?>
    <section class="hotel-section">
        <div class="hotel-shell">
            <div class="hotel-heading">
                <span class="hotel-kicker">Guest Reviews</span>
                <h2>Trust-building content for the homepage.</h2>
            </div>

            <div class="hotel-testimonial-grid">
                <?php foreach ($testimonials as $testimonial): ?>
                    <blockquote class="hotel-testimonial">
                        <p>"<?php echo esc_html($testimonial['quote']); ?>"</p>
                        <footer>
                            <strong><?php echo esc_html($testimonial['name']); ?></strong>
                            <span><?php echo esc_html($testimonial['role']); ?></span>
                        </footer>
                    </blockquote>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function hotel_booking_child_render_cta()
{
    ob_start();
    ?>
    <section class="hotel-cta">
        <div class="hotel-shell">
            <div class="hotel-cta-box">
                <div>
                    <span class="hotel-kicker">Ready To Book</span>
                    <h2>Bring guests from the banner directly into your booking flow.</h2>
                    <p>The booking form is now placed where users expect it: right in the hero banner.</p>
                </div>
                <a class="btn-primary hotel-scroll-link" href="#availability">Check dates now</a>
            </div>
        </div>
    </section>
    <?php

    return ob_get_clean();
}

function hotel_booking_child_shortcode_booking_bar()
{
    return hotel_booking_child_render_booking_bar();
}
add_shortcode('hotel_booking_bar', 'hotel_booking_child_shortcode_booking_bar');

function hotel_booking_child_shortcode_story()
{
    return hotel_booking_child_render_story_section();
}
add_shortcode('hotel_story_section', 'hotel_booking_child_shortcode_story');

function hotel_booking_child_shortcode_rooms()
{
    return hotel_booking_child_render_featured_rooms();
}
add_shortcode('hotel_featured_rooms', 'hotel_booking_child_shortcode_rooms');

function hotel_booking_child_shortcode_offers()
{
    return hotel_booking_child_render_card_section(
        'offer_cards',
        'Hotel Offers',
        'Sections below the hero now look more complete and premium.',
        'The homepage now carries the visitor from booking intent into room value, amenities, and guest experience instead of stopping at a few basic blocks.'
    );
}
add_shortcode('hotel_offer_section', 'hotel_booking_child_shortcode_offers');

function hotel_booking_child_shortcode_services()
{
    return hotel_booking_child_render_card_section(
        'service_cards',
        'Services',
        'Supportive details that make the property feel like a real destination.'
    );
}
add_shortcode('hotel_service_section', 'hotel_booking_child_shortcode_services');

function hotel_booking_child_shortcode_experiences()
{
    return hotel_booking_child_render_card_section(
        'experience_cards',
        'Experiences',
        'Stronger supporting sections below the room showcase.'
    );
}
add_shortcode('hotel_experience_section', 'hotel_booking_child_shortcode_experiences');

function hotel_booking_child_shortcode_testimonials()
{
    return hotel_booking_child_render_testimonials();
}
add_shortcode('hotel_testimonial_section', 'hotel_booking_child_shortcode_testimonials');

function hotel_booking_child_shortcode_cta()
{
    return hotel_booking_child_render_cta();
}
add_shortcode('hotel_cta_section', 'hotel_booking_child_shortcode_cta');

function hotel_booking_child_shortcode_homepage()
{
    ob_start();
    ?>
    <main class="site-main hotel-home">
        <?php echo do_shortcode('[hotel_booking_bar]'); ?>
        <?php echo do_shortcode('[hotel_story_section]'); ?>
        <?php echo do_shortcode('[hotel_featured_rooms]'); ?>
        <?php echo do_shortcode('[hotel_offer_section]'); ?>
        <?php echo do_shortcode('[hotel_service_section]'); ?>
        <?php echo do_shortcode('[hotel_testimonial_section]'); ?>
        <?php echo do_shortcode('[hotel_experience_section]'); ?>
        <?php echo do_shortcode('[hotel_cta_section]'); ?>
    </main>
    <?php

    return ob_get_clean();
}
add_shortcode('hotel_homepage_layout', 'hotel_booking_child_shortcode_homepage');

function hotel_booking_child_register_elementor_category($elements_manager)
{
    $elements_manager->add_category(
        'hotel-booking',
        array(
            'title' => __('Hotel Booking', 'hotel-booking-child'),
            'icon' => 'fa fa-hotel',
        )
    );
}
add_action('elementor/elements/categories_registered', 'hotel_booking_child_register_elementor_category');

function hotel_booking_child_register_elementor_widgets($widgets_manager)
{
    require_once get_stylesheet_directory() . '/inc/elementor-hotel-section-widget.php';

    $widgets_manager->register(new \Hotel_Booking_Elementor_Hero_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_Story_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_Featured_Rooms_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_Offers_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_Services_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_Testimonials_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_Experiences_Widget());
    $widgets_manager->register(new \Hotel_Booking_Elementor_CTA_Widget());
}
add_action('elementor/widgets/register', 'hotel_booking_child_register_elementor_widgets');

function hotel_booking_child_enable_room_rest_api($args, $post_type)
{
    if ('room' !== $post_type)
    {
        return $args;
    }

    $args['show_in_rest'] = true;
    $args['rest_base']    = 'rooms';

    return $args;
}
add_filter('register_post_type_args', 'hotel_booking_child_enable_room_rest_api', 10, 2);

function hotel_booking_child_register_room_rest_meta()
{
    $fields = array(
        'room_price' => '_room_price',
        'room_max_guests' => '_room_max_guests',
        'room_size' => '_room_size',
        'room_beds' => '_room_beds',
        'room_hotel_name' => '_room_hotel_name',
        'room_location' => '_room_location',
        'room_gallery_ids' => '_room_gallery_ids',
    );

    foreach ($fields as $rest_key => $meta_key)
    {
        register_post_meta(
            'room',
            $meta_key,
            array(
                'show_in_rest' => array(
                    'schema' => array(
                        'type' => 'string',
                    ),
                ),
                'single' => true,
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback' => '__return_true',
            )
        );
    }
}
add_action('init', 'hotel_booking_child_register_room_rest_meta', 40);

function hotel_booking_child_prepare_room_gallery_rest_field($post_arr)
{
    $post_id     = isset($post_arr['id']) ? absint($post_arr['id']) : 0;
    $gallery_raw = get_post_meta($post_id, '_room_gallery_ids', true);
    $gallery_ids = array_filter(array_map('absint', explode(',', (string) $gallery_raw)));
    $images      = array();

    $featured_id = get_post_thumbnail_id($post_id);

    if ($featured_id)
    {
        array_unshift($gallery_ids, $featured_id);
        $gallery_ids = array_values(array_unique($gallery_ids));
    }

    foreach ($gallery_ids as $image_id)
    {
        $large_url = wp_get_attachment_image_url($image_id, 'large');
        $thumb_url = wp_get_attachment_image_url($image_id, 'thumbnail');

        if (!$large_url)
        {
            continue;
        }

        $images[] = array(
            'id' => $image_id,
            'large' => $large_url,
            'thumb' => $thumb_url ? $thumb_url : $large_url,
            'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true) ?: get_the_title($post_id),
        );
    }

    return $images;
}

function hotel_booking_child_register_room_rest_fields()
{
    $scalar_fields = array(
        'room_price' => '_room_price',
        'room_max_guests' => '_room_max_guests',
        'room_size' => '_room_size',
        'room_beds' => '_room_beds',
        'room_hotel_name' => '_room_hotel_name',
        'room_location' => '_room_location',
    );

    foreach ($scalar_fields as $field_name => $meta_key)
    {
        register_rest_field(
            'room',
            $field_name,
            array(
                'get_callback' => function ($post_arr) use ($meta_key) {
                    return get_post_meta(absint($post_arr['id']), $meta_key, true);
                },
                'schema' => array(
                    'description' => __('Room field.', 'hotel-booking-child'),
                    'type' => 'string',
                    'context' => array('view', 'edit'),
                ),
            )
        );
    }

    register_rest_field(
        'room',
        'room_gallery',
        array(
            'get_callback' => 'hotel_booking_child_prepare_room_gallery_rest_field',
            'schema' => array(
                'description' => __('Room gallery images.', 'hotel-booking-child'),
                'type' => 'array',
                'context' => array('view', 'edit'),
            ),
        )
    );
}
add_action('rest_api_init', 'hotel_booking_child_register_room_rest_fields');

function hotel_booking_child_allowed_headless_origins()
{
    return apply_filters(
        'hotel_booking_child_allowed_headless_origins',
        array(
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://172.16.60.17:5173',
        )
    );
}

function hotel_booking_child_send_rest_cors_headers($served, $result, $request, $server)
{
    $origin  = get_http_origin();
    $allowed = hotel_booking_child_allowed_headless_origins();

    if ($origin && in_array($origin, $allowed, true))
    {
        header('Access-Control-Allow-Origin: ' . esc_url_raw($origin));
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        header('Vary: Origin', false);
    }

    if ('OPTIONS' === $_SERVER['REQUEST_METHOD'])
    {
        status_header(200);
        exit;
    }

    return $served;
}
add_filter('rest_pre_serve_request', 'hotel_booking_child_send_rest_cors_headers', 10, 4);

function hotel_booking_child_prepare_room_api_data($post)
{
    $featured_image = get_the_post_thumbnail_url($post, 'large');

    return array(
        'id' => (int) $post->ID,
        'slug' => $post->post_name,
        'title' => get_the_title($post),
        'excerpt' => has_excerpt($post) ? get_the_excerpt($post) : wp_trim_words(wp_strip_all_tags($post->post_content), 28),
        'content' => apply_filters('the_content', $post->post_content),
        'link' => get_permalink($post),
        'featuredImage' => $featured_image ? $featured_image : '',
        'price' => get_post_meta($post->ID, '_room_price', true),
        'maxGuests' => get_post_meta($post->ID, '_room_max_guests', true),
        'size' => get_post_meta($post->ID, '_room_size', true),
        'beds' => get_post_meta($post->ID, '_room_beds', true),
    );
}

function hotel_booking_child_headless_homepage_response()
{
    $rooms = get_posts(
        array(
            'post_type' => 'room',
            'post_status' => 'publish',
            'posts_per_page' => 6,
        )
    );

    return rest_ensure_response(
        array(
            'site' => array(
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'url' => home_url('/'),
            ),
            'hero' => array(
                'eyebrow' => 'Premium Harbor Stay Hotel',
                'title' => 'Luxury Hotel Rooms & Comfortable Stays in the Heart of the City',
                'description' => 'Experience a perfect blend of comfort, luxury, and convenience at Harbor Stay. 
Our modern hotel rooms, premium facilities, and personalized services ensure 
a relaxing stay whether you are traveling for business or leisure.',
            ),
            'sections' => hotel_booking_child_homepage_data(),
            'rooms' => array_map('hotel_booking_child_prepare_room_api_data', $rooms),
        )
    );
}

function hotel_booking_child_create_booking_via_rest(WP_REST_Request $request)
{
    $params = $request->get_json_params();

    // Optional: require login for real booking workflow.
    // Enable by adding `define('HOTEL_BOOKING_REQUIRE_LOGIN', true);` in wp-config.php.
    if (defined('HOTEL_BOOKING_REQUIRE_LOGIN') && HOTEL_BOOKING_REQUIRE_LOGIN && !is_user_logged_in())
    {
        return new WP_Error(
            'hotel_booking_auth_required',
            __('Please log in to place a booking.', 'hotel-booking-child'),
            array('status' => 401)
        );
    }

    $check_in  = isset($params['check_in']) ? sanitize_text_field($params['check_in']) : '';
    $check_out = isset($params['check_out']) ? sanitize_text_field($params['check_out']) : '';
    $guests    = isset($params['guests']) ? sanitize_text_field($params['guests']) : '';
    $room_id   = isset($params['room_id']) ? absint($params['room_id']) : 0;
    $name      = isset($params['name']) ? sanitize_text_field($params['name']) : '';
    $email     = isset($params['email']) ? sanitize_email($params['email']) : '';
    $phone     = isset($params['phone']) ? sanitize_text_field($params['phone']) : '';

    if (!$check_in || !$check_out || !$guests || !$room_id || !$name || !$email)
    {
        return new WP_Error(
            'hotel_booking_missing_fields',
            __('Missing required booking fields.', 'hotel-booking-child'),
            array('status' => 400)
        );
    }

    $room = get_post($room_id);

    if (!$room || 'room' !== $room->post_type || 'publish' !== $room->post_status)
    {
        return new WP_Error(
            'hotel_booking_invalid_room',
            __('The selected room does not exist.', 'hotel-booking-child'),
            array('status' => 404)
        );
    }

    $booking_id = wp_insert_post(
        array(
            'post_title' => sprintf('Booking - %s - %s', $name, current_time('mysql')),
            'post_type' => 'booking',
            'post_status' => 'publish',
        ),
        true
    );

    if (is_wp_error($booking_id))
    {
        return new WP_Error(
            'hotel_booking_create_failed',
            __('Booking could not be created.', 'hotel-booking-child'),
            array('status' => 500)
        );
    }

    update_post_meta($booking_id, '_booking_check_in', $check_in);
    update_post_meta($booking_id, '_booking_check_out', $check_out);
    update_post_meta($booking_id, '_booking_guests', $guests);
    update_post_meta($booking_id, '_booking_room_id', $room_id);
    update_post_meta($booking_id, '_booking_name', $name);
    update_post_meta($booking_id, '_booking_email', $email);
    update_post_meta($booking_id, '_booking_phone', $phone);
    update_post_meta($booking_id, '_booking_status', 'new');

    // If the request is authenticated (same-site cookie auth), link booking to the WP user.
    if (is_user_logged_in())
    {
        $user_id = (int) get_current_user_id();
        if ($user_id)
        {
            update_post_meta($booking_id, '_booking_user_id', $user_id);
            wp_update_post(array(
                'ID' => $booking_id,
                'post_author' => $user_id,
            ));
        }
    }

    // Optional: if the bookings table plugin is active, sync this booking into the custom table.
    if (function_exists('hotel_booking_bookings_table_upsert_for_post'))
    {
        hotel_booking_bookings_table_upsert_for_post($booking_id);
    }

    /**
     * Let the booking workflow plugin send initial emails immediately now that meta is saved.
     * This avoids WP-Cron delays and prevents "blank details" emails.
     */
    do_action('hotel_booking_booking_created', $booking_id, 'rest');

    return rest_ensure_response(
        array(
            'success' => true,
            'booking_id' => (int) $booking_id,
            'message' => __('Booking successful.', 'hotel-booking-child'),
        )
    );
}

function hotel_booking_child_register_headless_routes()
{
    register_rest_route(
        'hotel-booking/v1',
        '/homepage',
        array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => 'hotel_booking_child_headless_homepage_response',
            'permission_callback' => '__return_true',
        )
    );

    register_rest_route(
        'hotel-booking/v1',
        '/booking',
        array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => 'hotel_booking_child_create_booking_via_rest',
            'permission_callback' => '__return_true',
        )
    );
}
add_action('rest_api_init', 'hotel_booking_child_register_headless_routes');

function hotel_booking_child_get_booking_room_label($room_id)
{
    $room_id = absint($room_id);

    if (!$room_id)
    {
        return '';
    }

    $room = get_post($room_id);

    if (!$room || 'room' !== $room->post_type)
    {
        return '';
    }

    return get_the_title($room);
}

function hotel_booking_child_booking_admin_columns($columns)
{
    $new_columns = array();

    foreach ($columns as $key => $label)
    {
        $new_columns[$key] = $label;

        if ('title' === $key)
        {
            $new_columns['booking_room'] = __('Room', 'hotel-booking-child');
            $new_columns['booking_check_in'] = __('Check-in', 'hotel-booking-child');
            $new_columns['booking_check_out'] = __('Check-out', 'hotel-booking-child');
            $new_columns['booking_guests'] = __('Guests', 'hotel-booking-child');
            $new_columns['booking_name'] = __('Name', 'hotel-booking-child');
            $new_columns['booking_email'] = __('Email', 'hotel-booking-child');
            $new_columns['booking_phone'] = __('Phone', 'hotel-booking-child');
        }
    }

    return $new_columns;
}
add_filter('manage_booking_posts_columns', 'hotel_booking_child_booking_admin_columns');

function hotel_booking_child_booking_admin_column_values($column, $post_id)
{
    if ('booking_room' === $column)
    {
        $room_id = (int) get_post_meta($post_id, '_booking_room_id', true);
        $label = hotel_booking_child_get_booking_room_label($room_id);

        if (!$room_id || !$label)
        {
            echo '—';
            return;
        }

        $edit_link = get_edit_post_link($room_id);

        if ($edit_link)
        {
            printf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($label));
            return;
        }

        echo esc_html($label);
        return;
    }

    if ('booking_check_in' === $column)
    {
        $value = (string) get_post_meta($post_id, '_booking_check_in', true);
        echo $value ? esc_html($value) : '—';
        return;
    }

    if ('booking_check_out' === $column)
    {
        $value = (string) get_post_meta($post_id, '_booking_check_out', true);
        echo $value ? esc_html($value) : '—';
        return;
    }

    if ('booking_guests' === $column)
    {
        $value = (string) get_post_meta($post_id, '_booking_guests', true);
        echo $value ? esc_html($value) : '—';
        return;
    }

    if ('booking_name' === $column)
    {
        $value = (string) get_post_meta($post_id, '_booking_name', true);
        echo $value ? esc_html($value) : '—';
        return;
    }

    if ('booking_email' === $column)
    {
        $value = (string) get_post_meta($post_id, '_booking_email', true);
        echo $value ? esc_html($value) : '—';
        return;
    }

    if ('booking_phone' === $column)
    {
        $value = (string) get_post_meta($post_id, '_booking_phone', true);
        echo $value ? esc_html($value) : '—';
        return;
    }
}
add_action('manage_booking_posts_custom_column', 'hotel_booking_child_booking_admin_column_values', 10, 2);

function hotel_booking_child_booking_meta_box_html($post)
{
    $check_in  = (string) get_post_meta($post->ID, '_booking_check_in', true);
    $check_out = (string) get_post_meta($post->ID, '_booking_check_out', true);
    $guests    = (string) get_post_meta($post->ID, '_booking_guests', true);
    $room_id   = (int) get_post_meta($post->ID, '_booking_room_id', true);
    $name      = (string) get_post_meta($post->ID, '_booking_name', true);
    $email     = (string) get_post_meta($post->ID, '_booking_email', true);
    $phone     = (string) get_post_meta($post->ID, '_booking_phone', true);
    $room_name = hotel_booking_child_get_booking_room_label($room_id);

    echo '<div style="display:grid;gap:10px;">';
    echo '<div><strong>' . esc_html__('Room', 'hotel-booking-child') . ':</strong> ' . ($room_name ? esc_html($room_name) : '—') . '</div>';
    echo '<div><strong>' . esc_html__('Check-in', 'hotel-booking-child') . ':</strong> ' . ($check_in ? esc_html($check_in) : '—') . '</div>';
    echo '<div><strong>' . esc_html__('Check-out', 'hotel-booking-child') . ':</strong> ' . ($check_out ? esc_html($check_out) : '—') . '</div>';
    echo '<div><strong>' . esc_html__('Guests', 'hotel-booking-child') . ':</strong> ' . ($guests ? esc_html($guests) : '—') . '</div>';
    echo '<hr style="border:0;border-top:1px solid #e5e5e5;">';
    echo '<div><strong>' . esc_html__('Name', 'hotel-booking-child') . ':</strong> ' . ($name ? esc_html($name) : '—') . '</div>';
    echo '<div><strong>' . esc_html__('Email', 'hotel-booking-child') . ':</strong> ' . ($email ? esc_html($email) : '—') . '</div>';
    echo '<div><strong>' . esc_html__('Phone', 'hotel-booking-child') . ':</strong> ' . ($phone ? esc_html($phone) : '—') . '</div>';
    echo '</div>';
}

function hotel_booking_child_register_booking_meta_box()
{
    add_meta_box(
        'hotel_booking_booking_details',
        __('Booking Details', 'hotel-booking-child'),
        'hotel_booking_child_booking_meta_box_html',
        'booking',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes_booking', 'hotel_booking_child_register_booking_meta_box');
