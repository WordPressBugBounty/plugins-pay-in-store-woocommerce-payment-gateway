<?php
/*
  Plugin Name: Pay in Store WooCommerce Payment Gateway
  Plugin URI: https://www.papaki.com
  Description: Provides a Pay in Store upon pick up Payment Gateway for Woocommerce.
  Version: 1.3.0
  Author: Papaki
  Author URI: https://www.papaki.com
  License: GPL-3.0+
  License URI: http://www.gnu.org/licenses/gpl-3.0.txt
  WC tested up to: 10.2.2
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
add_action( 'plugins_loaded', function () {
    if ( ! class_exists( '\WC_Payment_Gateway' ) ) {
        add_action( 'admin_notices', function () {
            echo '<div class="notice notice-error"><p>';
            echo __( 'PayInStore WooCommerce Payment Gateway requires WooCommerce to be installed and active.', 'woo-payment-gateway-for-payinstore' );
            echo '</p></div>';
        } );
        return;
    }

    spl_autoload_register( function ( $class ) {
        $prefix   = 'Papaki\\PayInStore\\WooCommerce\\';
        $base_dir = plugin_dir_path( __FILE__ ) . 'classes/';

        $len = strlen( $prefix );
        if ( strncmp( $prefix, $class, $len ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, $len );
        $file           = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    } );

    new \Papaki\PayInStore\WooCommerce\Application( plugin_basename( __FILE__ ) );
}, 0 );
