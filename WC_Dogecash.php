<?php

if (class_exists('WC_Payment_Gateway')) {
    class WC_Dogecash extends WC_Payment_Gateway{

        const DOGEC_API_URL = "https://payment-checker.dogecash.org/";

        public function __construct(){
            $this->id = 'degecash_payment';
            $this->method_title = __('DogeCash cryptocurrency payment','woocommerce-dogecash');
            $this->method_description = __('DogeCash Payment Gateway allows you to receive payments in DOGEC cryptocurrency','woocommerce-dogecash');
            $this->has_fields = true;
            $this->init_form_fields();
            $this->init_settings();
            $this->enabled = $this->get_option('enabled');
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->payment_address = $this->get_option('payment_address');
            $this->confirmation_no = $this->get_option('confirmation_no');
            $this->max_time_limit = $this->get_option('max_time_limit');
            $this->cryptocurrency_used = "DOGEC";
            $this->default_currency_used = get_woocommerce_currency();
            $this->exchange_rate = $this->dogec_exchange_rate($this->default_currency_used);
            $this->plugin_version = "1.0.2";

            // Add support for "Woocommerce subscriptions" plugin
            $this->dogec_remove_filter( 'template_redirect', 'maybe_setup_cart', 100 );
            $this->supports = array(
               'products',
               'subscriptions',
            );

            add_action('woocommerce_update_options_payment_gateways_'.$this->id, array($this, 'process_admin_options'));
        }
        public function init_form_fields(){
                    $this->form_fields = array(
                        'enabled' => array(
                            'title'         => __( 'Enable/Disable', 'woocommerce-dogecash' ),
                            'type'          => 'checkbox',
                            'label'         => __( 'Enable DogeCash Cryptocurrency Payment', 'woocommerce-dogecash' ),
                            'default'       => 'yes'
                        ),
                        'title' => array(
                            'title'         => __( 'Method Title', 'woocommerce-dogecash' ),
                            'type'          => 'text',
                            'default'       => __( 'DogeCash Cryptocurrency Payment', 'woocommerce-dogecash' ),
                            'desc_tip'   => __( 'The payment method title which you want to appear to the customer in the checkout page.'),
                        ),
                        'description' => array(
                            'title' => __( 'Payment Description', 'woocommerce-dogecash' ),
                            'type' => 'text',
                            'default' => 'Please send the exact amount in DOGEC to the payment address bellow.',
                            'desc_tip'   => __( 'The payment description message which you want to appear to the customer on the payment page. You can pass a thank you note as well.' ),
                        ),
                        'payment_address' => array(
                            'title' => __( 'DogeCash Wallet Address', 'woocommerce-dogecash' ),
                            'type' => 'text',
                            'desc_tip'   => __( 'DogeCash wallet address where you will receive DOGEC from sales.' ),
                        ),
                        'confirmation_no' => array(
                            'title' => __( 'Minimum Confirmations', 'woocommerce-dogecash' ),
                            'type' => 'text',
                            'default' => '5',
                            'desc_tip'  => __( 'Number of confirmations upon which the order will be considered as confirmed.' ),
                        ),
                         'max_time_limit' => array(
                            'title' => __( 'Maximum Payment Time (in Minutes)', 'woocommerce-dogecash' ),
                            'type' => 'text',
                            'default' => "15",
                            'desc_tip' => __( 'Time allowed for a user to make the required payment.' ),
                        )
                 );
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'title' and availability on a country-by-country basis
         *
         * @since 1.0.0
         * @return void
         */
        public function admin_options() {
            ?>
                <h3><?php _e('DogeCash Payment Settings', 'woocommerce-dogecash' ); ?></h3>
                <p>DogeCash Payment Gateway allows you to receive payments in DOGEC cryptocurrency</p>
                <table class="form-table">
                    <?php $this->generate_settings_html();?>
                </table>
            <?php
        }


        // Process payment
        public function process_payment( $order_id ) {

            global $woocommerce;
            $order = new WC_Order( $order_id );

             // Reduce stock levels for the product
            wc_reduce_stock_levels( $order_id );

            // Empty cart after payment
            $woocommerce->cart->empty_cart();

            // Redirect to order-pay page
            return array(
                'result' => 'success',
                'redirect' =>  $order->get_checkout_payment_url( $on_checkout = false ) . '&cp=1'
               );
        }


        // Payent method structure on the checkout page
        public function payment_fields(){
            ?>
                <fieldset style="padding: 0.75em 0.625em 0.75em;">
                    <table>
                        <tr style="vertical-align: middle; text-align: left;">
                            <td width="180">
                                <img alt="plugin logo" width="160" style="max-height: 40px;" src="<?php echo plugins_url('/woocommerce-dogecash/img/plugin-logo.png') ?>">
                            </td>
                            <td>
                                <div>Exchange rate:</div>
                                <strong> 1 <?php echo $this->cryptocurrency_used; ?> = <?php echo round($this->exchange_rate, 5); ?> <?php echo $this->default_currency_used; ?></strong>
                            </td>
                        </tr>
                    </table>
                </fieldset>
            <?php
        }


        // Exchange rate in the default store currency
        public function dogec_exchange_rate($default_currency) {
    		    static $rate;

            if ( $rate !== null ) {
                return $rate['result'];
            }

            if ( is_checkout() ) {
                $rate = file_get_contents(DOGEC_API_URL ."?rate=" . $default_currency);
                $rate = json_decode($rate, true);
                return $rate['result'];
            }
        }


        // Remove filters
        function dogec_remove_filter( $hook_name = '', $method_name = '', $priority = 0 ) {
            global $wp_filter;
            global $wp;

            // Remove filter unders plugin specific conditions
            if ( isset( $_GET['pay_for_order'] ) && isset( $_GET['key'] ) && isset( $wp->query_vars['order-pay'] )  && isset( $_GET['cp'] ) ) {

                // Take only filters on right hook name and priority
                if ( ! isset( $wp_filter[ $hook_name ][ $priority ] ) || ! is_array( $wp_filter[ $hook_name ][ $priority ] ) ) {
                    return false;
                }

                // Loop on filters registered
                foreach ( (array) $wp_filter[ $hook_name ][ $priority ] as $unique_id => $filter_array ) {
                    // Test if filter is an array (always for class/method)
                    if ( isset( $filter_array['function'] ) && is_array( $filter_array['function'] ) ) {
                        // Test if object is a class and method is equal to param
                        if ( is_object( $filter_array['function'][0] ) && get_class( $filter_array['function'][0] ) && $filter_array['function'][1] == $method_name ) {
                            // Test for WordPress >= 4.7 WP_Hook class
                            if ( is_a( $wp_filter[ $hook_name ], 'WP_Hook' ) ) {
                                unset( $wp_filter[ $hook_name ]->callbacks[ $priority ][ $unique_id ] );
                            } else {
                                unset( $wp_filter[ $hook_name ][ $priority ][ $unique_id ] );
                            }
                        }
                    }
                }
            }

            return false;
        }


    }
}
?>
