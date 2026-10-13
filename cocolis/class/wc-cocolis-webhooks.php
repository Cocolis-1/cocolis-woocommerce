<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Cocolis_Webhooks_Method
{
    /**
     * Header sent by Cocolis on every webhook, in the form "t=<unix timestamp>,v1=<hmac-sha256 hex digest>".
     */
    const SIGNATURE_HEADER = 'x-cocolis-signature';

    /**
     * Maximum age (in seconds) accepted for a signed webhook, to protect against replay attacks.
     */
    const SIGNATURE_TOLERANCE_IN_SECONDS = 300;

    /**
     * Option holding the webhook signing secret fetched from the Cocolis API.
     */
    const SIGNING_SECRET_OPTION = 'cocolis_webhook_signing_secret';

    /**
     * Transient used to rate-limit signing secret refreshes triggered by signature mismatches.
     */
    const SIGNING_SECRET_REFRESH_LOCK = 'cocolis_webhook_secret_refresh_lock';

    public function __construct()
    {
        add_action('rest_api_init', array($this, 'cocolis_register_hooks'), 999, 2);
    }

    /**
     * Permission callback shared by all webhook routes.
     *
     * Verifies the HMAC-SHA256 signature sent by Cocolis in the X-Cocolis-Signature header,
     * as documented in https://doc.cocolis.fr/docs/installation-et-utilisation/webhooks#securite
     *
     * @param WP_REST_Request $request
     * @return true|WP_Error
     */
    public function verify_webhook_signature($request)
    {
        // Escape hatch, e.g. for local testing. Defaults to enforcing signatures.
        if (!apply_filters('cocolis_verify_webhook_signature', true)) {
            return true;
        }

        $header = $request->get_header(self::SIGNATURE_HEADER);
        if (empty($header)) {
            return new WP_Error(
                'cocolis_missing_signature',
                __('Missing Cocolis webhook signature.', 'cocolis'),
                array('status' => 401)
            );
        }

        $parts = $this->parse_signature_header($header);
        if (empty($parts['t']) || empty($parts['v1']) || !ctype_digit($parts['t'])) {
            return new WP_Error(
                'cocolis_invalid_signature_header',
                __('Malformed Cocolis webhook signature header.', 'cocolis'),
                array('status' => 401)
            );
        }

        if (abs(time() - (int) $parts['t']) > self::SIGNATURE_TOLERANCE_IN_SECONDS) {
            return new WP_Error(
                'cocolis_expired_signature',
                __('Cocolis webhook signature timestamp is outside of the allowed tolerance.', 'cocolis'),
                array('status' => 401)
            );
        }

        $signed_payload = $parts['t'] . '.' . $request->get_body();

        $secret = self::get_signing_secret();
        if (!empty($secret) && hash_equals(hash_hmac('sha256', $signed_payload, $secret), $parts['v1'])) {
            return true;
        }

        // The secret may have been rotated on the Cocolis side: refresh it once and retry.
        $secret = self::get_signing_secret(true);
        if (!empty($secret) && hash_equals(hash_hmac('sha256', $signed_payload, $secret), $parts['v1'])) {
            return true;
        }

        return new WP_Error(
            'cocolis_invalid_signature',
            __('Invalid Cocolis webhook signature.', 'cocolis'),
            array('status' => 401)
        );
    }

    /**
     * Parse a signature header of the form "t=1764000000,v1=abcdef..." into an associative array.
     *
     * @param string $header
     * @return array
     */
    private function parse_signature_header($header)
    {
        $parts = array();
        foreach (explode(',', $header) as $pair) {
            $pair = explode('=', trim($pair), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }
        return $parts;
    }

    /**
     * Return the webhook signing secret, fetching it from the Cocolis API when missing or forced.
     *
     * @param bool $force_refresh
     * @return string Empty string when the secret cannot be retrieved.
     */
    public static function get_signing_secret($force_refresh = false)
    {
        $secret = get_option(self::SIGNING_SECRET_OPTION, '');
        if (!empty($secret) && !$force_refresh) {
            return $secret;
        }

        // Avoid hammering the Cocolis API when receiving a burst of unsigned/forged requests.
        if (get_transient(self::SIGNING_SECRET_REFRESH_LOCK)) {
            return $secret;
        }
        set_transient(self::SIGNING_SECRET_REFRESH_LOCK, 1, self::SIGNATURE_TOLERANCE_IN_SECONDS);

        return self::refresh_signing_secret();
    }

    /**
     * Fetch the webhook signing secret from the Cocolis API and store it.
     *
     * @return string Empty string on failure.
     */
    public static function refresh_signing_secret()
    {
        try {
            if (!function_exists('cocolis_shipping_method_init')) {
                return '';
            }
            cocolis_shipping_method_init();
            if (!class_exists('WC_Cocolis_Shipping_Method')) {
                return '';
            }

            $shipping_class = new WC_Cocolis_Shipping_Method();
            $client = $shipping_class->cocolis_authenticated_client();
            $response = $client->callAuthentificated('applications/webhook_signing_secret', 'GET');
            $body = json_decode($response->text(), true);

            $secret = isset($body['data']['webhook_signing_secret']) ? (string) $body['data']['webhook_signing_secret'] : '';

            if (!empty($secret)) {
                update_option(self::SIGNING_SECRET_OPTION, $secret, false);
            }

            return $secret;
        } catch (\Throwable $th) {
            error_log('Cocolis ERROR : unable to fetch the webhook signing secret: ' . $th->getMessage());
            return '';
        }
    }

    /**
     * Extract and validate the order targeted by a webhook request.
     *
     * @param WP_REST_Request $request
     * @param array|null $data Filled with the decoded JSON payload.
     * @return WC_Order|WP_Error
     */
    private function get_webhook_order($request, &$data = null)
    {
        $data = $request->get_json_params();

        if (empty($data) || !is_array($data) || empty($data['event']) || empty($data['external_id'])) {
            return new WP_Error(
                'cocolis_invalid_payload',
                __('Event or order ID missing from Webhook', 'cocolis'),
                array('status' => 400)
            );
        }

        $order = wc_get_order(absint($data['external_id']));

        if (!$order) {
            return new WP_Error(
                'cocolis_order_not_found',
                __('No order found for the given external_id', 'cocolis'),
                array('status' => 404)
            );
        }

        return $order;
    }

    /**
     * Webhooks actions based on the ride at Cocolis
     */
    function cocolis_webhook_offer_accepted($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("A carrier has been selected to carry out the Cocolis delivery.", 'cocolis');
        $order->add_order_note($note, false);
        $order->update_status('processing');

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_offer_completed($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("Delivery completed by Cocolis", 'cocolis');
        $order->add_order_note($note, false);
        $order->update_status('completed');

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_ride_published($request)
    {
        $order = $this->get_webhook_order($request, $data);
        if (is_wp_error($order)) {
            return $order;
        }

        if (empty($data['ride_id'])) {
            return new WP_Error(
                'cocolis_invalid_payload',
                __('Ride ID missing from Webhook', 'cocolis'),
                array('status' => 400)
            );
        }

        try {
            cocolis_shipping_method_init();
            $shipping_class = new WC_Cocolis_Shipping_Method();
            $client = $shipping_class->cocolis_authenticated_client();
            $client = $client->getRideClient();
            $ride = $client->get($data['ride_id']);
            $id = $ride->id;
            $prod = $shipping_class->get_option('production_mode') == "sandbox" ? false : true;

            $note = __("The delivery offer has just been published on cocolis.fr", 'cocolis');
            $order->add_order_note($note, false);

            $base_url = $prod ? 'https://www.cocolis.fr' : 'https://sandbox.cocolis.fr';
            $link = sprintf('%s/ride/public/%s', $base_url, $id);

            $note = __("Link to ad: ", 'cocolis') . $link;
            $order->add_order_note($note, false);
        } catch (\Throwable $th) {
            error_log('Cocolis ERROR : ' . $th);
            return new WP_Error(
                'cocolis_ride_fetch_failed',
                __('Unable to fetch the ride from the Cocolis API', 'cocolis'),
                array('status' => 500)
            );
        }

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_pickup_slot_accepted_by_sender($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("The seller accepted the carrier's pickup slot", 'cocolis');
        $order->add_order_note($note, false);

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_deposit_slot_accepted_by_recipient($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("The buyer has accepted the carrier's delivery slot", 'cocolis');
        $order->add_order_note($note, false);

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_ride_availabilities_pending($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("A carrier offers their services in response to a ride, and confirmation is awaited from the sender and recipient.", 'cocolis');
        $order->add_order_note($note, false);

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_offer_cancelled($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("The delivery is cancelled by the carrier. The seller and the buyer are informed, their tracking page is updated.", 'cocolis');
        $order->add_order_note($note, false);

        return rest_ensure_response(array('success' => true));
    }

    function cocolis_webhook_ride_expired($request)
    {
        $order = $this->get_webhook_order($request);
        if (is_wp_error($order)) {
            return $order;
        }

        $note = __("The ride did not find a carrier. Get closer to our support and with cocolis.fr", 'cocolis');
        $order->add_order_note($note, false);
        $order->update_status('failed');

        return rest_ensure_response(array('success' => true));
    }

    /**
     * Register all routes for actions
     */
    function cocolis_register_hooks()
    {
        $routes = array(
            'webhook_offer_accepted' => 'cocolis_webhook_offer_accepted',
            'webhook_offer_completed' => 'cocolis_webhook_offer_completed',
            'webhook_ride_published' => 'cocolis_webhook_ride_published',
            'webhook_offer_cancelled' => 'cocolis_webhook_offer_cancelled',
            'webhook_ride_expired' => 'cocolis_webhook_ride_expired',
            'webhook_pickup_slot_accepted_by_sender' => 'cocolis_webhook_pickup_slot_accepted_by_sender',
            'webhook_deposit_slot_accepted_by_recipient' => 'cocolis_webhook_deposit_slot_accepted_by_recipient',
            'webhook_ride_availabilities_pending' => 'cocolis_webhook_ride_availabilities_pending',
        );

        foreach ($routes as $route => $callback) {
            register_rest_route('cocolis/v1', '/' . $route, array(
                'methods'  => 'POST',
                'callback' => array($this, $callback),
                // Requests are authenticated with the Cocolis HMAC signature instead of a WordPress user.
                'permission_callback' => array($this, 'verify_webhook_signature'),
            ));
        }
    }
}

new WC_Cocolis_Webhooks_Method();
