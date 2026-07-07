<?php

namespace Papaki\PayInStore\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Application {
    public const PLUGIN_TITLE = 'Pay in Store';
    public const TEXT_DOMAIN  = 'woocommerce';

    private $entrypoint_path;

    public function __construct( $entrypoint ) {
        $this->entrypoint_path = $entrypoint;

        $this->init();

        $checkout_block = new Checkout_Block( $entrypoint );
        $checkout_block->init();
    }

    public function init() {
        add_filter( 'woocommerce_payment_gateways', [ $this, 'woocommerce_add_payinstore_gateway' ] );
    }

    /**
     * Add PayInStore Gateway to WC
     *
     * @param array $methods
     * @return array
     * */
    public function woocommerce_add_payinstore_gateway( $methods ) {
        $methods[] = '\Papaki\PayInStore\WooCommerce\WC_Gateway_PayInStore';
        return $methods;
    }
}
