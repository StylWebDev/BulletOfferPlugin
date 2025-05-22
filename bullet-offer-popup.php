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

        // there is a problem with this section.
        add_action('woocommerce_before_calculate_totals', function($cart) {
            if (is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) return;
            if (!isset($_COOKIE['bullet_offer']) || $_COOKIE['bullet_offer'] !== '1') return;

            $discount_percent = (float) get_option('bop_discount_percentage', 10);

            foreach ($cart->get_cart() as $cart_item) {
                if ($cart_item['data'] instanceof WC_Product) {
                    $original_price = $cart_item['data']->get_regular_price();
                    $discounted_price = $original_price * ((100 - $discount_percent) / 100);
                    $cart_item['data']->set_price($discounted_price);
                }
            }
        });

        add_action('template_redirect', function() {
            if (is_checkout() && isset($_COOKIE['bullet_offer']) && $_COOKIE['bullet_offer'] === '1') {
                WC()->cart->calculate_totals();
            }
        });


        add_action('woocommerce_thankyou', function() {
            setcookie('bullet_offer', '', time() - 3600, '/');
        });

        add_filter('woocommerce_add_to_cart_redirect', function($url) {
            if (isset($_GET['bullet_offer']) && $_GET['bullet_offer'] === '1') {
                return wc_get_checkout_url();
            }
            return $url;
        });

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
        register_setting('bullet_offer_settings', 'bop_discount');
        register_setting('bullet_offer_settings', 'bop_message_color');
        register_setting('bullet_offer_settings', 'bop_popup_width');
        register_setting('bullet_offer_settings', 'bop_popup_height');
        register_setting('bullet_offer_settings', 'bop_cooldown');
        register_setting('bullet_offer_settings', 'bop_discount_percentage');
        register_setting('bullet_offer_settings', 'bop_background_color');
        register_setting('bullet_offer_settings', 'bop_text_color');
        register_setting('bullet_offer_settings', 'bop_button_background_color');
        register_setting('bullet_offer_settings', 'bop_button_text_color' );
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
                        <th scope="row">Popup Background Color</th>
                        <td><input type="color" name="bop_background_color" value="<?php echo esc_attr(get_option('bop_background_color', '#ffffff')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Popup Text Color</th>
                        <td><input type="color" name="bop_text_color" value="<?php echo esc_attr(get_option('bop_text_color', '#000000')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Button Background Color</th>
                        <td><input type="color" name="bop_button_background_color" value="<?php echo esc_attr(get_option('bop_btn_background_color', '#008000')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">button Text Color</th>
                        <td><input type="color" name="bop_button_text_color" value="<?php echo esc_attr(get_option('bop_button_text_color', '#fff')); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Display Time (seconds) [1-60]</th>
                        <td><input type="number" min="1" max="60" name="bop_display_time" value="<?php echo esc_attr(get_option('bop_display_time', 10)); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Popup Message</th>
                        <td><input type="text" name="bop_message" value="<?php echo esc_attr(get_option('bop_message')); ?>" size="50" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Offer Color</th>
                        <td><input type="color" name="bop_message_color" value="<?php echo esc_attr(get_option('bop_message_color', ' #ff0000')); ?>" /></td>
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
                    <tr valign="top">
                        <th scope="row">Discount Percentage</th>
                    <td><input type="number" name="bop_discount_percentage" value="<?php echo esc_attr(get_option('bop_discount_percentage', 10)); ?>" min="1" max="100" />%</td>
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
                    'relation' => 'AND',
                    array(
                        'key' => '_stock_status',
                        'value' => 'instock',
                    ),
                ),
            );

            $products = get_posts($product_props);

            $eligible_products = array();

            foreach ($products as $product_post) {
                $product = wc_get_product($product_post->ID);
                if (!$product) continue;

                if ($product->is_type('variable')) {
                    $total_stock = 0;
                    foreach ($product->get_children() as $child_id) {
                        $variation = wc_get_product($child_id);
                        if ($variation && $variation->is_in_stock()) {
                            $total_stock += $variation->get_stock_quantity() ?? 0;
                        }
                    }
                    if ($total_stock >= 5) {
                        $eligible_products[] = $product;
                    }
                } else {
                   $stock = $product->get_stock_quantity() ?? 0;
                   if ($stock >= 5) {
                       $eligible_products[] = $product;
                   }
                }
            }

            if (!empty($eligible_products)) {
                $random_product = $eligible_products[array_rand($eligible_products)];

                if ($random_product->is_type('variable') && isset($random_product)) {
                    $variations = $random_product->get_children();
                    $in_stock_variations = [];

                    foreach ($variations as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        if ($variation && $variation->is_in_stock()) {
                            $in_stock_variations[] = $variation;
                        }
                    }

                    if (!empty($in_stock_variations)) {
                        $random_product = $in_stock_variations[array_rand($in_stock_variations)];
                    } else {
                        // no in-stock variations, skip popup
                        return;
                    }

                } elseif ($random_product->is_type('grouped') && isset($random_product)) {
                    $children_ids = $random_product->get_children();
                    $in_stock_children = [];

                    foreach ($children_ids as $child_id) {
                        $child = wc_get_product($child_id);
                        if ($child && $child->is_in_stock()) {
                            $in_stock_children[] = $child;
                        }
                    }

                    if (!empty($in_stock_children)) {
                        $random_product = $in_stock_children[array_rand($in_stock_children)];
                    } else {
                        // no in-stock group children, skip popup
                        return;
                    }
                }
            }

            if (isset($random_product)) {
                        $product_id = $random_product->get_id();
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
            'cooldown' => get_option('bop_cooldown', 24),
            'backgroundColor' => get_option('bop_background_color', '#ffffff'),
            'textColor' => get_option('bop_text_color', '#000000'),
            "btnBackgroundColor" => get_option('bop_button_background_color', '008000'),
            "msgColor" => get_option('bop_message_color', '#ff0000'),
            'btnTextColor' => get_option('bop_button_text_color', '#fff'),
        ));
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
