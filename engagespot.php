<?php
/*
  Plugin Name: Engagespot
  Plugin URI: http://engagespot.co
  Description: Engagespot adds web push notifications to your Wordpress website. Connect with your visitors even when they are not on your website.
  Version: 2.1
  Author: Engagespot
  Author URI: http://engagespot.co
  Requires at least: 2.7
  Tested up to: 4.7
  License: GPLv2
  Text Domain: engagespot
 */

/*
  Copyright (C) 2017 Engagespot

  This program is free software; you can redistribute it and/or
  modify it under the terms of the GNU General Public License
  as published by the Free Software Foundation; either version 2
  of the License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

define('ENGAGESPOT_EVENTS', 'https://api.engagespot.co/2/events');
define('ENGAGESPOT_NOTIFY', 'https://api.engagespot.co/2/campaigns');

add_action('admin_menu', 'engagespot_admin_menu');

if (!function_exists('engagespot_admin_menu')) {

    /**
     * 
     * For creating admin menus in wordpres backend
     * 
     */
    function engagespot_admin_menu() {
        add_menu_page(__('Engagespot', 'customtracker'), __('Engagespot', 'customtracker'), 'upload_files', __FILE__, 'track_events_callback', 'dashicons-chart-line');
    }

    /**
     *  For hanndling content of admin menu
     */
    if (!function_exists('track_events_callback')) {

        function track_events_callback() {
            include 'templates/admin-template.php';
        }

    }
}
$engagespot_status = get_option('engagespot_status', '0');

if ($engagespot_status == '1') {

    add_action('wp_head', 'engagespot_header_script');

    add_action('wp_head', 'engagespot_wp_events');

    add_action('woocommerce_add_to_cart', 'engagespot_woo_cart', 10, 4);

    add_action('woocommerce_thankyou', 'engagespot_woo_completed');
//    add_action('woocommerce_order_status_completed', 'engagespot_woo_completed');
}


if (!function_exists('engagespot_woo_completed')) {

    /**
     * For purchased products
     * @param int $order_id
     */
    function engagespot_woo_completed($order_id) {

        $order = new WC_Order($order_id);
        $items = $order->get_items();
        foreach ($items as $item) {
            $postTitle = $item['name'];
            $postID = $item['product_id'];
            engagespotCallEventAPI('purchased', $postID, $postTitle);
        }
    }

}

if (!function_exists('engagespot_wp_events')) {


    /**
     * For tracking events of frontend
     */
    function engagespot_wp_events() {
        global $post;
        $postID = $post->ID;
        $postTitle = $post->post_title;
        engagespotCallEventAPI('visited', $postID, $postTitle);
    }

}


if (!function_exists('engagespot_woo_cart')) {


    /**
     * To handle add to cart event
     * @param string|bool $cart_item_key
     * @param int $product_id contains the id of the product to add to the cart
     * @param int $quantity contains the quantity of the item to add
     * @param int $variation_id
     */
    function engagespot_woo_cart($cart_item_key, $product_id, $quantity, $variation_id) {
        $productName = wc_get_product($product_id)->post->post_title;
        engagespotCallEventAPI('added_to_cart', $product_id, $productName);
    }

}

/**
 * For calling event API
 * @param string $action
 * @param int|string $id
 * @param string $name
 */
function engagespotCallEventAPI($action, $id, $name) {
    $_webPushUserHash = filter_input(INPUT_COOKIE, '_webPushUserHash');
    if (isset($_webPushUserHash)) {
        $eventdata = array(
            'user_hash' => $_webPushUserHash,
            'action' => $action,
            'object' => array(
                'id' => (string) $id,
                'name' => $name
            ),
        );
        return engagespot_handler(ENGAGESPOT_EVENTS, json_encode($eventdata));
    }
}

// Adding js files in header

function engagespot_engagespot_js() {
    wp_enqueue_script('engagespot-js', 'https://cdn.engagespot.co/EngagespotSDK.2.0.js', array(), '1.0', false);
}

if (!function_exists('engagespot_header_script')) {

    /*
     * for adding footer API script
     */

    function engagespot_header_script() {
        $engagespot_site_key = get_option('engagespot_site_key');
        ?>

        <script>
window.Engagespot={},q=function(e){return function(){(window.engageq=window.engageq||[]).push({f:e,a:arguments})}},f=["captureEvent","subscribe","init","showPrompt"];for(k in f)Engagespot[f[k]]=q(f[k]);var s=document.createElement("script");s.type="text/javascript",s.async=!0,s.src="https://cdn.engagespot.co/EngagespotSDK.2.0.js";var x=document.getElementsByTagName("script")[0];x.parentNode.insertBefore(s,x);

Engagespot.init('<?php echo $engagespot_site_key ?>');
</script>
        <?php
    }

}

add_action('add_meta_boxes', 'engagespot_post_meta');

if (!function_exists('engagespot_post_meta')) {

    /**
     * to add metabox in posts section where checkbox will be located
     */
    function engagespot_post_meta() {
        add_meta_box('engagespot_post_meta', 'Send Notifications', 'engagespot_postmeta_call', 'post', 'side', 'high', null);
    }

    if (!function_exists('engagespot_postmeta_call')) {

        function engagespot_postmeta_call() {
            wp_nonce_field(basename(__FILE__), "engagespot_meta-box-nonce");
            global $post;
            ?>
            <div>
                <label for="meta-send-checkbox">Send notifications</label>
                <?php
                $checkbox_value = get_post_meta($post->ID, "cte-checkbox", true);
                if (($checkbox_value == "true") || ($post->post_status == 'auto-draft')) {
                    ?>  
                    <input name="engagespot_check_box" id="engagespot_check_box" type="checkbox" value="true" checked>
                    <?php
                } else {
                    ?>
                    <input name="engagespot_check_box" id="engagespot_check_box" type="checkbox" value="true">
                    <?php
                }
                ?>
            </div>
            <?php
        }

    }
}


if (!function_exists('engagespot_notification_area')) {

    /**
     * To save the values of posts section and send notificaiton
     * @global type $post
     * @param type $post_id
     * @return type
     */
    function engagespot_notification_area($post_id) {

        global $post;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;
        if (wp_is_post_revision($post_id))
            return;
        if (isset($_POST['engagespot_check_box'])) {
            $noti_title = $post->post_title;

            if ($post->post_excerpt) {
                $noti_message = $post->post_excerpt;
            } else {
                $content = $post->post_content;
                $text = wp_trim_words($content, 20);
                $noti_message = $text;
            }

            $noti_link = get_permalink($post);
            $noti_image = wp_get_attachment_url(get_post_thumbnail_id($post_id));
            $eventdata = array(
                'campaign_name' => $noti_title,
                'notification' => array(
                    'title' => $noti_title,
                    'message' => $noti_message,
                    'url' => $noti_link,
                    'icon' => $noti_image
                ),
                'send_to' => 'everyone'
            );

            $response = engagespot_handler(ENGAGESPOT_NOTIFY, json_encode($eventdata));
        }
    }

}

add_action('publish_post', 'engagespot_notification_area');

/**
 * 
 * @param string $url
 * @param array|string $data
 * @return json
 */
function engagespot_handler($url, $data) {
    $engagespot_api_key = get_option('engagespot_api_key');
    try {
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array('Api-Key' => $engagespot_api_key),
            'body' => $data,
            'cookies' => array()
                )
        );
        return $response;
    } catch (Exception $ex) {
        
    }
}

// Check to see if user posted first time.
add_action('transition_post_status', 'engagespot_post_status', 10, 3);

/**
 * 
 * @param String $new
 * @param String $old
 * @param Array|Object $post
 */
function engagespot_post_status($new, $old, $post) {
    if ($new == 'draft') {
        update_post_meta($post->ID, 'cte-checkbox', (filter_input(INPUT_POST, 'engagespot_check_box') == 'true') ? 'true' : '');
    } else if ($new == 'publish') {
        update_post_meta($post->ID, 'cte-checkbox', '');
    }
}
