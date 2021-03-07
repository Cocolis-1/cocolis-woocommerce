<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';
include_once dirname(__FILE__, 2) . "/wc-cocolis-shipping.php";

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
    function webhook_offer_accepted($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $resource_id = $data['resource_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);

        if (!empty($order)) {
            $note = __("A carrier has been selected to carry out the Cocolis delivery.", 'cocolis-woocommerce');

            // Add the note
            $order->add_order_note($note, true);
            
            if (!empty($resource_id)) {
                cocolis_shipping_method_init();
                $shipping_class = new WC_Cocolis_Shipping_Method();
                $client = $shipping_class->authenticatedClient();
                $client = $client->getRideClient();
                $ride = $client->get($resource_id);
                $slug = $ride->slug;
                $prod = $shipping_class->settings['production_mode'] == "sandbox" ? false : true;

                $link = $prod ? 'https://cocolis.fr/ride-public/' .
                    $slug : 'https://sandbox.cocolis.fr/ride-public/' . $slug;

                $note = printf(__("The public ride URL : %s ", 'cocolis-woocommerce'), $link);

                // Add the note
                $order->add_order_note($note, true);

                $note = printf(__("Buyer tracking delivery URL : %s", 'cocolis-woocommerce'), $ride->getBuyerURL());

                // Add the note
                $order->add_order_note($note, true);

                $note = printf(__("[Private] Seller tracking delivery URL : ", 'cocolis-woocommerce'), $ride->getSellerURL());

                // Add the note
                $order->add_order_note($note);
            }

            $order->update_status('processing');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function webhook_offer_completed($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("Delivery completed by Cocolis", 'cocolis-woocommerce');

            // Add the note
            $order->add_order_note($note);
            $order->update_status('completed');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function webhook_ride_published($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("An offer was published on cocolis.fr", 'cocolis-woocommerce');

            // Add the note
            $order->add_order_note($note, true);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function webhook_offer_cancelled($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("The delivery is cancelled by the carrier. The seller and the buyer are informed, their tracking page is updated.", 'cocolis-woocommerce');

            // Add the note
            $order->add_order_note($note, true);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function webhook_ride_expired($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("The ride did not find a carrier. Get closer to our support and with cocolis.fr", 'cocolis-woocommerce');

            // Add the note
            $order->add_order_note($note, true);
            $order->update_status('failed');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function register_hooks()
    {
        register_rest_route('cocolis/v1', '/webhook_offer_accepted', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => 'POST',
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => 'webhook_offer_accepted',
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_offer_completed', array(
            'methods'  => 'POST',
            'callback' => 'webhook_offer_completed',
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_ride_published', array(
            'methods'  => 'POST',
            'callback' => 'webhook_ride_published',
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_offer_cancelled', array(
            'methods'  => 'POST',
            'callback' => 'webhook_offer_cancelled',
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_ride_published', array(
            'methods'  => 'POST',
            'callback' => 'webhook_ride_published',
            'permission_callback' => '__return_true'
        ));
    }

    add_action('rest_api_init', 'register_hooks');
}
