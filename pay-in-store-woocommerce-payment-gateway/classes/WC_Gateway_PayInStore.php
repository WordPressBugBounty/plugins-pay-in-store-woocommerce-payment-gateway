<?php

namespace Papaki\PayInStore\WooCommerce;
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Pay In Store Payment Gateway.
 *
 * @property string $instructions
 * @property array $enable_for_methods
 * @property bool $enable_for_virtual
 */
class WC_Gateway_PayInStore extends \WC_Payment_Gateway {
    const PLUGIN_TITLE = Application::PLUGIN_TITLE;
    const TEXT_DOMAIN  = Application::TEXT_DOMAIN;

    protected string $instructions;
    protected array $enable_for_methods;
    protected bool $enable_for_virtual;

    public function __construct() {
        $this->id                 = 'payinstore';
        $this->icon               = apply_filters( 'woocommerce_payinstore_icon', '' );
        $this->method_title       = __( static::PLUGIN_TITLE, static::TEXT_DOMAIN );
        $this->method_description = __( 'Have your customers pay with cash (or by other means) in store upon  pickup.', static::TEXT_DOMAIN );
        $this->has_fields         = false;

        // Load the settings
        $this->init_form_fields();
        $this->init_settings();

        // Get settings
        $this->title              = sanitize_text_field( $this->get_option( 'title' ) );
        $this->description        = sanitize_textarea_field( $this->get_option( 'description' ) );
        $this->instructions       = wp_kses_post( $this->get_option( 'instructions', $this->description ) );
        $this->enable_for_methods = (array) $this->get_option( 'enable_for_methods', array() );
        $this->enable_for_virtual = $this->get_option( 'enable_for_virtual', 'yes' ) === 'yes' ? true : false;

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_thankyou_payinstore', array( $this, 'thankyou_page' ) );

        // Customer Emails
        add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $shipping_methods = array();

        if ( is_admin() ) {
            foreach (WC()->shipping()->load_shipping_methods() as $method) {
                $shipping_methods[ $method->id ] = $method->get_method_title();
            }
        }

        $this->form_fields = array(
            'enabled'            => array(
                'title'       => __( 'Enable Pay in Store', static::TEXT_DOMAIN ),
                'label'       => __( 'Enable Pay in Store', static::TEXT_DOMAIN ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
            'title'              => array(
                'title'       => __( 'Title', static::TEXT_DOMAIN ),
                'type'        => 'text',
                'description' => __( 'Payment method description that the customer will see on your checkout.', static::TEXT_DOMAIN ),
                'default'     => __( 'Pay in Store', static::TEXT_DOMAIN ),
                'desc_tip'    => true,
            ),
            'description'        => array(
                'title'       => __( 'Description', static::TEXT_DOMAIN ),
                'type'        => 'textarea',
                'description' => __( 'Payment method description that the customer will see on your website.', static::TEXT_DOMAIN ),
                'default'     => __( 'Pay with cash upon delivery.', static::TEXT_DOMAIN ),
                'desc_tip'    => true,
            ),
            'instructions'       => array(
                'title'       => __( 'Instructions', static::TEXT_DOMAIN ),
                'type'        => 'textarea',
                'description' => __( 'Instructions that will be added to the thank you page.', static::TEXT_DOMAIN ),
                'default'     => __( 'Pay with cash upon delivery.', static::TEXT_DOMAIN ),
                'desc_tip'    => true,
            ),
            'enable_for_methods' => array(
                'title'             => __( 'Enable for shipping methods', static::TEXT_DOMAIN ),
                'type'              => 'multiselect',
                'class'             => 'wc-enhanced-select',
                'css'               => 'width: 450px;',
                'default'           => '',
                'description'       => __( 'If Pay in Store is only available for certain methods, set it up here. Leave blank to enable for all methods.', static::TEXT_DOMAIN ),
                'options'           => $shipping_methods,
                'desc_tip'          => true,
                'custom_attributes' => array(
                    'data-placeholder' => __( 'Select shipping methods', static::TEXT_DOMAIN ),
                ),
            ),
            'enable_for_virtual' => array(
                'title'   => __( 'Accept for virtual orders', static::TEXT_DOMAIN ),
                'label'   => __( 'Accept Pay in Store if the order is virtual', static::TEXT_DOMAIN ),
                'type'    => 'checkbox',
                'default' => 'yes',
            ),
        );
    }

    /**
     * Check If The Gateway Is Available For Use.
     *
     * @return bool
     */
    public function is_available() {
        $order          = null;
        $needs_shipping = false;
        // Test if shipping is needed first
        if ( WC()->cart && WC()->cart->needs_shipping() ) {
            $needs_shipping = true;
        } elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
            $order_id = absint( get_query_var( 'order-pay' ) );
            $order    = wc_get_order( $order_id );

            // Test if order needs shipping.
            if ( 0 < sizeof( $order->get_items() ) ) {
                foreach ($order->get_items() as $item) {
                    if ( $item instanceof \WC_Order_Item_Product ) {
                        $_product = $item->get_product();
                        if ( $_product && $_product->needs_shipping() ) {
                            $needs_shipping = true;
                            break;
                        }
                    }
                }
            }
        }

        $needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );


        // Virtual order, with virtual disabled
        if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
            return false;
        }

        // Check methods
        if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
            // Only apply if all packages are being shipped via chosen methods, or order is virtual
            $chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

            if ( isset( $chosen_shipping_methods_session ) ) {
                $chosen_shipping_methods = array_unique( $chosen_shipping_methods_session );
            } else {
                $chosen_shipping_methods = array();
            }

            $check_method = false;

            if ( is_object( $order ) ) {
                if ( $order->shipping_method ) {
                    $shipping_items = $order->get_items( 'shipping' );
                    $shipping_item  = reset( $shipping_items );
                    if ( $shipping_item instanceof \WC_Order_Item_Shipping ) {
                        $check_method = $shipping_item->get_method_id();
                    }
                }
            } elseif ( empty( $chosen_shipping_methods ) || sizeof( $chosen_shipping_methods ) > 1 ) {
                $check_method = false;
            } elseif ( sizeof( $chosen_shipping_methods ) == 1 ) {
                $check_method = $chosen_shipping_methods[0];
            }

            if ( ! $check_method ) {
                return false;
            }

            $found = false;

            foreach ($this->enable_for_methods as $method_id) {
                if ( strpos( $check_method, $method_id ) === 0 ) {
                    $found = true;
                    break;
                }
            }

            if ( ! $found ) {
                return false;
            }
        }

        return parent::is_available();
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Mark as processing or on-hold (payment won't be taken until delivery)
        $order->update_status( apply_filters( 'woocommerce_payinstore_process_payment_order_status', $order->has_downloadable_item() ? 'on-hold' : 'processing', $order ), __( 'Payment to be made upon pick up from store.', static::TEXT_DOMAIN ) );

        // Reduce stock levels
        wc_reduce_stock_levels( $order_id );

        // Remove cart
        WC()->cart->empty_cart();

        // Return thankyou redirect
        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url( $order ),
        );
    }

    /**
     * Output for the order received page.
     */
    public function thankyou_page() {
        if ( $this->instructions ) {
            echo wpautop( wptexturize( $this->instructions ) );
        }
    }

    /**
     * Add content to the WC emails.
     *
     * @access public
     * @param \WC_Order $order
     * @param bool $sent_to_admin
     * @param bool $plain_text
     */
    public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
        if ( method_exists( $order, 'get_payment_method' ) ) {
            $payment_method = $order->get_payment_method();
        } else {
            $payment_method = $order->payment_method;
        }
        if ( $this->instructions && ! $sent_to_admin && 'payinstore' === $payment_method ) {
            echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
        }
    }
}
