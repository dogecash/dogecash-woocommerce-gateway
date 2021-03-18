<?php
/**
 * Pay for order form
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/checkout/form-pay.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates
 * @version 3.4.0
 */


defined( 'ABSPATH' ) || exit;

$wc_dogec = new WC_Dogecash;

$totals = $order->get_order_item_totals();
$cp_order = dogec_get_cp_order_info($order->get_id());

if($cp_order->order_status == "expired") {
		$redirect = $order->get_cancel_order_url();
		wp_safe_redirect($redirect);
		exit;
}


if($cp_order->order_status == "confirmed") {
		$redirect = $order->get_checkout_order_received_url();
		wp_safe_redirect($redirect);
		exit;
}


?>
<?php if ( $order->get_payment_method() ==  $wc_dogec->id) : ?>

		<input type="hidden" name="cp_order_remaining_time" value="<?php echo dogec_order_remaining_time($order->get_id()); ?>">
		<input type="hidden" name="cp_order_id" value="<?php echo $order->get_id(); ?>">

		<div class="cp-order-info">
				<ul class="cp-order-info-list">
						<li class="cp-order-info-list-item">
								<?php _e( 'Order number:', 'woocommerce' ); ?>
								<strong><?php echo $order->get_order_number(); ?></strong>
						</li>

						<li class="cp-order-info-list-item">
								<?php _e( 'Date:', 'woocommerce' ); ?>
								<strong><?php echo wc_format_datetime( $order->get_date_created() ); ?></strong>
						</li>

						<li class="cp-order-info-list-item">
								<?php _e( 'Total:', 'woocommerce' ); ?>
								<strong><?php echo $cp_order->order_in_crypto . " " . $wc_dogec->cryptocurrency_used . " (" . $cp_order->order_total . " " . $cp_order->order_default_currency . ")" ?></strong>
						</li>
				</ul>
		</div>

		<?php if ( $order->needs_payment() ) : ?>
		<div class="cp-box-wrapper">
				<div class="cp-box-col-1">
						<h2><?php echo $wc_dogec->method_title ?></h2>
						<p class="cp-payment-msg"><?php echo (dogec_order_remaining_time($order->get_id()) < 0) ? "The payment time for order has expired! Do not make any payments as they will be invalid! If you have already made a payment within the allowed time, please wait." : $wc_dogec->description ?></p>

						<div>Amount:</div>
						<div class="cp-input-box">
								<input type="text" class="cp-payment-input" value="<?php echo $cp_order->order_in_crypto ?>" readonly>
								<button type="button" class="cp-copy-btn"><img src="<?php echo plugins_url('/woocommerce-dogecash/img/cp-copy-icon.svg') ?>" /></button>
						</div>

						<br />

						<div>Payment Address:</div>
						<div class="cp-input-box">
								<input type="text" class="cp-payment-input" value="<?php echo $cp_order->payment_address ?>" readonly>
								<button type="button" class="cp-copy-btn"><img src="<?php echo plugins_url('/woocommerce-dogecash/img/cp-copy-icon.svg') ?>" /></button>
						</div>

						<br />

						<div class="cp-payment-info-holder">
								<div class="cp-counter">00:00</div>
								<div class="cp-payment-info">
										<div class="cp-payment-info-status">Waiting for payment...</div>
										<div class="cp-payment-info-text">Exchange rate locked 1 <?php echo $wc_dogec->cryptocurrency_used; ?> = <?php echo round($cp_order->order_crypto_exchange_rate, 5) . ' ' . $cp_order->order_default_currency; ?></div>
								</div>
						</div>
				</div>
				<div class="cp-box-col-2">
						<div class="cp-qr-code-holder">
								<img src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=<?php echo $cp_order->payment_address ?>&choe=UTF-8" />
						</div>
				</div>
		</div>
		<?php endif; ?>

<?php else: ?>

<form id="order_review" method="post">

	<table class="shop_table">
		<thead>
			<tr>
				<th class="product-name"><?php esc_html_e( 'Product', 'woocommerce' ); ?></th>
				<th class="product-quantity"><?php esc_html_e( 'Qty', 'woocommerce' ); ?></th>
				<th class="product-total"><?php esc_html_e( 'Totals', 'woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( count( $order->get_items() ) > 0 ) : ?>
				<?php foreach ( $order->get_items() as $item_id => $item ) : ?>
					<?php
					if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
						continue;
					}
					?>
					<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_order_item_class', 'order_item', $item, $order ) ); ?>">
						<td class="product-name">
							<?php
								echo apply_filters( 'woocommerce_order_item_name', esc_html( $item->get_name() ), $item, false ); // @codingStandardsIgnoreLine

								do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order, false );

								wc_display_item_meta( $item );

								do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order, false );
							?>
						</td>
						<td class="product-quantity"><?php echo apply_filters( 'woocommerce_order_item_quantity_html', ' <strong class="product-quantity">' . sprintf( '&times; %s', esc_html( $item->get_quantity() ) ) . '</strong>', $item ); ?></td><?php // @codingStandardsIgnoreLine ?>
						<td class="product-subtotal"><?php echo $order->get_formatted_line_subtotal( $item ); ?></td><?php // @codingStandardsIgnoreLine ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<?php if ( $totals ) : ?>
				<?php foreach ( $totals as $total ) : ?>
					<tr>
						<th scope="row" colspan="2"><?php echo $total['label']; ?></th><?php // @codingStandardsIgnoreLine ?>
						<td class="product-total"><?php echo $total['value']; ?></td><?php // @codingStandardsIgnoreLine ?>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</tfoot>
	</table>

	<div id="payment">
		<?php if ( $order->needs_payment() ) : ?>
			<ul class="wc_payment_methods payment_methods methods">
				<?php
				if ( ! empty( $available_gateways ) ) {
					foreach ( $available_gateways as $gateway ) {
						wc_get_template( 'checkout/payment-method.php', array( 'gateway' => $gateway ) );
					}
				} else {
					echo '<li class="woocommerce-notice woocommerce-notice--info woocommerce-info">' . apply_filters( 'woocommerce_no_available_payment_methods_message', __( 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.', 'woocommerce' ) ) . '</li>'; // @codingStandardsIgnoreLine
				}
				?>
			</ul>
		<?php endif; ?>
		<div class="form-row">
			<input type="hidden" name="woocommerce_pay" value="1" />

			<?php wc_get_template( 'checkout/terms.php' ); ?>

			<?php do_action( 'woocommerce_pay_order_before_submit' ); ?>

			<?php echo apply_filters( 'woocommerce_pay_order_button_html', '<button type="submit" class="button alt" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // @codingStandardsIgnoreLine ?>

			<?php do_action( 'woocommerce_pay_order_after_submit' ); ?>

			<?php wp_nonce_field( 'woocommerce-pay', 'woocommerce-pay-nonce' ); ?>
		</div>
	</div>
</form>
<?php endif; ?>
