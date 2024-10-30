<?php
class WC_Gateway_CdlCheckout extends WC_Payment_Gateway {

    public $testmode;
    public $autocomplete_order;
    public $payment_page;
    public $public_key;
    public $secret_key;
    public $enabled;
    public $msg;
    public $remove_cancel_order_button;

    public function __construct() {
        $this->id                 = 'cdl_checkout';
        $this->has_fields = false;
        $this->method_title       = esc_html__('CDL Checkout Payment Gateway', 'cdl-checkout-for-woocommerce');
        $this->method_description = esc_html__('Make payment using your debit and credit cards', 'cdl-checkout-for-woocommerce');

        $this->supports = array(
            'products',
        );

        $this->init_form_fields();
        $this->init_settings();

        $this->title              = $this->get_option('title');
        $this->description        = $this->get_option('description');
        $this->enabled            = $this->get_option('enabled');
        $this->testmode           = $this->get_option('testmode') === 'yes' ? true : false;
        $this->autocomplete_order = $this->get_option('autocomplete_order') === 'yes' ? true : false;
        $this->remove_cancel_order_button = $this->get_option( 'remove_cancel_order_button' ) === 'yes' ? true : false;

        $this->public_key = $this->get_option('public_key');
        $this->secret_key = $this->get_option('secret_key');

        // Hooks
        add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
        add_action('admin_enqueue_scripts', array( $this, 'admin_scripts' ));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action( 'admin_notices', array( $this, 'admin_notices' ) );
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Webhook listener/API hook.
        add_action( 'woocommerce_api_cdl_checkout_wc_payment_webhook', array( $this, 'process_webhooks' ) );
    }

    /**
     * Display CDL Checkout payment icon.
     */
    public function get_icon() {

        $icon = '<img src="' . WC_HTTPS::force_https_url( plugins_url( 'assets/images/cdl-checkout-wc.jpg', CDL_CHECKOUT_MAIN_FILE ) ) . '" alt="Direct Checkout Payment Options" />';


        return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );

    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => esc_html__('Enable/Disable', 'cdl-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'label' => esc_html__('Enable CDL Checkout', 'cdl-checkout-for-woocommerce'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => esc_html__('Title', 'cdl-checkout-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__('This controls the payment method title which the user sees during checkout.', 'cdl-checkout-for-woocommerce'),
                'default' => esc_html__('CDL Checkout', 'cdl-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => esc_html__('Description', 'cdl-checkout-for-woocommerce'),
                'type' => 'textarea',
                'description' => esc_html__('This controls the payment method description which the user sees during checkout.', 'cdl-checkout-for-woocommerce'),
                'default' => esc_html__('Pay via CDL Checkout', 'cdl-checkout-for-woocommerce'),
            ),
            'testmode' => array(
                'title' => esc_html__('Test mode', 'cdl-checkout-for-woocommerce'),
                'label' => esc_html__('Enable Test Mode', 'cdl-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'description' => esc_html__('Test mode enables you to test payments before going live. <br />Once the LIVE MODE is enabled on your CDL Checkout account uncheck this.', 'cdl-checkout-for-woocommerce'),
                'default' => 'yes',
                'desc_tip' => true,
            ),
            'public_key' => array(
                'title' => esc_html__('Public Key', 'cdl-checkout-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__('Enter your Public Key here.', 'cdl-checkout-for-woocommerce'),
                'default' => '',
            ),
            'secret_key' => array(
                'title' => esc_html__('Secret Key', 'cdl-checkout-for-woocommerce'),
                'type' => 'password',
                'description' => esc_html__('Enter your Secret Key here.', 'cdl-checkout-for-woocommerce'),
                'default' => '',
            ),
            'autocomplete_order' => array(
                'title' => esc_html__('Autocomplete Order After Payment', 'cdl-checkout-for-woocommerce'),
                'label' => esc_html__('Autocomplete Order', 'cdl-checkout-for-woocommerce'),
                'type' => 'checkbox',
                'description' => esc_html__('If enabled, the order will be marked as complete after successful payment', 'cdl-checkout-for-woocommerce'),
                'default' => 'no',
                'desc_tip' => true,
            ),
            'remove_cancel_order_button'       => array(
                'title'       => esc_html__( 'Remove Cancel Order & Restore Cart Button', 'cdl-checkout-for-woocommerce' ),
                'label'       => esc_html__( 'Remove the cancel order & restore cart button on the pay for order page', 'cdl-checkout-for-woocommerce' ),
                'type'        => 'checkbox',
                'description' => '',
                'default'     => 'no',
            ),
        );
    }

    /**
     * Check if  CDL Checkout Payment is enabled.
     *
     * @return bool
     */
    public function is_available() {
        if ('yes' === $this->enabled) {
            if (!($this->public_key && $this->secret_key)) {
                $this->msg = esc_html__('CDL Checkout Payment Gateway Disabled: Missing API keys.', 'cdl-checkout-for-woocommerce');
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Check if CDL Checkout merchant details is filled.
     */
    public function admin_notices() {

        if ( $this->enabled == 'no' ) {
            return;
        }

        // Check required fields.

        if ( ! ( $this->public_key && $this->secret_key ) ) {
           $message = sprintf(
           // Translators: %s is the placeholder for the link to CDL Checkout setting page
               esc_html__( 'Please enter your CDL Checkout merchant details <a href="%s">here</a> to be able to use the CDL Checkout WooCommerce plugin.', 'cdl-checkout-for-woocommerce' ),
               esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=cdl_checkout' ) )
            );

            echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
        }

    }

    /**
     * Admin Panel Options.
     */
    public function admin_options() {

        ?>
        <div>
            <strong>
                <?php
                $webhook_url = untrailingslashit( WC()->api_request_url( 'Cdl_Checkout_WC_Payment_Webhook' ) );


                $message = sprintf(
                // Translators: %1$s is the placeholder for the cdl checkout merchant URL
                // Translators: %2$s is the placeholder for the cdl checkout webhook URL
                    __( 'Optional: To avoid situations where bad network makes it impossible to verify transactions, set your webhook URL <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a> to the URL below:', 'cdl-checkout-for-woocommerce'),
                    esc_url( 'https://checkout.creditdirect.ng/bnpl/#/merchant/portal/auth/login' )
                );
                echo '<div class="error"><p>' . wp_kses_post( $message ) . '</p></div>';
                ?>
                <div style="display: inline-flex; align-items: start;  margin-top: 8px;">
                    <div style="margin: 0; padding: 0 8px; background: transparent; color: red; border: none;">
                        <code id="webhook-url">
                            <?php

                            printf(
                            // Translators: %1$s is the placeholder for the cdl checkout webhook URL
                            // Translators: %2$s is the placeholder for the cdl checkout webhook URL
                                esc_html__( 'Webhook URL: %s', 'cdl-checkout-for-woocommerce' ),
                                esc_url( $webhook_url )
                            ); ?>
                        </code>
                    </div>
                    <button role="button" id="copy-webhook-url" style="cursor: pointer; margin-left: 10px; padding: 0 8px; display: inline-flex; align-items: center;">
                        <span class="dashicons dashicons-admin-page"></span> Copy
                    </button>

                </div>
            </strong>
            <!-- Notification area -->
            <div id="copy-notice" class="notice notice-success is-dismissible" style="display: none;">
                <p>Copied to clipboard!</p>
            </div>
        </div>
        <h2><?php esc_html_e( 'CDL Checkout', 'cdl-checkout-for-woocommerce' ); ?>
            <?php
            if ( function_exists( 'wc_back_link' ) ) {
                wc_back_link( esc_html__( 'Return to payments', 'cdl-checkout-for-woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
            }
            ?>
        </h2>
        <?php

        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';

    }

    public function payment_fields() {
        if ( $this->description ) {
            echo esc_html( wptexturize( $this->description ) );
        }

        if ( ! is_ssl() ) {
            return;
        }
    }

    public function receipt_page($order_id) {

        static $displayed = false;

        // Check if the form has already been displayed
        if ($displayed) {
            return;
        }

        $order = wc_get_order($order_id);

        // Start output buffer
        ob_start();
        ?>
        <div id="cdl-checkout-form">
            <p>Thank you for your order. Please click the button below to pay with CDL Checkout.</p>

            <div id="cdl-checkout-payment-form">
                <button type="button" class="button pay" id="cdl-checkout-payment-button">Pay With CDL Checkout</button>

                <?php if (! $this->remove_cancel_order_button) : ?>
                    <a class="button cancel" id="cdl-checkout-cancel-payment-button" href="<?php echo esc_url($order->get_cancel_order_url()); ?>">
                        Cancel order &amp; restore cart
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
        // End output buffer and echo content
        $output = ob_get_clean();

        echo wp_kses_post($output);

        $displayed = true;
    }


    /**
     * Enqueue the payment scripts
     */
    public function payment_scripts() {
        $pay_for_order = isset($_GET['pay_for_order']) ? sanitize_text_field($_GET['pay_for_order']) : '';

        if ( $pay_for_order || ! is_checkout_pay_page() ) {
            return;
        }

        if ( $this->enabled === 'no' ) {
            return;
        }

        wp_enqueue_script( 'jquery' );

        wp_enqueue_script('cdl-checkout-js', 'https://checkout.creditdirect.ng/bnpl/checkout.min.js', [], CDL_CHECKOUT_VERSION, false);

        wp_enqueue_script( 'cdl-checkout-payment-js', plugins_url( 'assets/js/cdl-checkout.js', CDL_CHECKOUT_MAIN_FILE ), array( 'jquery', 'cdl-checkout-js' ), CDL_CHECKOUT_VERSION, true );



        $order_key = isset($_GET['key']) ? sanitize_text_field(urldecode($_GET['key'])) : '';

        $order_id  = absint( get_query_var( 'order-pay' ) );

        $order = wc_get_order($order_id);

        if ($order) {
            wp_localize_script('cdl-checkout-payment-js', 'cdlCheckoutData', [
                'orderId' => $order_id,
                'publicKey' => $this->public_key,
                'isLive' => !$this->testmode,
                'order_status' => $order->get_status(),
                'totalAmount' => $order->get_total(),
                'customerEmail' => $order->get_billing_email(),
                'customerPhone' => $order->get_billing_phone(),
                'products' => $this->get_cart_items(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'signTransactionNonce' => wp_create_nonce('sign_transaction'),
                'saveCheckoutTransactionIdNonce' => wp_create_nonce('save_transaction_id'),
                'returnUrl' => $this->get_return_url($order)
            ]);
        }
    }

    /**
     * Enqueue admin scripts.
     */
    public function admin_scripts($hook) {

        if ('woocommerce_page_wc-settings' !== $hook) {
            return;
        }

        wp_enqueue_script(
                'wp-cdl-checkout-admin-script',
                plugins_url( 'assets/js/cdl-checkout-admin.js', CDL_CHECKOUT_MAIN_FILE ),
                array('jquery'),
                CDL_CHECKOUT_VERSION,
                true
        );


    }

    /**
     * Process the payment
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        $order->add_order_note( esc_html__('Awaiting CDL Checkout payment', 'cdl-checkout-for-woocommerce'));


        // Redirect to the thank you page
        return array(
            'result'   => 'success',
            'redirect' => $order->get_checkout_payment_url( true ),
        );
    }


    public function process_webhooks() {

        $post_data = file_get_contents('php://input');
        $response = json_decode($post_data, true);

        $logger = wc_get_logger();
        $log_context = array('source' => 'cdl_checkout_webhook');
        $timestamp = gmdate("Y-m-d H:i:s"); // Current time

        $logger->info("Webhook received at: $timestamp", $log_context);

        $checkoutTransactionId = isset($response['checkoutTransactionId']) ? sanitize_text_field($response['checkoutTransactionId']) : '';
        $eventType = isset($response['eventType']) ? sanitize_text_field($response['eventType']) : '';


        // query order by checkout transaction Id
        $query = new WC_Order_Query(array(
            'meta_key'    => '_checkout_transaction_id',
            'meta_value'  => $checkoutTransactionId,
            'limit'       => 1,
            'return'      => 'ids',
        ));

        $orders = $query->get_orders();

        if (!empty($orders)) {
            $order_id = $orders[0];
            $order = wc_get_order($order_id);

            if ($eventType === 'Checkout_Customer_Payment_Completed') {
                if ($order) {
                    $order->add_order_note('Customer deposit received through CDL Checkout.');

                    $logger->info("Checkout_Customer_Payment_Completed webhook received at: $timestamp", $log_context);


                    // Respond back to acknowledge receipt of the webhook
                    header('HTTP/1.1 200 OK');
                    echo wp_json_encode(['status' => 'success', 'message' => 'Webhook processed successfully 1']);
                    exit;
                }

            }

            if ($eventType === 'Checkout_Merchant_Payment_Completed') {

                if ($order) {
                    $order->payment_complete();
                    $order->add_order_note('Payment received through CDL Checkout.');

                    // Reduce stock levels
                    wc_reduce_stock_levels($order_id);

                    // Remove cart
                    WC()->cart->empty_cart($order_id);

                    if ( $this->is_autocomplete_order_enabled( $order ) ) {
                        $order->update_status( 'completed' );
                    }

                    $logger->info("Checkout_Merchant_Payment_Completed webhook received at: $timestamp", $log_context);

                    // Respond back to acknowledge receipt of the webhook
                    header('HTTP/1.1 200 OK');
                    echo wp_json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
                    exit;
                }

            }


        }

        $logger->error("Invalid data received at: $timestamp", $log_context);

        header('HTTP/1.1 400 Bad Request');
        echo wp_json_encode(['status' => 'error', 'message' => 'Invalid data received']);
        exit;
    }

    protected function is_autocomplete_order_enabled( $order ) {
        $autocomplete_order = false;

        $payment_method = $order->get_payment_method();

        $cdl_checkout_settings = get_option('woocommerce_' . $payment_method . '_settings');

        if ( isset( $cdl_checkout_settings['autocomplete_order'] ) && 'yes' === $cdl_checkout_settings['autocomplete_order'] ) {
            $autocomplete_order = true;
        }

        return $autocomplete_order;
    }

    private function get_cart_items() {
        $items = array();
        foreach (WC()->cart->get_cart() as $cart_item) {
            $product = $cart_item['data'];
            $items[] = array(
                'productName' => $product->get_name(),
                'productAmount' => $cart_item['line_total'],
                'productId' => $product->get_id()
            );
        }
        return $items;
    }

    // logo url
    public function cdl_checkout_get_logo_url()
    {

        $url = WC_HTTPS::force_https_url(plugins_url('assets/images/cdl-checkout-wc.jpg', CDL_CHECKOUT_MAIN_FILE));

        return apply_filters('wc_cdl_checkout_gateway_icon_url', $url, $this->id);
    }
  
}
