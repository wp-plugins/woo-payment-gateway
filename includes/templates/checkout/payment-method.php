<?php
/**
 * Output a single payment method
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$custom_ui = 'yes' == $gateway->get_option('braintree_custom_ui') ? true : false;
$paypal = 'yes' == $gateway->get_option('braintree_paypal_enable') ? true : false;
$dropin = 'yes' == $gateway->get_option('braintree_dropin_ui') ? true : false;
?>

<li class="payment_method_<?php echo $gateway->id; ?>">
	<input id="payment_method_<?php echo $gateway->id; ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

	<label for="payment_method_<?php echo $gateway->id; ?>">
		<?php echo $gateway->get_title(); ?> <?php echo $gateway->get_icon(); ?>
	</label>
	<?php 
	if($gateway->id !== 'braintree_payment_gateway' || ($gateway->id == 'braintree_payment_gateway' && $custom_ui )) {
		if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
		<div class="payment_box payment_method_<?php echo $gateway->id; ?>" <?php if ( ! $gateway->chosen ) : ?><?php endif; ?>>
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; }?>
</li> 
<?php if($gateway->id == 'braintree_payment_gateway' && $dropin){
	?>
	
		<?php 	echo $gateway->payment_fields(); ?>
		<input type="hidden" id="order_amount" value="<?php echo apply_filters('get_hidden_cart_total')?>"/> 
  <script>
/*     jQuery(document).ready(function(){

		 var $ = jQuery;
			var clientToken = $('#client_token').val();
			var amount = $('#order_amount').val();
			var form = $('.checkout');
			if(form){
				form.attr('id', 'checkout');
			}
			else {
				form = $("form[name='checkout']").attr('id', 'checkout');
			}
			if(clientToken){
				braintree.setup(clientToken, 'dropin', {container: 'dropin-container', 
					onPaymentMethodReceived: function (response){
						paymentReceived.execute;

						}});
			
			}

  })   */
  </script>
  <?php } if($gateway->id == 'braintree_payment_gateway' && $custom_ui && $paypal){?>
  					<div><h3><?php echo __('Paypal', 'woocommerce')?></h3></div>
  					<div id="paypal-container"></div>
  					<input type="hidden" id="order_amount" value="<?php echo apply_filters('get_hidden_cart_total')?>"/>
  					<script>
  					jQuery(document).ready(function(){

  					var $ = jQuery;
  					var amount = $('#order_amount').val();
  					var clientToken = $('#client_token').val();			

  					braintree.setup(clientToken, 'paypal', {
  					  	container: "paypal-container",
  					  	singleUse: false,
  					    locale: '<?php echo get_locale()?>',
  					  	currency: '<?php echo get_woocommerce_currency()?>',
  					  	editable: false,
  					  	onPaymentMethodReceived: function (obj) {
  					  }
  					});

  					
  					})
  					</script>
  <?php }?>
  