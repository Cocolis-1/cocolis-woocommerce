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
        $data = $request->get_json_params();
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

            $order->update_status('processing');
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_offer_completed($request)
    {
        $data = $request->get_json_params();
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
        $data = $request->get_json_params();
        $orderid = $data['external_id'];
        $event = $data['event'];
        $ride_id = $data['ride_id'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            cocolis_shipping_method_init();
            $shipping_class = new WC_Cocolis_Shipping_Method();
            $client = $shipping_class->cocolis_authenticated_client();
            $client = $client->getRideClient();
            $ride = $client->get($ride_id);
            $id = $ride->id;
            $prod = $shipping_class->settings['production_mode'] == "sandbox" ? false : true;

            $note = __("The delivery offer has just been published on cocolis.fr", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);

            $base_url = $prod ? 'https://www.cocolis.fr' : 'https://sandbox.cocolis.fr';
            $link = sprintf('%s/ride/public/%s', $base_url, $id);

            $note = __("Link to ad: ", 'cocolis') . $link;

            // Add the note
            $order->add_order_note($note, false);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_pickup_slot_accepted_by_sender($request)
    {
        $data = $request->get_json_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("The seller accepted the carrier's pickup slot", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_deposit_slot_accepted_by_recipient($request)
    {
        $data = $request->get_json_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("The buyer has accepted the carrier's delivery slot", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_ride_availabilities_pending($request)
    {
        $data = $request->get_json_params();
        $orderid = $data['external_id'];
        $event = $data['event'];

        if (empty($event) || empty($orderid)) {
            echo ('Event or order ID missing from Webhook');
            exit;
        }

        $order = new WC_Order($orderid);


        if (!empty($order)) {
            $note = __("A carrier offers their services in response to a ride, and confirmation is awaited from the sender and recipient.", 'cocolis');

            // Add the note
            $order->add_order_note($note, false);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    function cocolis_webhook_offer_cancelled($request)
    {
        $data = $request->get_json_params();
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
        $data = $request->get_json_params();
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
        register_rest_route('cocolis/v1', '/webhook_pickup_slot_accepted_by_sender', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_pickup_slot_accepted_by_sender'),
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_deposit_slot_accepted_by_recipient', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_deposit_slot_accepted_by_recipient'),
            'permission_callback' => '__return_true'
        ));
        register_rest_route('cocolis/v1', '/webhook_ride_availabilities_pending', array(
            'methods'  => 'POST',
            'callback' => array($this, 'cocolis_webhook_ride_availabilities_pending'),
            'permission_callback' => '__return_true'
        ));
    }
}

new WC_Cocolis_Webhooks_Method();
