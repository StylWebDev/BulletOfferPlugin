<?php
/*
Plugin Name: Bullet Offer Popup
Description: Displays a scroll-triggered popup with product offers based on user activity and admin settings.
Version: 1.0
Author: Konstantinos Stylianou
*/

if (!defined('ABSPATH')) exit;

class BulletOfferPopup {

    public function __construct() {
        add_action('admin_menu', array($this, 'create_settings_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'inject_popup_html'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('woocommerce_before_calculate_totals', array($this, 'apply_bullet_offer_discount'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'mark_bullet_offer_cart_item'), 10, 3);

    }

    public function create_settings_menu() {
        add_options_page(
            'Bullet Offer Popup Settings',
            'Bullet Offer Popup',
            'manage_options',
            'bullet-offer-popup',
            array($this, 'settings_page_html')
        );
    }

    public function register_settings() {
        register_setting('bullet_offer_settings', 'bop_scroll_trigger');
        register_setting('bullet_offer_settings', 'bop_display_time');
        register_setting('bullet_offer_settings', 'bop_message');
/*         register_setting('bullet_offer_settings', 'bop_product_id'); */
        register_setting('bullet_offer_settings', 'bop_discount');
        register_setting('bullet_offer_settings', 'bop_popup_width');
        register_setting('bullet_offer_settings', 'bop_popup_height');
        register_setting('bullet_offer_settings', 'bop_cooldown');
    }

    public function settings_page_html() {
        ?>
        <div class="wrap">
            <h1>Bullet Offer Popup Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bullet_offer_settings');
                do_settings_sections('bullet_offer_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Scroll Trigger (px)</th>
                        <td><input type="number" name="bop_scroll_trigger" value="<?php echo esc_attr(get_option('bop_scroll_trigger', 300)); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Display Time (seconds)</th>
                        <td><input type="number" name="bop_display_time" value="<?php echo esc_attr(get_option('bop_display_time', 10)); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Popup Message</th>
                        <td><input type="text" name="bop_message" value="<?php echo esc_attr(get_option('bop_message')); ?>" size="50" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Discount Text</th>
                        <td><input type="text" name="bop_discount" value="<?php echo esc_attr(get_option('bop_discount')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Popup Width (px)</th>
                        <td><input type="number" name="bop_popup_width" value="<?php echo esc_attr(get_option('bop_popup_width', 400)); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Cooldown Period (hours)</th>
                        <td><input type="number" name="bop_cooldown" value="<?php echo esc_attr(get_option('bop_cooldown', 24)); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('bop-style', plugins_url('css/bop-styles.css', __FILE__));

        if (!is_product_category()) return;

        $queried_category = get_queried_object();

        $product_id = 0;
        $image_url = '';
        $title = '';

        if ($queried_category && isset($queried_category->term_id)) {
            $product_props = array(
                'post_type' => 'product',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field' => 'term_id',
                        'terms' => $queried_category->term_id,
                    ),
                ),
                'meta_query' => array(
                    array(
                        'key' => '_stock',
                        'value' => 5,
                        'compare' => '>=',
                        'type' => 'NUMERIC',
                    ),
                    array(
                        'key' => '_stock_status',
                        'value' => 'instock',
                    ),
                ),
            );

            $products = get_posts($product_props);
            if (!empty($products)) {
                $random_product = $products[array_rand($products)];
                $product_id = $random_product->ID;
                $title = get_the_title($product_id);
                $image_url = get_the_post_thumbnail_url($product_id, 'thumbnail');
            }
        }

        wp_enqueue_script('jquery');

        wp_enqueue_script(
            'bop-script',
            plugins_url('js/bop-script.js', __FILE__),
            array('jquery'),
            '1.1',
            true
        );

        wp_localize_script('bop-script', 'bopData', array(
            'scrollTrigger' => get_option('bop_scroll_trigger', 300),
            'displayTime' => get_option('bop_display_time', 10),
            'message' => get_option('bop_message'),
            'discount' => get_option('bop_discount'),
            'productID' => $product_id,
            'width' => get_option('bop_popup_width', 400),
            'image' => $image_url,
            'title' => $title,
            'siteUrl' => 'http://stylcommerce.local/checkout',
            'cooldown' => get_option('bop_cooldown', 24)
        ));
    }

    public function apply_bullet_offer_discount($cart) {
        if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) return;

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            // Apply discount if cart came from bullet offer link
            if (!empty($cart_item['bullet_offer'])) {
                $original_price = $cart_item['data']->get_price();
                $discounted_price = $original_price * 0.90; // 10% off
                $cart_item['data']->set_price($discounted_price);
            }
        }
    }

    public function mark_bullet_offer_cart_item($cart_item_data, $product_id, $variation_id) {
        if (isset($_GET['bullet_offer']) && $_GET['bullet_offer'] == '1') {
            $cart_item_data['bullet_offer'] = true;
        }
        return $cart_item_data;
    }

    public function inject_popup_html() {
        echo '<div id="bop-popup">
                <div id="bop-close" style="position:absolute;top:5px;right:10px;cursor:pointer;font-weight:bold;">
                    <img id="close_btn" src="https://api.iconify.design/material-symbols:cancel.svg?color=%23ed0707" width="20px" alt="close_btn" />
                    </div>
                <div id="bop-content"></div>
              </div>';
    }
}

new BulletOfferPopup();
