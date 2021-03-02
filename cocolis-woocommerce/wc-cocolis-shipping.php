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
                    $this->method_title       = __('Cocolis Shipping Method', 'cocolis-woocommerce');
                    $this->method_description = __('Cocolis Woocommerce Plugin to add Cocolis.fr as a delivery method', 'cocolis-woocommerce');
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
                    $this->title = isset($this->settings['title']) ? $this->settings['title'] : __('Cocolis Shipping', 'cocolis-woocommerce');
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
                            'title' => __('Enable', 'cocolis-woocommerce'),
                            'type' => 'checkbox',
                            'description' => __('Enable this shipping.', 'cocolis-woocommerce'),
                            'default' => 'no'
                        ),
                        'production_mode' => array(
                        'title'             => __('Mode', 'cocolis-woocommerce'),
                        'type'              => 'select',
                        'description'       => __('Use this module in developement mode (sandbox) or in production ?', 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 'sandbox',
                        'options' => array(
                            'sandbox' => __('Sandbox', 'cocolis-woocommerce'),
                            'production' => __('Production', 'cocolis-woocommerce')),
                        'css'      => 'width:196px;',
                        ),
                        'app_id' => array(
                        'title'             => __('App id', 'cocolis-woocommerce'),
                        'type'              => 'text',
                        'description'       => __('Enter the app-id provided to you by Cocolis', 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 'app_id',
                        'css'      => 'width:196px;',
                        ),
                        'password' => array(
                        'title'             => __('Password', 'cocolis-woocommerce'),
                        'type'              => 'password',
                        'description'       => __('Enter the password provided to you by Cocolis', 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 'password',
                        'css'      => 'width:196px;',
                        ),
                        'width' => array(
                        'title'             => __('Default width in cm', 'cocolis-woocommerce'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the width indicated in the product sheet.', 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'height' => array(
                        'title'             => __('Default height in cm', 'cocolis-woocommerce'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the height indicated in the product sheet.', 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'length' => array(
                        'title'             => __('Default length in cm', 'cocolis-woocommerce'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the length indicated in the product sheet.', 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'email' => array(
                        'title'             => __('Email', 'cocolis-woocommerce'),
                        'type'              => 'email',
                        'description'       => __("Required for the ride creation at Cocolis (vendor email)", 'cocolis-woocommerce'),
                        'desc_tip'          => true,
                        'default'           => 'admin@vendor.com',
                        'css'      => 'width:196px;',
                        ),
                        'phone' => array(
                        'title'             => __('Phone', 'cocolis-woocommerce'),
                        'type'              => 'tel',
                        'description'       => __("Required for the ride creation at Cocolis (landline or cell phone)", 'cocolis-woocommerce'),
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
                        wp_die(__("The credentials provided are not recognized by the Cocolis API.", 'cocolis-woocommerce'), __("Authentication error on the API server", 'cocolis-woocommerce'), ['response' => 401, 'back_link' => true]);
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
                                'label' => $this->title . __(' with insurance', 'cocolis-woocommerce'),
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
        if ($method->id === "cocolis") {
            $label = '<svg version="1.1" id="Calque_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
            viewBox="0 0 32 32" style="enable-background:new 0 0 32 32; vertical-align: middle;" width="42" height="42" xml:space="preserve">
            <style type="text/css">
                .st0{fill:#484867;}
                .st1{fill:#0069D8;}
                .st2{fill:#FFFFFF;}
            </style>
            <path class="st0" d="M23.6,25.8c0.3,0,0.6-0.3,0.6-0.6c0-0.3-0.3-0.6-0.6-0.6c-0.3,0-0.6,0.3-0.6,0.6C23,25.5,23.3,25.8,23.6,25.8z
                    M25.7,27.4c0.1,0,0.2,0,0.3,0l-0.4-0.9c-0.1,0-0.1,0-0.2,0c-0.7,0-1.1,0.4-1.1,1.1c0,0.4,0.3,1.2,0.3,1.6c0,0.2-0.1,0.3-0.4,0.3
                c-0.1,0-0.3,0-0.4,0l0.4,0.9c0.1,0,0.2,0,0.3,0c0.7,0,1.1-0.5,1.1-1.1c0-0.5-0.3-1.2-0.3-1.6C25.3,27.5,25.4,27.4,25.7,27.4
                L25.7,27.4z M23.8,26.5h-1l-0.5,2L21,29.2l1.2-4.6h-1L20,29.2c0,0.2,0,0.3,0,0.4c0,0.4,0.3,0.7,0.7,0.7c0.2,0,0.4-0.1,0.5-0.2
                l0.8-0.5c0,0.4,0.3,0.7,0.7,0.7c0.2,0,0.4-0.1,0.5-0.2l0.2-0.1l0.3-1.2l-0.7,0.4L23.8,26.5L23.8,26.5z M18,29.4
                c-0.5,0-0.9-0.4-0.9-1c0-0.6,0.4-1,0.9-1c0.5,0,1,0.4,1,1C18.9,29,18.5,29.4,18,29.4z M18,26.4c-1.1,0-2,0.9-2,2
                c0,0.2,0,0.4,0.1,0.6c-0.3,0.3-0.6,0.4-1,0.4c-0.6,0-1-0.5-1-1c0-0.6,0.4-1,1.1-1c0.1,0,0.3,0,0.4,0l-0.4-1c-0.1,0-0.1,0-0.2,0
                c-1,0-1.8,0.9-1.8,2c0,1.1,0.8,1.9,2,1.9c0.6,0,1.1-0.3,1.5-0.7c0.4,0.4,0.9,0.7,1.5,0.7c1.1,0,2-0.9,2-2
                C19.9,27.3,19.1,26.4,18,26.4"/>
            <path class="st1" d="M10.9,29.4c-0.5,0-0.9-0.4-0.9-1c0-0.6,0.4-1,0.9-1c0.5,0,1,0.4,1,1C11.9,29,11.4,29.4,10.9,29.4L10.9,29.4z
                    M10.9,26.4c-1.1,0-2,0.9-2,2C9,28.6,9,28.8,9,29c-0.3,0.3-0.6,0.4-1,0.4c-0.6,0-1-0.5-1-1c0-0.6,0.4-1,1.1-1c0.1,0,0.3,0,0.4,0
                l-0.4-1c-0.1,0-0.1,0-0.2,0c-1,0-1.9,0.9-1.9,2c0,1.1,0.8,1.9,2,1.9c0.6,0,1.1-0.3,1.5-0.7c0.4,0.4,0.9,0.7,1.5,0.7c1.1,0,2-0.9,2-2
                C12.9,27.3,12,26.4,10.9,26.4"/>
            <path class="st1" d="M16,23.2c-0.5,0-0.9-0.1-1.3-0.4l-7.5-4.4c-0.4-0.2-0.7-0.5-0.9-0.9C6.1,17.3,6,16.9,6,16.5V7.8
                c0-0.3,0.1-0.7,0.2-1c0.1-0.3,0.3-0.5,0.5-0.7l0,0C6.9,5.9,7,5.8,7.2,5.7l7.4-4.3C15.1,1.2,15.5,1,16,1c0.5,0,0.9,0.1,1.3,0.4
                l7.5,4.3c0.2,0.1,0.3,0.2,0.5,0.3c0.2,0.2,0.4,0.5,0.6,0.8C25.9,7.2,26,7.5,26,7.8v8.7c0,0.4-0.1,0.8-0.3,1.2
                c-0.2,0.4-0.5,0.7-0.9,0.9l-7.4,4.3C17,23.1,16.5,23.2,16,23.2L16,23.2z M18.5,7.7C18.5,7.7,18.5,7.7,18.5,7.7l-0.2-0.2"/>
            <path class="st2" d="M10.1,12.3c0,3.1,2.5,5.5,6,5.5c2.6,0,4.5-1.5,6-3.6l-1.4-1.6c-1.4,1.4-2.6,2.5-4.6,2.5c-1.9,0-3-1.3-3-2.9
                c0-1.6,1.2-2.8,3.3-2.8c0.4,0,0.9,0,1.4,0.1l-1.4-2.8c-0.2,0-0.5-0.1-0.7-0.1C12.8,6.6,10.1,9.2,10.1,12.3"/>
            </svg>                  
            <b> ' . $label . "</b> </br>" . __("Insurance included up to 500 €", 'cocolis-woocommerce');
        } elseif ($method->id === "cocolis_assurance") {
            $total = WC()->cart->get_subtotal();
            // Maximal cost insurance
            if ($total <= 1500) {
                $max_value = 1500;
            } elseif ($total <= 3000) {
                $max_value = 3000;
            } elseif ($total <= 5000) {
                $max_value = 5000;
            } else {
                $max_value = 5000;
            }

            $label = '<svg version="1.1" id="Calque_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px"
            viewBox="0 0 32 32" style="enable-background:new 0 0 32 32; vertical-align: middle;" width="42" height="42" xml:space="preserve">
            <style type="text/css">
                .st0{fill:#484867;}
                .st1{fill:#0069D8;}
                .st2{fill:#FFFFFF;}
            </style>
            <path class="st0" d="M23.6,25.8c0.3,0,0.6-0.3,0.6-0.6c0-0.3-0.3-0.6-0.6-0.6c-0.3,0-0.6,0.3-0.6,0.6C23,25.5,23.3,25.8,23.6,25.8z
                    M25.7,27.4c0.1,0,0.2,0,0.3,0l-0.4-0.9c-0.1,0-0.1,0-0.2,0c-0.7,0-1.1,0.4-1.1,1.1c0,0.4,0.3,1.2,0.3,1.6c0,0.2-0.1,0.3-0.4,0.3
                c-0.1,0-0.3,0-0.4,0l0.4,0.9c0.1,0,0.2,0,0.3,0c0.7,0,1.1-0.5,1.1-1.1c0-0.5-0.3-1.2-0.3-1.6C25.3,27.5,25.4,27.4,25.7,27.4
                L25.7,27.4z M23.8,26.5h-1l-0.5,2L21,29.2l1.2-4.6h-1L20,29.2c0,0.2,0,0.3,0,0.4c0,0.4,0.3,0.7,0.7,0.7c0.2,0,0.4-0.1,0.5-0.2
                l0.8-0.5c0,0.4,0.3,0.7,0.7,0.7c0.2,0,0.4-0.1,0.5-0.2l0.2-0.1l0.3-1.2l-0.7,0.4L23.8,26.5L23.8,26.5z M18,29.4
                c-0.5,0-0.9-0.4-0.9-1c0-0.6,0.4-1,0.9-1c0.5,0,1,0.4,1,1C18.9,29,18.5,29.4,18,29.4z M18,26.4c-1.1,0-2,0.9-2,2
                c0,0.2,0,0.4,0.1,0.6c-0.3,0.3-0.6,0.4-1,0.4c-0.6,0-1-0.5-1-1c0-0.6,0.4-1,1.1-1c0.1,0,0.3,0,0.4,0l-0.4-1c-0.1,0-0.1,0-0.2,0
                c-1,0-1.8,0.9-1.8,2c0,1.1,0.8,1.9,2,1.9c0.6,0,1.1-0.3,1.5-0.7c0.4,0.4,0.9,0.7,1.5,0.7c1.1,0,2-0.9,2-2
                C19.9,27.3,19.1,26.4,18,26.4"/>
            <path class="st1" d="M10.9,29.4c-0.5,0-0.9-0.4-0.9-1c0-0.6,0.4-1,0.9-1c0.5,0,1,0.4,1,1C11.9,29,11.4,29.4,10.9,29.4L10.9,29.4z
                    M10.9,26.4c-1.1,0-2,0.9-2,2C9,28.6,9,28.8,9,29c-0.3,0.3-0.6,0.4-1,0.4c-0.6,0-1-0.5-1-1c0-0.6,0.4-1,1.1-1c0.1,0,0.3,0,0.4,0
                l-0.4-1c-0.1,0-0.1,0-0.2,0c-1,0-1.9,0.9-1.9,2c0,1.1,0.8,1.9,2,1.9c0.6,0,1.1-0.3,1.5-0.7c0.4,0.4,0.9,0.7,1.5,0.7c1.1,0,2-0.9,2-2
                C12.9,27.3,12,26.4,10.9,26.4"/>
            <path class="st1" d="M16,23.2c-0.5,0-0.9-0.1-1.3-0.4l-7.5-4.4c-0.4-0.2-0.7-0.5-0.9-0.9C6.1,17.3,6,16.9,6,16.5V7.8
                c0-0.3,0.1-0.7,0.2-1c0.1-0.3,0.3-0.5,0.5-0.7l0,0C6.9,5.9,7,5.8,7.2,5.7l7.4-4.3C15.1,1.2,15.5,1,16,1c0.5,0,0.9,0.1,1.3,0.4
                l7.5,4.3c0.2,0.1,0.3,0.2,0.5,0.3c0.2,0.2,0.4,0.5,0.6,0.8C25.9,7.2,26,7.5,26,7.8v8.7c0,0.4-0.1,0.8-0.3,1.2
                c-0.2,0.4-0.5,0.7-0.9,0.9l-7.4,4.3C17,23.1,16.5,23.2,16,23.2L16,23.2z M18.5,7.7C18.5,7.7,18.5,7.7,18.5,7.7l-0.2-0.2"/>
            <path class="st2" d="M10.1,12.3c0,3.1,2.5,5.5,6,5.5c2.6,0,4.5-1.5,6-3.6l-1.4-1.6c-1.4,1.4-2.6,2.5-4.6,2.5c-1.9,0-3-1.3-3-2.9
                c0-1.6,1.2-2.8,3.3-2.8c0.4,0,0.9,0,1.4,0.1l-1.4-2.8c-0.2,0-0.5-0.1-0.7-0.1C12.8,6.6,10.1,9.2,10.1,12.3"/>
            </svg>                  
            <b> ' . $label . "</b> <br>" . __("Insurance included up to ", 'cocolis-woocommerce') . $max_value . " €";
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
            // Waiting GitHub returns
        }
    }

    function language_init() {
        load_plugin_textdomain( 'cocolis-woocommerce', false, 'cocolis-woocommerce/languages' );
      }
      
    add_action('init', 'language_init');

    add_action('woocommerce_after_shipping_rate', 'action_after_shipping_rate', 20, 2);

    add_filter('woocommerce_shipping_methods', 'add_cocolis_shipping_method');
    
    add_filter('woocommerce_cart_shipping_method_full_label', 'filter_woocommerce_cart_shipping_method_full_label', 10, 2);

    add_action('activated_plugin', 'cocolis_activation_redirect');

    add_action('woocommerce_shipping_init', 'cocolis_shipping_method_init');
}
