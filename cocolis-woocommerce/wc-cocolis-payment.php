<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
include_once "wc-cocolis-shipping.php";

/**
 * Plugin Name: Cocolis Woocommerce
 * Plugin URI: https://www.cocolis.fr
 * Description: A plugin to add Cocolis.fr as a carrier on Woocommerce
 * Author:  Cocolis.fr
 * Author URI: https://www.cocolis.fr
 * Version: 1.0
 * Developer: Alexandre BETTAN, Sebastien Fieloux
 * Developer URI: https://github.com/btnalexandre, https://github.com/sebfie
 * Domain Path: /languages
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.O.html
 */

/**
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('woocommerce_order_status_processing', 'payment_complete');
    function payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        if ($order->has_shipping_method('cocolis-woocommerce')) {
            // The main address pieces:
            $store_address     = get_option('woocommerce_store_address');
            $store_address_2   = get_option('woocommerce_store_address_2');
            $store_city        = get_option('woocommerce_store_city');
            $store_postcode    = get_option('woocommerce_store_postcode');

            // The country/state
            $store_raw_country = get_option('woocommerce_default_country');

            // Split the country/state
            $split_country = explode(":", $store_raw_country);

            // Country and state separated:
            $store_country = $split_country[0];

            $order_shipping_first_name = $order_data['shipping']['first_name'];
            $order_shipping_last_name = $order_data['shipping']['last_name'];
            $order_shipping_company = $order_data['shipping']['company'];
            $order_shipping_address_1 = $order_data['shipping']['address_1'];
            $order_shipping_address_2 = $order_data['shipping']['address_2'];
            $order_shipping_city = $order_data['shipping']['city'];
            $order_shipping_state = $order_data['shipping']['state'];
            $order_shipping_postcode = $order_data['shipping']['postcode'];
            $order_shipping_country = $order_data['shipping']['country'];

            
            exit;
        }
    }
}
