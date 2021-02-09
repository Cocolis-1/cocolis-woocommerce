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
    add_filter('woocommerce_billing_fields', 'add_birth_date_billing_field', 20, 1);
    function add_birth_date_billing_field($billing_fields)
    {
        $billing_fields['birth_date'] = array(
        'type'        => 'date',
        'label'       => __('Birth date (for insurance)'),
        'class'       => array('form-row-wide'),
        'priority'    => 25,
        'required'    => true,
        'clear'       => true,
        'placeholder' => __('Birth date required for insurance')
    );
        return $billing_fields;
    }

    add_action('woocommerce_checkout_update_order_meta', 'save_birth_date_billing_field', 20, 2);

    function save_birth_date_billing_field($order_id, $posted)
    {
        if (isset($posted['birth_date'])) {
            update_post_meta($order_id, 'birth_date', $posted['birth_date']);
        }
    }

    add_action('woocommerce_order_status_processing', 'payment_complete');
    function payment_complete($order_id)
    {
        $order = wc_get_order($order_id);
        $order_data = $order->get_data();
        if ($order->has_shipping_method('cocolis-woocommerce')) {
            // The main address pieces:
            $store_name = get_bloginfo('name');
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
            $order_shipping_email = $order_data['billing']['email'];
            $order_shipping_company = $order_data['shipping']['company'];
            $order_shipping_address_1 = $order_data['shipping']['address_1'];
            $order_shipping_address_2 = $order_data['shipping']['address_2'];
            $order_shipping_city = $order_data['shipping']['city'];
            $order_shipping_state = $order_data['shipping']['state'];
            $order_shipping_postcode = $order_data['shipping']['postcode'];
            $order_shipping_country = $order_data['shipping']['country'];
            $order_shipping_phone = $order_data['billing']['phone'];
            $order_birthdate = $order->get_meta('birth_date');

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

            $shipping_class = new WC_Cocolis_Shipping_Method();

            $client = $shipping_class->authenticatedClient();

            $phone = $shipping_class->settings['phone'];
            if (empty($phone)) {
                wp_die("No phone number provided in the Cocolis configuration", "Required seller phone number", ['response' => 401, 'back_link' => true]);
                exit;
            }

            $dimensions = 0;

            foreach ($products as $product) {
                $product = (object) $product;
                $data = wc_get_product($product->get_product_id());
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
                    "img" => wp_get_attachment_url($data->get_image_id()),
                    "height" => (int) $height,
                    "width" => (int) $width,
                    "length" => (int) $length,
                ]);
            }
        }

        $images = [];
        foreach ($arrayproducts as $image) {
            if (!empty($image['img'])) {
                array_push($images, $image['img']);
            }
        }

        //TODO: VERIFY IF TRANSLATION BROKE THIS METHOD
        if (str_contains($order->get_shipping_method(), "with insurance")) {
            $birthday = new DateTime($order_birthdate);

            $params = [
                    "description" => "Livraison de la commande : " . implode(", ", $arrayname) . " vendue sur le site marketplace.",
                    "external_id" => $order_id,
                    "from_address" => $from_composed_address,
                    "from_postal_code" => $store_postcode,
                    "to_address" => $composed_address,
                    "to_postal_code" => $order_shipping_postcode,
                    "from_is_flexible" => false,
                    "from_pickup_date" => $from_date,
                    "from_need_help" => true,
                    "to_is_flexible" => false,
                    "to_need_help" => true,
                    "with_insurance" => true,
                    "to_pickup_date" => $to_date,
                    "is_passenger" => false,
                    "is_packaged" => true,
                    "price" => (int) $order->get_shipping_total() * 100,
                    "volume" => $dimensions,
                    "environment" => "objects",
                    "photo_urls" => $images,
                    "rider_extra_information" => "Livraison de la commande : " . implode(", ", $arrayname),
                    "ride_objects_attributes" => $arrayproducts,
                    "ride_delivery_information_attributes" => [
                        "from_address" => $store_address,
                        "from_postal_code" => $store_postcode,
                        "from_city" => $store_city,
                        "from_country" => $store_country,
                        "from_contact_email" => $shipping_class->settings['email'],
                        "from_contact_phone" => $phone,
                        "from_contact_name" => $store_name,
                        "from_extra_information" => 'Vendeur MarketPlace',
                        "to_address" => $order_shipping_address_1,
                        "to_postal_code" => $order_shipping_postcode,
                        "to_city" => $order_shipping_city,
                        "to_country" => $order_shipping_country,
                        "to_contact_name" => $order_shipping_first_name . ' ' . $order_shipping_last_name,
                        "to_contact_email" => $order_shipping_email,
                        "to_contact_phone" => $order_shipping_phone,
                        "insurance_firstname" => $order_shipping_first_name,
                        "insurance_lastname" =>  $order_shipping_last_name,
                        "insurance_address" => $order_shipping_address_1,
                        "insurance_postal_code" => $order_shipping_postcode,
                        "insurance_city" => $order_shipping_city,
                        "insurance_country" => $order_shipping_country,
                        "insurance_birthdate" => $birthday->format('c')
                ],
            ];

            $client = $client->getRideClient();
            $client->create($params);
        } else {
            $params = [
                "description" => "Livraison de la commande : " . implode(", ", $arrayname) . " vendue sur le site marketplace.",
                "external_id" => $order_id,
                "from_address" => $from_composed_address,
                "from_postal_code" => $store_postcode,
                "to_address" => $composed_address,
                "to_postal_code" => $order_shipping_postcode,
                "from_is_flexible" => false,
                "from_pickup_date" => $from_date,
                "from_need_help" => true,
                "to_is_flexible" => false,
                "to_need_help" => true,
                "with_insurance" => false,
                "to_pickup_date" => $to_date,
                "is_passenger" => false,
                "is_packaged" => true,
                "price" => (int) $order->get_shipping_total() * 100,
                "volume" => $dimensions,
                "environment" => "objects",
                "photo_urls" => $images,
                "rider_extra_information" => "Livraison de la commande : " . implode(", ", $arrayname),
                "ride_objects_attributes" => $arrayproducts,
                "ride_delivery_information_attributes" => [
                    "from_address" => $store_address,
                    "from_postal_code" => $store_postcode,
                    "from_city" => $store_city,
                    "from_country" => $store_country,
                    "from_contact_email" => $shipping_class->settings['email'],
                    "from_contact_phone" => $phone,
                    "from_contact_name" => $store_name,
                    "from_extra_information" => 'Vendeur MarketPlace',
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
            $client->create($params);
        }
    }
}
