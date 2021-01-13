<?php
require_once dirname(__FILE__) . '/vendor/autoload.php';
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
                            'default' => 'yes'
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
                        'title'             => __('Width in cm'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the width indicated in the product sheet.'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'height' => array(
                        'title'             => __('Height in cm'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the height indicated in the product sheet.'),
                        'desc_tip'          => true,
                        'default'           => 1,
                        'css'      => 'width:196px;',
                        ),
                        'length' => array(
                        'title'             => __('Length in cm'),
                        'type'              => 'number',
                        'description'       => __('Allows you to calculate the costs in the absence of the length indicated in the product sheet.'),
                        'desc_tip'          => true,
                        'default'           => 1,
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
                    } catch (\Cocolis\Api\Errors\UnauthorizedException $th) {
                        wp_die("Les identifiants fournis ne sont pas reconnu par l'API de Cocolis", "Erreur d'authentification sur le serveur API", ['response' => 401, 'back_link' => true]);
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

                        $match = $client->getRideClient()->canMatch(75015, $postcode, $dimensions, $total * 100);
                        $shipping_cost = ($match->estimated_prices->regular) / 100;

                        $rate = array(
                            'id'   => '1',
                            'label' => $this->title,
                            'cost' => $shipping_cost,
                        );

                        // Register the rate
                        $this->add_rate($rate);
                        
                        if($total >= 500){
                            $shipping_cost_insurance = ($match->estimated_prices->with_insurance) / 100;
                            $rate = array(
                                'id'   => 'idassurance',
                                'label' => $this->title . ' with insurance',
                                'cost' => $shipping_cost_insurance,
                            );
                            $this->add_rate($rate);
                        }
                    }
                }
            }
        }
    }

    add_action('woocommerce_shipping_init', 'cocolis_shipping_method_init');

    function add_cocolis_shipping_method($methods)
    {
        $methods['add_cocolis_shipping_method'] = 'WC_Cocolis_Shipping_Method';
        return $methods;
    }

    add_filter('woocommerce_shipping_methods', 'add_cocolis_shipping_method');
}
