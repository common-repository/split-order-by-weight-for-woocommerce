<?php
/**
 * Thankyou page
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="woocommerce-order">

    <?php if ( $order ) :

        do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

        <?php if ( $order->has_status( 'failed' ) ) : ?>

        <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'woocommerce' ); ?></p>

        <p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
            <a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'woocommerce' ); ?></a>
            <?php if ( is_user_logged_in() ) : ?>
                <a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'woocommerce' ); ?></a>
            <?php endif; ?>
        </p>

    <?php else : ?>

        <p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'woocommerce' ), $order ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
        <ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">
            <li class="woocommerce-order-overview__order order">
                <?php
                $orderData = wc_get_order($order->get_id());
                $childOrdersData = $orderData->get_meta('order_ids');
                $childOrders = implode(',', unserialize($childOrdersData));
                ?>
                <?php if(count(unserialize($childOrdersData)) > 0) {
                    esc_html_e( 'Order numbers', 'woocommerce' );
                } else {
                    esc_html_e( 'Order number', 'woocommerce' );
                }
                ?>
                <strong><?php echo $childOrders ?></strong>
            </li>

            <li class="woocommerce-order-overview__date date">
                <?php esc_html_e( 'Date:', 'woocommerce' ); ?>
                <strong><?php echo wc_format_datetime( $order->get_date_created() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
            </li>

            <?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
                <li class="woocommerce-order-overview__email email">
                    <?php esc_html_e( 'Email:', 'woocommerce' ); ?>
                    <strong><?php echo $order->get_billing_email(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
                </li>
            <?php endif; ?>

            <?php if ( $order->get_payment_method_title() ) : ?>
                <li class="woocommerce-order-overview__payment-method method">
                    <?php esc_html_e( 'Payment method:', 'woocommerce' ); ?>
                    <strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
        <?php
        $parentOrderId = get_post_meta($order->get_id(), '_order_splitted_from');
        if(isset($parentOrderId) && is_array($parentOrderId) && count($parentOrderId)) {
            $parentOrderId = current($parentOrderId);
            if($parentOrderId == $order->get_id()){
                wc_get_template( 'order/order-details-customer.php', array( 'order' => $order ) );
            } else {
                do_action( 'woocommerce_thankyou', $order->get_id() );
            }
        } else {
            do_action( 'woocommerce_thankyou', $order->get_id() );
        }
        ?>
    <?php else : ?>

        <p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your splitted orders have been received.', 'woocommerce' ), null ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>

    <?php endif; ?>

</div>

