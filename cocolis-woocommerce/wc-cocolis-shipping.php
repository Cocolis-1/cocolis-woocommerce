<?php

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
include_once "class/wc-cocolis-payment.php";
include_once "class/wc-cocolis-webhooks.php";
use Cocolis\Api\Client;

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
    function cocolis_shipping_method_init()
    {
        if (! class_exists('WC_Cocolis_Shipping_Method')) {
            class WC_Cocolis_Shipping_Method extends WC_Shipping_Method
            {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct()
                {
                    global $woocommerce;
                    $this->id                 = 'cocolis-woocommerce';
                    $this->method_title       = __('Cocolis Shipping Method');
                    $this->method_description = __('Cocolis Woocommerce Plugin to add Cocolis.fr as a delivery method');
                    // Define user set variables.
                    $this->production_mode = $this->get_option('production_mode');
                    $this->app_id = $this->get_option('app_id');
                    $this->password = $this->get_option('password');
                    $this->width = $this->get_option('width');
                    $this->length = $this->get_option('length');
                    $this->height = $this->get_option('height');

                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array(
                        'BE','BG','CZ','DK','DE','EE','IE','EL','ES',
                        'FR','HR','IT','CY','LV','LT','LU','HU','MT',
                        'NL','AT','PL','PT','RO','SI','SK','FI','SE'
                    );
 
                    $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Cocolis Shipping', 'cocolis');
                    $this->init();
                }

                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                public function init()
                {
                    // Load the settings API
                    $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings

                    // Save settings in admin if you have any defined
                    add_action('woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ));
                    add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'validate_settings_fields' ));
                }

                public function init_form_fields()
                {
                    $this->form_fields = array(
                        'enabled' => array(
                            'title' => __('Enable', 'cocolis'),
                            'type' => 'checkbox',
                            'description' => __('Enable this shipping.', 'cocolis'),
                            'default' => 'no'
                        ),
                        'production_mode' => array(
                        'title'             => __('Mode'),
                        'type'              => 'select',
                        'description'       => __('Use this module in developement mode (sandbox) or in production ?'),
                        'desc_tip'          => true,
                        'default'           => 'sandbox',
                        'options' => array(
                            'sandbox' => 'Sandbox mode',
                            'production' => 'Production mode'),
                        'css'      => 'width:196px;',
                        ),
                        'app_id' => array(
                        'title'             => __('App id'),
                        'type'              => 'text',
                        'description'       => __('Enter the app-id provided to you by Cocolis'),
                        'desc_tip'          => true,
                        'default'           => 'app_id',
                        'css'      => 'width:196px;',
                        ),
                        'password' => array(
                        'title'             => __('Password'),
                        'type'              => 'password',
                        'description'       => __('Enter the password provided to you by Cocolis'),
                        'desc_tip'          => true,
                        'default'           => 'password',
                        'css'      => 'width:196px;',
                        ),
                        'width' => array(
                        'title'             => __('Default width in cm'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the width indicated in the product sheet.'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'height' => array(
                        'title'             => __('Default height in cm'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the height indicated in the product sheet.'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'length' => array(
                        'title'             => __('Default length in cm'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the length indicated in the product sheet.'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'email' => array(
                        'title'             => __('Email'),
                        'type'              => 'email',
                        'description'       => __("Required for the ride creation at Cocolis (vendor email)"),
                        'desc_tip'          => true,
                        'default'           => 'admin@vendor.com',
                        'css'      => 'width:196px;',
                        ),
                        'phone' => array(
                        'title'             => __('Phone'),
                        'type'              => 'tel',
                        'description'       => __("Required for the ride creation at Cocolis (landline or cell phone)"),
                        'desc_tip'          => true,
                        'default'           => '0600000000',
                        'css'      => 'width:196px;',
                        ),
                    );
                }

                /**
                 * Validate ids
                 * @see validate_settings_fields()
                 */
                public function validate_settings_fields($form_field = array())
                {
                    // get the posted value
                    $this->production_mode = $form_field['production_mode'] == "sandbox" ? false : true;
                    $this->app_id = $form_field['app_id'];
                    $this->password = $form_field['password'];
                    try {
                        $this->authenticatedClient();
                        $this->registerWebhooks();
                    } catch (\Cocolis\Api\Errors\UnauthorizedException $th) {
                        wp_die(__("The credentials provided are not recognized by the Cocolis API."), __("Authentication error on the API server"), ['response' => 401, 'back_link' => true]);
                        exit;
                    }
                    return $form_field;
                }

                public function authenticatedClient()
                {
                    $prod = $this->settings['production_mode'] == "sandbox" ? false : true;
                    $client = Client::create(array(
                        'app_id' => $this->settings['app_id'],
                        'password' => $this->settings['password'],
                        'live' => $prod,
                    ));
                    $client->signIn();
                    return $client;
                }

                public function registerWebhooks()
                {
                    $client = $this->authenticatedClient();
                    $webhooks = $client->getWebhookClient()->getAll();

                    if (!empty($webhooks)) {
                        foreach ($webhooks as $webhook) {
                            if (strpos($webhook->url, get_home_url()) !== true) {
                                $client->getWebhookClient()->update(
                                    [
                                    'event' => $webhook->event,
                                    'url' => get_home_url() . '/wp-json/cocolis/v1/webhook_' . $webhook->event,
                                    'active' => true
                                ],
                                    $webhook->id
                                );
                            }
                        }
                    } else {
                        $client->getWebhookClient()->create([
                    'event' => 'ride_published',
                    'url' => get_home_url() . '/wp-json/cocolis/v1/webhook_ride_published',
                    'active' => true
                ]);
                        $client->getWebhookClient()->create([
                    'event' => 'ride_expired',
                    'url' => get_home_url() . '/wp-json/cocolis/v1/webhook_ride_expired',
                    'active' => true
                ]);
                        $client->getWebhookClient()->create([
                    'event' => 'offer_accepted',
                    'url' => get_home_url() . '/wp-json/cocolis/v1/webhook_offer_accepted',
                    'active' => true
                ]);
                        $client->getWebhookClient()->create([
                    'event' => 'offer_cancelled',
                    'url' => get_home_url() . '/wp-json/cocolis/v1/webhook_offer_cancelled',
                    'active' => true
                ]);
                        $client->getWebhookClient()->create([
                    'event' => 'offer_completed',
                    'url' => get_home_url() . '/wp-json/cocolis/v1/webhook_offer_completed',
                    'active' => true
                ]);
                    }
                }

                /**
                 * calculate_shipping function.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping($package = array())
                {
                    $package = (object) $package;
                    $destination = (object) $package->destination;
                    $postcode = $destination->postcode;
                    $total = 0;
                    $dimensions = 0;
                    if (!empty($postcode)) {
                        $client = $this->authenticatedClient();
                        $products = $package->contents;
                        foreach ($products as $product) {
                            $product = (object) $product;
                            $width = (int) $product->data->get_width();
                            $length = (int) $product->data->get_length();
                            $height = (int) $product->data->get_height();

                            if ($width == 0 || $length == 0 || $height == 0) {
                                // Use the default value of volume for delivery fees
                                $width = $this->width;
                                $length = $this->length;
                                $height = $this->height;
                                $dimensions += (($width * $length * $height) / pow(10, 6)) * (int) $product->quantity;
                            } else {
                                $dimensions += (($width * $length * $height) / pow(10, 6)) * (int) $product->quantity;
                            }

                            $total += (int) $product->data->get_price() * (int) $product->quantity;
                        }
                        

                        if ($dimensions < 0.01) {
                            $dimensions += 0.01;
                        }

                        $dimensions = round($dimensions, 2);

                        $match = $client->getRideClient()->canMatch(get_option('woocommerce_store_postcode'), $postcode, $dimensions, $total * 100);
                        $shipping_cost = ($match->estimated_prices->regular) / 100;

                        $rate = array(
                            'id'   => 'cocolis',
                            'label' => '<svg viewBox="0 0 136.1 40" width="84" height="26"><path d="M107.9 10.1c2 0 3.6-1.6 3.6-3.6s-1.6-3.6-3.6-3.6-3.6 1.6-3.6 3.6 1.6 3.6 3.6 3.6m12.4 9c.7 0 1.4 0 2 .1l-2.5-5.3c-.4-.1-.8-.1-1.3-.1-4.3 0-6.5 2.6-6.5 6.5 0 2.2 2 7.2 2 9 0 1.2-.8 1.9-2.3 1.9-.6 0-1.7 0-2.7-.2l2.5 5.3c.6.1 1.2.2 1.9.2 4.4 0 6.7-2.7 6.7-6.4 0-2.7-2-7.2-2-9-.1-1 .5-2 2.2-2m-11.5-4.9h-6.1l-3.1 11.5-7.4 4.3 7.2-26.8h-6.1L86.1 30c-.3 1.1-.3 1.7-.3 2.3 0 2.3 1.9 4.2 4.3 4.2 1.2 0 2.2-.5 3.2-1.1l4.9-2.8c.1 2.2 1.9 3.9 4.3 3.9 1.2 0 2.2-.5 3.2-1.1l1.4-.8 1.9-7.1-4.3 2.5 4.1-15.8zM74 30.9c-3.2 0-5.7-2.4-5.7-5.8 0-3.5 2.6-5.8 5.7-5.8 3.2 0 5.8 2.4 5.8 5.8s-2.6 5.8-5.8 5.8m0-17.2c-6.5 0-11.8 5.1-11.8 11.4 0 1.2.2 2.3.5 3.4C61 30 59.1 31 56.5 31c-3.8 0-6-2.7-6-5.9s2.3-5.8 6.4-5.8c.8 0 1.7 0 2.7.2l-2.7-5.7c-.4-.1-.9-.1-1.3-.1-5.9 0-11.1 5.3-11.1 11.5 0 6.4 4.8 11.2 11.8 11.2 3.6 0 6.5-1.5 9-3.9 2.2 2.4 5.3 3.9 8.8 3.9 6.5 0 11.8-5.1 11.8-11.4-.1-6.2-5.4-11.3-11.9-11.3" fill="#484867"></path><path d="M31.4 30.9c-3.2 0-5.7-2.4-5.7-5.8 0-3.5 2.6-5.8 5.7-5.8 3.2 0 5.8 2.4 5.8 5.8s-2.6 5.8-5.8 5.8m0-17.2c-6.5 0-11.8 5.1-11.8 11.4 0 1.2.2 2.3.5 3.4-1.7 1.5-3.6 2.5-6.2 2.5-3.8 0-6-2.7-6-5.9s2.3-5.8 6.4-5.8c.8 0 1.7 0 2.7.2l-2.7-5.7c-.4-.1-.9-.1-1.3-.1-5.9 0-11.2 5.3-11.2 11.5 0 6.4 4.8 11.2 11.8 11.2 3.6 0 6.5-1.5 9-3.9 2.2 2.4 5.3 3.9 8.8 3.9 6.5 0 11.8-5.1 11.8-11.4.1-6.2-5.3-11.3-11.8-11.3" fill="#0069D8"></path></svg> ' . $this->title,
                            'cost' => $shipping_cost,
                        );

                        // Register the rate
                        $this->add_rate($rate);

                        $total = WC()->cart->get_subtotal();
                        
                        if ($total >= 500) {
                            $shipping_cost_insurance = ($match->estimated_prices->with_insurance) / 100;
                            $rate = array(
                                'id'   => 'cocolis_assurance',
                                'label' => $this->title . __(' with insurance'),
                                'cost' => $shipping_cost_insurance,
                            );
                            $this->add_rate($rate);
                        }
                    }
                }
            }
        }
    }

    function cocolis_activation_redirect($plugin)
    {
        if ($plugin == plugin_basename(__FILE__)) {
            exit(wp_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=cocolis-woocommerce')));
        }
    }


    function add_cocolis_shipping_method($methods)
    {
        $methods['add_cocolis_shipping_method'] = 'WC_Cocolis_Shipping_Method';
        return $methods;
    }


    function filter_woocommerce_cart_shipping_method_full_label($label, $method)
    {
        if ($method->id === "cocolis" || $method->id === "cocolis_assurance") {
            $label = '<svg style="vertical-align: middle;" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 30 34" width="30" height="34">
            <path d="M14.548 33.686a3.45 3.45 0 01-1.859-.536L1.751 26.56A3.667 3.667 0 010 23.442V10.255c0-.524.115-1.03.33-1.49a3.45 3.45 0 01.735-1.086l.03-.031c.199-.2.421-.372.664-.514L12.607.636a3.506 
            3.506 0 012.004-.624c.67 0 1.312.186 1.863.536l10.932 6.586c.246.148.469.325.666.523.36.35.645.776.828 1.252.165.416.26.87.26 1.346v13.187a3.613 3.613 0 01-1.794 3.147l-10.81 6.476c-.571.39-1.262.62-2.008.62m3.702-23.544c-.047-.028-.094-.06-.142-.09l-.216-.146" 
            fill="#0069D8"></path><path d="M5.95 17.103c0 4.757 3.58 8.329 8.732 8.329 3.747 0 6.554-2.243 8.812-5.395l-1.98-2.381c-2.04 2.047-3.776 3.735-6.64 3.735-2.78 0-4.435-1.991-4.435-4.426 0-2.38 1.736-4.316 4.739-4.316.578 0 1.266.028 2.01.167l-1.981-4.234a5.67 5.67 
            0 00-.993-.082c-4.354 0-8.264 3.928-8.264 8.603" fill="#FFFFFF"></path></svg> <b>' . $label . '</b>';
        }

        return $label;
    }

    function action_after_shipping_rate($method, $index)
    {
        // Targeting checkout page only:
        if (is_cart()) {
            return;
        } // Exit on cart page

        if ('cocolis' === $method->id) {
            echo __("<p>Livraison collaborative Cocolis assurée jusqu'à 500 euros. Pour en savoir plus, cliquez <a href='#'>ici</a></p>");
        }
    }

    add_action('woocommerce_after_shipping_rate', 'action_after_shipping_rate', 20, 2);

    add_filter('woocommerce_shipping_methods', 'add_cocolis_shipping_method');
    
    add_filter('woocommerce_cart_shipping_method_full_label', 'filter_woocommerce_cart_shipping_method_full_label', 10, 2);

    add_action('activated_plugin', 'cocolis_activation_redirect');

    add_action('woocommerce_shipping_init', 'cocolis_shipping_method_init');
}
