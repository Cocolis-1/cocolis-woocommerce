<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Cocolis_Webhooks_Method
{
    public function __construct()
    {
        add_action('rest_api_init', array($this, 'cocolis_register_hooks'), 999, 2);
    }

    /**
     * Webhooks actions based on the ride at Cocolis
     */
    function cocolis_webhook_offer_accepted($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $ride_id = $data['ride_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);

        if (!empty($order)) {
            $note = __("A carrier has been selected to carry out the Cocolis delivery.", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);

            if (!empty($ride_id)) {
                cocolis_shipping_method_init();
                $shipping_class = new WC_Cocolis_Shipping_Method();
                $client = $shipping_class->cocolis_authenticated_client();
                $client = $client->getRideClient();
                $ride = $client->get($ride_id);


                $note = __("Link to buyer tracking: ", 'cocolis') . $ride->getBuyerURL();

                // Add the note
                $order->add_order_note($note, false);

                $note = __("Link to vendor tracking: ", 'cocolis') . $ride->getSellerURL();

                // Add the note
                $order->add_order_note($note, false);
            }

            $order->update_status('processing');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_offer_completed($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("Delivery completed by Cocolis", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);
            $order->update_status('completed');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_ride_published($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];
        $ride_id = $data['ride_id'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $shipping_class = new WC_Cocolis_Shipping_Method();
            $client = $shipping_class->cocolis_authenticated_client();
            $client = $client->getRideClient();
            $ride = $client->get($ride_id);
            $slug = $ride->slug;
            $prod = $shipping_class->settings['production_mode'] == "sandbox" ? false : true;

            $note = __("The delivery offer has just been published on cocolis.fr", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);

            $link = $prod ? 'https://cocolis.fr/ride-public/' .
                $slug : 'https://sandbox.cocolis.fr/ride-public/' . $slug;

            $note = __("Link to ad: ", 'cocolis') . $link;

            // Add the note
            $order->add_order_note($note, false);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_offer_cancelled($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("The delivery is cancelled by the carrier. The seller and the buyer are informed, their tracking page is updated.", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_ride_expired($request)
    {
        $data = $request->get_body_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("The ride did not find a carrier. Get closer to our support and with cocolis.fr", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);
            $order->update_status('failed');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Register all routes for actions
     */
    function cocolis_register_hooks()
    {
        register_rest_route('cocolis/v1', '/webhook_offer_accepted', array(
            // By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
            'methods'  => 'POST',
            // Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
            'callback' => array($this, 'cocolis_webhook_offer_accepted'),
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_offer_completed', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_offer_completed'),
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_ride_published', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_ride_published'),
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_offer_cancelled', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_offer_cancelled'),
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_ride_published', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_ride_published'),
            'permission_callback' => '__return_true'
        ));
    }
}

new WC_Cocolis_Webhooks_Method();
