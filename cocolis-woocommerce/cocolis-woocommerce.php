<?php

/**
 * Plugin Name: Cocolis Woocommerce
 * Plugin URI: https://www.cocolis.fr
 * Description: A plugin to add Cocolis.fr as a carrier on Woocommerce
 * Author:  Cocolis.fr
 * Author URI: https://www.cocolis.fr
 * Version: 1.0
 */
if ( ! class_exists( 'WC_cocolis_woocommerce' ) ) :
class WC_cocolis_woocommerce {
  /**
  * Construct the plugin.
  */
  public function __construct() {
    add_action( 'plugins_loaded', array( $this, 'init' ) );
  }
  /**
  * Initialize the plugin.
  */
  public function init() {
    // Checks if WooCommerce is installed.
    if ( class_exists( 'WC_Integration' ) ) {
      // Include our integration class.
      include_once 'class-wc-integration-demo-integration.php';
      // Register the integration.
      add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );
    }
  }
  /**
   * Add a new integration to WooCommerce.
   */
  public function add_integration( $integrations ) {
    $integrations[] = 'WC_Cocolis_woocommerce_Integration';
    return $integrations;
  }
}
$WC_cocolis_woocommerce = new WC_cocolis_woocommerce( __FILE__ );
endif;