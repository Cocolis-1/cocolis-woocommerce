<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_Cocolis_Payment_Method
{
    public function __construct()
    {
        add_action('woocommerce_order_status_processing', array($this, 'cocolis_payment_complete'), 100);

        add_action('woocommerce_order_refunded', array($this, 'cocolis_order_refunded'), 10, 2);

        add_action('add_meta_boxes', array($this, 'add_meta_boxesws'), 10, 2);
    }

    /**
     * Status change on order to Processing
     */
    function cocolis_payment_complete($order_id)
    {
        try {
            $order = wc_get_order($order_id);
            $order_data = $order->get_data();
            if ($order->has_shipping_method('cocolis') && empty($order->get_meta('_cocolis_ride_id'))) {

                // The main address pieces:
                $store_name        = apply_filters('cocolis_store_name', get_bloginfo('name'), null);
                $store_address     = apply_filters('cocolis_store_address', get_option('woocommerce_store_address'), null);
                $store_address_2   = apply_filters('cocolis_store_address_2', get_option('woocommerce_store_address_2'), null);
                $store_city        = apply_filters('cocolis_store_city', get_option('woocommerce_store_city'), null);
                $store_postcode    = apply_filters('cocolis_store_postcode', get_option('woocommerce_store_postcode'), null);

                // The country/state
                $store_raw_country = apply_filters('cocolis_store_country', get_option('woocommerce_default_country'), null);

                // Split the country/state
                $split_country = explode(":", $store_raw_country);

                // Country and state separated:
                $store_country = $split_country[0];

                $order_shipping_first_name = $order_data['shipping']['first_name'];
                $order_shipping_last_name = $order_data['shipping']['last_name'];
                $order_shipping_email = $order_data['billing']['email'];
                $order_shipping_company = $order_data['shipping']['company'];
                $order_shipping_address_1 = $order_data['shipping']['address_1'];
                $order_shipping_address_2 = $order_data['shipping']['address_2'];
                $order_shipping_city = $order_data['shipping']['city'];
                $order_shipping_state = $order_data['shipping']['state'];
                $order_shipping_postcode = $order_data['shipping']['postcode'];
                $order_shipping_country = $order_data['shipping']['country'];
                $order_shipping_phone = $order_data['billing']['phone'];

                $from_composed_address = $store_address . ', '
                    . $store_postcode . ' ' . $store_city;

                $composed_address = $order_shipping_address_1 . ', ' . $order_shipping_postcode . ' ' . $order_shipping_city;

                $from_date = new DateTime('NOW');
                $from_date->setTimeZone(new DateTimeZone("Europe/Paris"));

                $to_date = new DateTime('NOW');
                $to_date  = $to_date->add(new DateInterval('P21D'));
                $to_date->setTimeZone(new DateTimeZone("Europe/Paris"));

                $from_date = $from_date->format('c');
                $to_date = $to_date->format('c');

                $products = (object) $order->get_items();
                $dimensions = 0;

                $arrayproducts = [];

                $arrayname = [];

                cocolis_shipping_method_init();
                $shipping_class = new WC_Cocolis_Shipping_Method();

                $client = $shipping_class->cocolis_authenticated_client();

                $phone = $shipping_class->get_option('phone');
                if (empty($phone)) {
                    // Do not wp_die() here: this hook runs during checkout and would break the payment flow.
                    error_log('Cocolis ERROR : no phone number provided in the Cocolis configuration');
                    $order->add_order_note(__("No phone number provided in the Cocolis configuration", 'cocolis'), false);
                    return false;
                }

                $dimensions = 0;

                foreach ($products as $product) {
                    $product = (object) $product;
                    $product_id = $product->get_product_id();

                    $store_name        = apply_filters('cocolis_store_name', get_bloginfo('name'), $product_id);
                    $store_address     = apply_filters('cocolis_store_address', get_option('woocommerce_store_address'), $product_id);
                    $store_address_2   = apply_filters('cocolis_store_address_2', get_option('woocommerce_store_address_2'), $product_id);
                    $store_city        = apply_filters('cocolis_store_city', get_option('woocommerce_store_city'), $product_id);
                    $store_postcode    = apply_filters('cocolis_store_postcode', get_option('woocommerce_store_postcode'), $product_id);
                    $phone             = apply_filters('cocolis_store_phone', $phone, $product_id);

                    // The country/state
                    $store_raw_country = apply_filters('cocolis_store_country', get_option('woocommerce_default_country'), $product_id);

                    $data = wc_get_product( $product_id );
                    $width = (int) $data->get_width();
                    $length = (int) $data->get_length();
                    $height = (int) $data->get_height();

                    if ($width == 0 || $length == 0 || $height == 0) {
                        // Use the default value of volume for delivery fees
                        $width = $shipping_class->width;
                        $length = $shipping_class->length;
                        $height = $shipping_class->height;
                        $dimensions += (($width * $length * $height) / pow(10, 6)) * (int) $product['qty'];
                    } else {
                        $dimensions += (($width * $length * $height) / pow(10, 6)) * (int) $product['qty'];
                    }


                    array_push($arrayname, $product->get_name());
                    array_push($arrayproducts, [
                        "title" => $product->get_name(),
                        "qty" => $product['qty'],
                        "photo_urls" => array(wp_get_attachment_url($data->get_image_id())),
                        "height" => (int) $height,
                        "width" => (int) $width,
                        "length" => (int) $length,
                    ]);
                }

                $content_value = (float) ($order->get_total() - $order->get_total_tax() - $order->get_shipping_tax()) * 100; // In cents
                $params = [
                    "description" => "Livraison de la commande : " . implode(", ", $arrayname) . " vendue sur le site marketplace.",
                    "external_id" => $order_id,
                    "idempotency_key" =>'cocolis_woocommerce_' . $order_id,
                    "from_address" => $from_composed_address,
                    "from_postal_code" => $store_postcode,
                    "to_address" => $composed_address,
                    "to_postal_code" => $order_shipping_postcode,
                    "from_is_flexible" => false,
                    "from_pickup_date" => $from_date,
                    "from_need_help" => true,
                    "to_is_flexible" => false,
                    "to_need_help" => true,
                    "to_pickup_date" => $to_date,
                    "is_passenger" => false,
                    "is_packaged" => true,
                    "price" => (int) round((float) $order->get_shipping_total() * 100),
                    "volume" => $dimensions,
                    "environment" => "objects",
                    "content_value" => $content_value, // In cents
                    "rider_extra_information" => "Livraison de la commande : " . implode(", ", $arrayname),
                    "ride_objects_attributes" => $arrayproducts,
                    "ride_delivery_information_attributes" => [
                        "from_address" => $store_address,
                        "from_postal_code" => $store_postcode,
                        "from_city" => $store_city,
                        "from_country" => $store_country,
                        "from_contact_email" => $shipping_class->get_option('email'),
                        "from_contact_phone" => $phone,
                        "from_contact_name" => $store_name,
                        "from_extra_information" => 'Vendeur Marketplace',
                        "to_address" => $order_shipping_address_1,
                        "to_postal_code" => $order_shipping_postcode,
                        "to_city" => $order_shipping_city,
                        "to_country" => $order_shipping_country,
                        "to_contact_name" => $order_shipping_first_name . ' ' . $order_shipping_last_name,
                        "to_contact_email" => $order_shipping_email,
                        "to_contact_phone" => $order_shipping_phone
                    ],
                ];

                $client = $client->getRideClient();
                $ride = $client->create($params);
                $order->update_meta_data('_cocolis_ride_id', $ride->id);
                $order->save();

                // Adding buyer and seller url notes

                $note = __("Link to buyer tracking: ", 'cocolis') . $ride->getBuyerURL();

                // Add the note
                $order->add_order_note($note, false);

                $note = __("Link to vendor tracking: ", 'cocolis') . $ride->getSellerURL();

                // Add the note
                $order->add_order_note($note, false);
            }
        } catch (\Throwable $th) {
            error_log('Cocolis ERROR : ' . $th);
            $order = wc_get_order($order_id);
            $note = __("Your request to Cocolis generated the following error: ", 'cocolis') . $th->getMessage();
            $order->add_order_note($note, false);
            return false;
        }
    }

    /**
     * When an order is refunded with Cocolis shipping
     */
    function cocolis_order_refunded($order_get_id, $refund_get_id)
    {
        try {
            $order = wc_get_order($order_get_id);

            if ($order->has_shipping_method('cocolis')) {
                if (!empty($order->get_meta('_cocolis_ride_id'))) {
                    cocolis_shipping_method_init();
                    $shipping_class = new WC_Cocolis_Shipping_Method();
                    $client = $shipping_class->cocolis_authenticated_client();
                    $client = $client->getRideClient();
                    $client->remove($order->get_meta('_cocolis_ride_id'));
                    $note = __("The delivery ad has been removed following the refund.", 'cocolis');
                    $order->add_order_note($note, false);
                } else {
                    $order = wc_get_order($order_get_id);
                    $note = __("We did not find any related cocolis ads, so the ad was not cancelled.", 'cocolis');
                    $order->add_order_note($note, false);
                }
            }
        } catch (\Throwable $th) {
            error_log('Cocolis ERROR : ' . $th);
            $order = wc_get_order($order_get_id);
            $note = __("Your request to Cocolis generated the following error: ", 'cocolis') . $th->getMessage();
            $order->add_order_note($note, false);
            return false;
        }
    }


    /**
     * Resolve the order from the object passed to meta box hooks (WP_Post on the
     * legacy order screen, WC_Order on the HPOS order screen), with a URL fallback.
     *
     * @param mixed $object
     * @return WC_Order|false
     */
    private function get_order_from_metabox_object($object)
    {
        if ($object instanceof WC_Order) {
            return $object;
        }

        if ($object instanceof WP_Post) {
            return wc_get_order($object->ID);
        }

        $order_id = isset($_GET['id']) ? absint($_GET['id']) : (isset($_GET['post']) ? absint($_GET['post']) : 0);

        return $order_id ? wc_get_order($order_id) : false;
    }

    function add_meta_boxesws($post_type = '', $object = null)
    {
        $order = $this->get_order_from_metabox_object($object);

        if ($order && $order->has_shipping_method('cocolis')) {
            // HPOS-aware screen id: 'woocommerce_page_wc-orders' or 'shop_order'.
            $screen = function_exists('wc_get_page_screen_id') ? wc_get_page_screen_id('shop-order') : 'shop_order';

            add_meta_box(
                'custom_order_meta_box',
                __('Manage my delivery with Cocolis', 'cocolis'),
                array($this, 'custom_metabox_content'),
                $screen,
                'normal',
                'default'
            );
        }
    }

    function custom_metabox_content($object = null)
    {
        $order = $this->get_order_from_metabox_object($object);

        if (!$order) return; // Exit

        $create_url = wp_nonce_url(
            add_query_arg('create_delivery', 'cocolis', $order->get_edit_order_url()),
            'cocolis_create_delivery_' . $order->get_id()
        );
?>
        <p><a href="<?php echo esc_url($create_url); ?>" class="button"><?php echo esc_html__("Create Cocolis Ride", 'cocolis'); ?></a></p>
<?php
        // The displayed value using GET method
        if (!empty($_GET['create_delivery'])
            && isset($_GET['_wpnonce'])
            && wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'cocolis_create_delivery_' . $order->get_id())
            && current_user_can('edit_shop_orders')
        ) {
            if (empty($order->get_meta('_cocolis_ride_id'))) {
                echo '<b style="color:#0069d8">' . esc_html__("The delivery offer has just been published on cocolis.fr", 'cocolis') . '</b>';
                $this->cocolis_payment_complete($order->get_id());
            } else {
                echo '<b style="color:red">' . esc_html__("We have find a related cocolis ad to this order, so the ad was not published.", 'cocolis') . '</b>';
            }
        }
    }
}

new WC_Cocolis_Payment_Method();
