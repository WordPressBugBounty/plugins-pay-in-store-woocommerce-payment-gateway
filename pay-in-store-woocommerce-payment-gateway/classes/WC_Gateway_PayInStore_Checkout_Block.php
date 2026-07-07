<?php

namespace Papaki\PayInStore\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( class_exists( '\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {

    final class WC_Gateway_PayInStore_Checkout_Block extends \Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType {
        private $gateway;
        protected $name = 'payinstore';

        public function initialize() {
            $this->gateway = new WC_Gateway_PayInStore();
        }

        public function is_active() {
            return $this->gateway->is_available();
        }

        public function get_payment_method_script_handles() {
            $handle = $this->name . '_gc-blocks-integration';

            wp_register_script(
                $handle,
                plugin_dir_url( __FILE__ ) . '../assets/js/blocks/checkout.js',
                [
                    'wc-blocks-registry',
                    'wc-settings',
                    'wp-element',
                    'wp-html-entities',
                    'wp-i18n',
                ],
                null,
                true
            );

            return [ $handle ];
        }

        public function get_payment_method_data() {
            $data = [
                'title'       => $this->gateway->title,
                'description' => $this->gateway->description,
            ];

            if ( ! empty( $this->gateway->icon ) ) {
                $data['icon'] = $this->gateway->icon;
            }

            return $data;
        }
    }

} else {

    final class WC_Gateway_PayInStore_Checkout_Block {
        public function __construct() {
            // Do nothing - blocks not supported
        }
    }

}
