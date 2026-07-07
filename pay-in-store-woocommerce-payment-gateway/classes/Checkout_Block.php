<?php

namespace Papaki\PayInStore\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Checkout_Block {
    const TEXT_DOMAIN = Application::TEXT_DOMAIN;
    private $entrypoint_path;

    public function __construct( $entrypoint ) {
        $this->entrypoint_path = $entrypoint;
    }

    public function init() {
        add_action( 'before_woocommerce_init', [ $this, 'declare_cart_checkout_blocks_compatibility' ] );
        add_action( 'woocommerce_blocks_loaded', [ $this, 'woo_register_order_approval_payment_method_type' ] );
    }

    /**
     * Custom function to declare compatibility with cart_checkout_blocks feature
     */
    public function declare_cart_checkout_blocks_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', $this->entrypoint_path, true );
        }
    }

    /**
     * Custom function to register a payment method type
     */
    public function woo_register_order_approval_payment_method_type() {
        if ( ! class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
            return;
        }

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
                // Register an instance of WC_Gateway_PayInStore_Checkout_Block
                $payment_method_registry->register( new WC_Gateway_PayInStore_Checkout_Block );
            }
        );
    }
}
