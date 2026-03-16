<?php
/**
 * Elementor widgets for hotel homepage sections.
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class Hotel_Booking_Elementor_Base_Widget extends \Elementor\Widget_Base {
    public function get_categories() {
        return array('hotel-booking');
    }

    public function get_keywords() {
        return array('hotel', 'booking', 'rooms', 'hero', 'testimonial');
    }

    protected function card_repeater_controls($default_items) {
        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'icon',
            array(
                'label'   => __('Label', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => '01',
            )
        );

        $repeater->add_control(
            'title',
            array(
                'label'   => __('Title', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => __('Card Title', 'hotel-booking-child'),
            )
        );

        $repeater->add_control(
            'copy',
            array(
                'label'   => __('Description', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => __('Card description.', 'hotel-booking-child'),
            )
        );

        return array(
            'type'        => \Elementor\Controls_Manager::REPEATER,
            'fields'      => $repeater->get_controls(),
            'default'     => $default_items,
            'title_field' => '{{{ title }}}',
        );
    }
}

class Hotel_Booking_Elementor_Hero_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() {
        return 'hotel-hero-booking';
    }

    public function get_title() {
        return __('Hotel Hero Banner', 'hotel-booking-child');
    }

    public function get_icon() {
        return 'eicon-slider-push';
    }

    protected function register_controls() {
        $this->start_controls_section(
            'hero_content',
            array(
                'label' => __('Hero Content', 'hotel-booking-child'),
            )
        );

        $this->add_control(
            'kicker',
            array(
                'label'   => __('Kicker', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::TEXT,
                'default' => 'Boutique Hotel & Apartments',
            )
        );

        $this->add_control(
            'title',
            array(
                'label'   => __('Title', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'Book a refined stay with space, style, and comfort.',
            )
        );

        $this->add_control(
            'description',
            array(
                'label'   => __('Description', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::TEXTAREA,
                'default' => 'A stronger homepage with a proper hotel banner, a clear availability form directly in the hero, and polished sections below to make the site feel like a real booking website.',
            )
        );

        $this->add_control(
            'background_image',
            array(
                'label'   => __('Background Image', 'hotel-booking-child'),
                'type'    => \Elementor\Controls_Manager::MEDIA,
                'default' => array(
                    'url' => get_stylesheet_directory_uri() . '/images/hero-banner.jpg',
                ),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings    = $this->get_settings_for_display();
        $rooms       = get_posts(
            array(
                'post_type'      => 'room',
                'posts_per_page' => -1,
            )
        );
        $background  = !empty($settings['background_image']['url']) ? $settings['background_image']['url'] : '';
        $style_attr  = $background ? ' style="background-image: linear-gradient(180deg, rgba(12, 18, 24, 0.28), rgba(12, 18, 24, 0.68)), url(' . esc_url($background) . '), linear-gradient(135deg, #77614b, #35291f);"' : '';
        ?>
        <section class="hotel-hero hotel-hero--editable"<?php echo $style_attr; ?>>
            <div class="hotel-shell">
                <div class="hotel-hero-intro">
                    <?php if (!empty($settings['kicker'])) : ?>
                        <span class="hotel-kicker"><?php echo esc_html($settings['kicker']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($settings['title'])) : ?>
                        <h1 class="hotel-hero-title"><?php echo esc_html($settings['title']); ?></h1>
                    <?php endif; ?>
                    <?php if (!empty($settings['description'])) : ?>
                        <p class="hotel-hero-description"><?php echo esc_html($settings['description']); ?></p>
                    <?php endif; ?>
                </div>

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
                                    <?php foreach ($rooms as $room) : ?>
                                        <option value="<?php echo esc_attr($room->ID); ?>"><?php echo esc_html($room->post_title); ?></option>
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
    }
}

class Hotel_Booking_Elementor_Story_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() {
        return 'hotel-story-section';
    }

    public function get_title() {
        return __('Hotel Story Section', 'hotel-booking-child');
    }

    public function get_icon() {
        return 'eicon-call-to-action';
    }

    protected function register_controls() {
        $this->start_controls_section('story_main', array('label' => __('Main Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Stay Story'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'A proper hotel booking homepage with stronger first impression.'));
        $this->add_control('description', array('label' => __('Description', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'The layout now leads with a real banner and in-hero booking form, then moves into room presentation, services, guest trust, and destination-focused content like a modern hotel website should.'));
        $this->end_controls_section();

        $repeater = new \Elementor\Repeater();
        $repeater->add_control('value', array('label' => __('Value', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => '24/7'));
        $repeater->add_control('text', array('label' => __('Text', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Guest support and check-in assistance.'));
        $this->start_controls_section('story_highlights', array('label' => __('Highlights', 'hotel-booking-child')));
        $this->add_control(
            'highlights',
            array(
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array(
                    array('value' => '24/7', 'text' => 'Guest support and check-in assistance.'),
                    array('value' => '5 Star', 'text' => 'Luxury hospitality tone across the homepage.'),
                    array('value' => 'Top Rated', 'text' => 'Built to showcase rooms in a premium way.'),
                ),
                'title_field' => '{{{ value }}}',
            )
        );
        $this->end_controls_section();

        $this->start_controls_section('story_side', array('label' => __('Side Card', 'hotel-booking-child')));
        $this->add_control('side_kicker', array('label' => __('Side Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Why It Works'));
        $this->add_control('side_title', array('label' => __('Side Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Better visual hierarchy and clearer conversion path.'));
        $this->add_control('side_description', array('label' => __('Side Description', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Guests see the value proposition first, then the date selector, then the featured rooms and hotel services. That is much closer to a real booking website than the previous basic homepage.'));
        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        ?>
        <section class="hotel-section">
            <div class="hotel-shell">
                <div class="hotel-story-grid">
                    <div class="hotel-story-panel">
                        <span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span>
                        <div class="hotel-heading">
                            <h2><?php echo esc_html($s['title']); ?></h2>
                            <p><?php echo esc_html($s['description']); ?></p>
                        </div>
                        <div class="hotel-story-highlights">
                            <?php foreach ($s['highlights'] as $item) : ?>
                                <div class="hotel-story-highlight">
                                    <strong><?php echo esc_html($item['value']); ?></strong>
                                    <p><?php echo esc_html($item['text']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <aside class="hotel-story-card">
                        <span class="hotel-kicker"><?php echo esc_html($s['side_kicker']); ?></span>
                        <h3><?php echo esc_html($s['side_title']); ?></h3>
                        <p><?php echo esc_html($s['side_description']); ?></p>
                    </aside>
                </div>
            </div>
        </section>
        <?php
    }
}

class Hotel_Booking_Elementor_Featured_Rooms_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() {
        return 'hotel-featured-rooms';
    }

    public function get_title() {
        return __('Hotel Featured Rooms', 'hotel-booking-child');
    }

    public function get_icon() {
        return 'eicon-posts-grid';
    }

    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Featured Rooms'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Elegant spaces designed for short and extended stays.'));
        $this->add_control('description', array('label' => __('Description', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'These cards use your existing room post type, so the homepage stays connected to your current room and booking setup.'));
        $this->add_control('posts_per_page', array('label' => __('Rooms Count', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::NUMBER, 'default' => 3, 'min' => 1, 'max' => 12));
        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $q = new WP_Query(array('post_type' => 'room', 'posts_per_page' => (int) $s['posts_per_page']));
        ?>
        <section class="hotel-section" id="rooms">
            <div class="hotel-shell">
                <div class="hotel-heading">
                    <span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span>
                    <h2><?php echo esc_html($s['title']); ?></h2>
                    <p><?php echo esc_html($s['description']); ?></p>
                </div>
                <div class="hotel-room-grid">
                    <?php if ($q->have_posts()) : ?>
                        <?php while ($q->have_posts()) : $q->the_post(); ?>
                            <?php $price = get_post_meta(get_the_ID(), '_room_price', true); $guests = get_post_meta(get_the_ID(), '_room_max_guests', true); $size = get_post_meta(get_the_ID(), '_room_size', true); ?>
                            <article class="hotel-room-card">
                                <a class="hotel-room-media" href="<?php the_permalink(); ?>">
                                    <?php if (has_post_thumbnail()) : the_post_thumbnail('large'); else : ?><span class="hotel-room-placeholder">Suite</span><?php endif; ?>
                                </a>
                                <div class="hotel-room-body">
                                    <div class="hotel-room-meta">
                                        <?php if ($price) : ?><span><?php echo esc_html('$' . $price . ' /night'); ?></span><?php endif; ?>
                                        <?php if ($guests) : ?><span><?php echo esc_html($guests . ' guests'); ?></span><?php endif; ?>
                                        <?php if ($size) : ?><span><?php echo esc_html($size . ' sq ft'); ?></span><?php endif; ?>
                                    </div>
                                    <h3><?php the_title(); ?></h3>
                                    <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                                    <a class="hotel-room-link" href="<?php the_permalink(); ?>">View room</a>
                                </div>
                            </article>
                        <?php endwhile; wp_reset_postdata(); ?>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        <?php
    }
}

class Hotel_Booking_Elementor_Offers_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() { return 'hotel-offers-section'; }
    public function get_title() { return __('Hotel Offers Section', 'hotel-booking-child'); }
    public function get_icon() { return 'eicon-icon-box'; }
    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Hotel Offers'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Sections below the hero now look more complete and premium.'));
        $this->add_control('description', array('label' => __('Description', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'The homepage now carries the visitor from booking intent into room value, amenities, and guest experience instead of stopping at a few basic blocks.'));
        $this->add_control('cards', $this->card_repeater_controls(array(
            array('icon' => '01', 'title' => 'Luxury Rooms', 'copy' => 'Large, bright suites and apartment-style stays built for comfort, privacy, and longer visits.'),
            array('icon' => '02', 'title' => 'Fine Dining', 'copy' => 'Curated breakfast, chef-led dinners, and a warmer hospitality feel across the entire stay.'),
            array('icon' => '03', 'title' => 'Spa & Wellness', 'copy' => 'Massage, relaxation spaces, and wellness moments that make the property feel complete.'),
        )));
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        ?>
        <section class="hotel-section"><div class="hotel-shell"><div class="hotel-heading"><span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span><h2><?php echo esc_html($s['title']); ?></h2><p><?php echo esc_html($s['description']); ?></p></div><div class="hotel-offer-grid"><?php foreach ($s['cards'] as $card) : ?><article class="hotel-offer-card"><span class="hotel-offer-icon"><?php echo esc_html($card['icon']); ?></span><h3><?php echo esc_html($card['title']); ?></h3><p><?php echo esc_html($card['copy']); ?></p></article><?php endforeach; ?></div></div></section>
        <?php
    }
}

class Hotel_Booking_Elementor_Services_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() { return 'hotel-services-section'; }
    public function get_title() { return __('Hotel Services Section', 'hotel-booking-child'); }
    public function get_icon() { return 'eicon-info-box'; }
    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Services'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Supportive details that make the property feel like a real destination.'));
        $this->add_control('cards', $this->card_repeater_controls(array(
            array('icon' => 'A', 'title' => 'Airport Pickup', 'copy' => 'Smooth arrival support for guests who want a premium start from the moment they land.'),
            array('icon' => 'B', 'title' => 'Private Experiences', 'copy' => 'Tailored local activities, romantic setups, and curated city recommendations.'),
            array('icon' => 'C', 'title' => 'Family Stay Options', 'copy' => 'Flexible layouts, extra bedding, and guest-friendly support for longer family trips.'),
        )));
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        ?>
        <section class="hotel-section"><div class="hotel-shell"><div class="hotel-heading"><span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span><h2><?php echo esc_html($s['title']); ?></h2></div><div class="hotel-service-grid"><?php foreach ($s['cards'] as $card) : ?><article class="hotel-service-card"><span class="hotel-offer-icon"><?php echo esc_html($card['icon']); ?></span><h3><?php echo esc_html($card['title']); ?></h3><p><?php echo esc_html($card['copy']); ?></p></article><?php endforeach; ?></div></div></section>
        <?php
    }
}

class Hotel_Booking_Elementor_Testimonials_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() { return 'hotel-testimonials-section'; }
    public function get_title() { return __('Hotel Testimonials Section', 'hotel-booking-child'); }
    public function get_icon() { return 'eicon-testimonial-carousel'; }
    protected function register_controls() {
        $repeater = new \Elementor\Repeater();
        $repeater->add_control('quote', array('label' => __('Quote', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Guest review text.'));
        $repeater->add_control('name', array('label' => __('Name', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Guest Name'));
        $repeater->add_control('role', array('label' => __('Role', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Recent guest'));
        $this->start_controls_section('content', array('label' => __('Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Guest Reviews'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Trust-building content for the homepage.'));
        $this->add_control(
            'items',
            array(
                'type'        => \Elementor\Controls_Manager::REPEATER,
                'fields'      => $repeater->get_controls(),
                'default'     => array(
                    array('quote' => 'The homepage now feels like a real premium hotel website. The rooms looked stronger and the availability section was much clearer.', 'name' => 'Priya Sharma', 'role' => 'Recent guest'),
                    array('quote' => 'Much better structure than before. The hero section, booking form, and room presentation finally feel polished.', 'name' => 'Daniel Joseph', 'role' => 'Weekend traveller'),
                ),
                'title_field' => '{{{ name }}}',
            )
        );
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        ?>
        <section class="hotel-section"><div class="hotel-shell"><div class="hotel-heading"><span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span><h2><?php echo esc_html($s['title']); ?></h2></div><div class="hotel-testimonial-grid"><?php foreach ($s['items'] as $item) : ?><blockquote class="hotel-testimonial"><p>"<?php echo esc_html($item['quote']); ?>"</p><footer><strong><?php echo esc_html($item['name']); ?></strong><span><?php echo esc_html($item['role']); ?></span></footer></blockquote><?php endforeach; ?></div></div></section>
        <?php
    }
}

class Hotel_Booking_Elementor_Experiences_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() { return 'hotel-experiences-section'; }
    public function get_title() { return __('Hotel Experiences Section', 'hotel-booking-child'); }
    public function get_icon() { return 'eicon-gallery-grid'; }
    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Experiences'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Stronger supporting sections below the room showcase.'));
        $this->add_control('cards', $this->card_repeater_controls(array(
            array('icon' => '', 'title' => 'Rooftop Evenings', 'copy' => 'Sunset views, lounge seating, and a hospitality atmosphere that feels elevated and calm.'),
            array('icon' => '', 'title' => 'Local Discovery', 'copy' => 'Markets, dining streets, and signature places nearby to help the stay feel connected to the destination.'),
            array('icon' => '', 'title' => 'Wellness Mornings', 'copy' => 'Slow breakfast, private balconies, and quiet shared spaces that support restful travel.'),
        )));
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        ?>
        <section class="hotel-section"><div class="hotel-shell"><div class="hotel-heading"><span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span><h2><?php echo esc_html($s['title']); ?></h2></div><div class="hotel-experience-grid"><?php foreach ($s['cards'] as $card) : ?><article class="hotel-experience-card"><h3><?php echo esc_html($card['title']); ?></h3><p><?php echo esc_html($card['copy']); ?></p></article><?php endforeach; ?></div></div></section>
        <?php
    }
}

class Hotel_Booking_Elementor_CTA_Widget extends Hotel_Booking_Elementor_Base_Widget {
    public function get_name() { return 'hotel-cta-section'; }
    public function get_title() { return __('Hotel CTA Section', 'hotel-booking-child'); }
    public function get_icon() { return 'eicon-button'; }
    protected function register_controls() {
        $this->start_controls_section('content', array('label' => __('Content', 'hotel-booking-child')));
        $this->add_control('kicker', array('label' => __('Kicker', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Ready To Book'));
        $this->add_control('title', array('label' => __('Title', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'Bring guests from the banner directly into your booking flow.'));
        $this->add_control('description', array('label' => __('Description', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXTAREA, 'default' => 'The booking form is now placed where users expect it: right in the hero banner.'));
        $this->add_control('button_text', array('label' => __('Button Text', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::TEXT, 'default' => 'Check dates now'));
        $this->add_control('button_link', array('label' => __('Button Link', 'hotel-booking-child'), 'type' => \Elementor\Controls_Manager::URL, 'default' => array('url' => '#availability')));
        $this->end_controls_section();
    }
    protected function render() {
        $s = $this->get_settings_for_display();
        $url = !empty($s['button_link']['url']) ? $s['button_link']['url'] : '#availability';
        ?>
        <section class="hotel-cta">
            <div class="hotel-shell">
                <div class="hotel-cta-box">
                    <div>
                        <span class="hotel-kicker"><?php echo esc_html($s['kicker']); ?></span>
                        <h2><?php echo esc_html($s['title']); ?></h2>
                        <p><?php echo esc_html($s['description']); ?></p>
                    </div>
                    <a class="btn-primary hotel-scroll-link" href="<?php echo esc_url($url); ?>"><?php echo esc_html($s['button_text']); ?></a>
                </div>
            </div>
        </section>
        <?php
    }
}
