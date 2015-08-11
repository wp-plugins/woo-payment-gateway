<?php
/**
 * Process the customer payment using the braintree PHP SDK. The $order_id is used to fetch the order amount to be charged.
 * @param int $order_id
 */
function process_customer_payment($order_id){
	global $woocommerce;
	
	$order = new WC_Order($order_id);
	if(!$order) {
		wc_add_notice(__('There has been an issue processing your order. Please try again', 'woocommerce'), 'error');
		return;
	}
	$result = apply_filters('process_braintree_payment', $order, $order_id);
		if($result->success){
			$order->update_status('Payment Processed', __('Credit card payment processed', 'woocommerce'));
			$woocommerce->cart->empty_cart();
			$order->payment_complete($result->transaction->id);
			return array(
					'result'=>'success',
					'redirect'=>WC_Payment_Gateway::get_return_url($order));
		}
		elseif($result->transaction){
			wc_add_notice($result->message, 'error');
			return array(
					'result'=>'failure', 
					'redirect'=>''
			);
		}
		else {
			$message = $result->_attributes->message;
			foreach($result->errors->deepAll() as $error){
				wc_add_notice($error->message, 'error');
			}
			return array(
					'result'=>'fail', 
					'redirect'=>''
			);
		}
	
}

add_filter('process_customer_payment', 'process_customer_payment', 10, 1);
		




/**
 * Trigger the Braintree payment processing using the payment nonce. 
 * @param WC_Order $order_array
 */
function process_braintree_payment(WC_Order $order){
	if(!isset($_REQUEST['payment_method_nonce'])){
		wc_add_notice(__('There has been an error verifying your payment', 'woocommerce'), 'error');
		return;
	}
	$amount = $order->get_total();
	$nonce = $_REQUEST['payment_method_nonce'];
	$save_payment = 'save' == $_POST['save_cc'] ? true : false;
	$paypal = 'PayPalAccount' == $_POST['payment_type'] ? true : false;
	$creditCard = 'CreditCard' == $_POST['payment_type'] ? true : false;
	
	$user_id = wp_get_current_user()->ID;
	
	$user_id = apply_filters('get_user_for_payment', $user_id);
	
	$args = array(
			'amount'=>$amount,
			'orderId'=>$order->id,
			'options'=>array(
					'submitForSettlement'=>true
			),
			'customer'=>array(
					'firstName'=>$_POST['billing_first_name'],
					'lastName'=>$_POST['billing_last_name']
			)
		
	);
	
	if(!empty($_POST['existing_card'])){
		$token = $_POST['existing_card'];
	}
	
	if($paypal || $creditCard && !$token){
		$args['paymentMethodNonce'] = $nonce;
	}
	
	if($token){
		$args['paymentMethodToken'] = $token;
		$save_payment = false;
		//$args['customerId'] = $user_id;
	}
	
	$args = apply_filters('braintree_payment_arguments', $args);
	
	if($save_payment){
		$args['options']['storeInVaultOnSuccess'] = true;
		
		if($user_id){
			if(has_braintree_id($user_id)){
				
				$result = Braintree_PaymentMethod::create(array(
						'customerId'=>$user_id,
						'paymentMethodNonce'=>$nonce
				));
				if($result->success){
				$args['paymentMethodToken'] = $result->paymentMethod->token;
				unset($args['paymentMethodNonce']);
				}
			}
			else {
				$args['customer']['id'] = $user_id;
				$args['paymentMethodNonce'] = $nonce;
			}
		}
		
		
	}
	$result = Braintree_Transaction::sale($args);
	
	do_action('process_braintree_transaction_result',$result, $order);
	
	return $result;
}
add_filter('process_braintree_payment', 'process_braintree_payment', 10, 2);


function get_credit_cardTypes(){
	$card_types = array('American Express', 'MasterCard', 'Discover', 'Visa', 'Diners Club', 'Carte', 'China UnionPay',  
			'JCB', 'Laser', 'Maestro');
	return $card_types;
}

function card_type_html($card_types){
	foreach($card_types as $card){
		$html .= '<option id="'.$card.'" class="cc_option">'.$card.'</option>';
	}
	return $html;
}

function get_expiration_months_html(){
	/* $months = array(
			'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
			'October', 'November', 'December'); */
	$months = array('01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12');
	
	//$count = 1;
	foreach($months as $month){
		$html .= '<option id="'.$month.'">'.$month.'</option>';
	}
	return $html;
}

function get_expiration_years(){
	$current_year = (int) date('Y');
	$count = 10;
	for($i = 0; $i < $count; $i++){
		$html .= '<option class="expire_year">'.$current_year.'</option>';
		$current_year++;
	}
	return $html;
}

function send_braintree_client_token($user){
	if($user){
	$user_id = $user->id;
	$html = '<input type="hidden" id="client_token" value="'.Braintree_ClientToken::generate(array('customerId'=>$user_id)).'"/>';
	return $html;
	 }
	$html = '<input type="hidden" id="client_token" value="'.Braintree_ClientToken::generate().'"/>';
	return $html;
}

function get_braintree_checkout_ui($form_type, $gateway){
	if($form_type == 'custom'){
		get_custom_checkout_ui($gateway);
	}
	else {
		get_dropin_ui($gateway);
	}
}
add_filter('get_braintree_checkout_ui', 'get_braintree_checkout_ui', 10, 2);

function get_custom_checkout_ui($gateway){
	$wp_user = wp_get_current_user();
	$user = get_current_braintree_user($wp_user->ID);
	
	$user = apply_filters('get_braintree_user', $user, $gateway);
	?>
	
				
				<div class="cc-container">
				<input type="hidden" name="payment_type"/>
				<input type="hidden" id="payment_form" value="custom"/>
			
				<div class="cc-header"><h2><?php echo __('Payment Information', 'woocommerce')?></h2></div>
					<div class="cc-info">
					<?php if($user->paymentMethods){?>
						<table class="saved-cards">
							<tbody>			
								<tr>
								<td class="left-td"><label><?php echo __('Saved Cards', 'woocommerce')?></label></td>
							
								<td class="right-td">
									<div class="saved-cards">
										<select name="existing_card" class="existing-card">
											<option value="" ></option>
						<?php 			
					foreach($user->creditCards as $index => $braintree_card){ ?>
						<option class="saved-card" name="saved_card" <?php if($braintree_card->isDefault()){?>selected <?php }?> value="<?php echo $braintree_card->token ?>"><?php echo $braintree_card->cardType.' '.$braintree_card->maskedNumber?></option>
								<?php }
					foreach($user->paypalAccounts as $index => $paypal_account){?>
						<option class="saved-card" name="saved_card" <?php if($paypal_account->isDefault()){?>selected <?php }?> value="<?php echo $paypal_account->token ?>"><?php echo 'Paypal Account - '.$paypal_account->email ?></option>
					<?php }}?>
								</select>
									</div>
								</td>
							</tr>
								
							</tbody>
						</table>
						<?php if( $user->paymentMethods ) {?>
						<div class="new-payment-method"><?php echo __('New Card', 'woocommerce')?></div>
						<?php }?>
						<table class="cc-table" <?php if( $user->paymentMethods ) {?>style="display:none"<?php }?>>
							<tbody>
										
								<tr>
									<td class="left-td"><label><?php echo __('Credit Card Type', 'woocommerce')?></label></td>
									<td class="right-td">
										<select><?php echo card_type_html(get_credit_cardTypes())?></select>
									<div class="credit-cards"><span class="cc-type"><img src="<?php echo WC_BRAINTREE_SCRIPT_PATH.'images/visa.png'?>"/></span>
										<span class="cc-type"><img src="<?php echo WC_BRAINTREE_SCRIPT_PATH.'images/discover.png'?>"/></span>
										<span class="cc-type"><img src="<?php echo WC_BRAINTREE_SCRIPT_PATH.'images/mastercard.png'?>"/></span>
										<span class="cc-type"><img src="<?php echo WC_BRAINTREE_SCRIPT_PATH.'images/american-express.png'?>"/></span>
									</div>
									</td>
								</tr>
								<tr>
									<td class="left-td"><label><?php echo __('Credit Card Number', 'woocommerce')?></label></td>
									<td class="right-td"><input data-braintree-name="number" id="CC_NUM" type="text"/>
									</td>
								</tr>
								<tr>
									<td class="left-td"><label><?php echo __('Expires On', 'woocommerce')?></label></td>
									<td class="right-td">
										<div class="expire-month">
											<select data-braintree-name="expiration_month"><?php echo get_expiration_months_html()?></select>
										</div>
										<div class="expire-year">
											<select data-braintree-name="expiration_year"><?php echo get_expiration_years()?></select>
										</div>
									
									</td>
								</tr>
								<tr>
									<td class="left-td"><label><?php echo __('CVV', 'woocommerce')?></label></td>
									<td class="right-td"><input type="password" class="cvv" data-braintree-name="cvv" /></td>
								</tr>
								<tr>
									<td class="left-td"><label><?php echo __('Save Credit Card', 'woocommerce')?></label></td>
									<td class="right-td"><input type="checkbox" name="save_cc" value="save"/></td>
								</tr>
							</tbody>
						</table>
				<!--  	</div> -->
					  <script>
    jQuery(document).ready(function(){

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
				braintree.setup(clientToken, 'custom', {id: 'checkout', 
					onPaymentMethodReceived: function (response){
						paymentReceived.execute(response);

						}});
			
			}

  })   
  </script>
					<?php 
					echo send_braintree_client_token();
					
					get_paypal_screen($gateway);?>
					
				</div>
				<?php 
}

function get_dropin_ui($gateway){
	$wp_user = wp_get_current_user();
	$user = get_current_braintree_user($wp_user->ID);
	
	$user = apply_filters('get_braintree_user', $user, $gateway);
	?>
	
	<div id="dropin-container"></div>
  <?php echo send_braintree_client_token($user)?>
	<input type="hidden" name="payment_type"/>
	<input type="hidden" id="payment_form" value="dropin"/>
	  <script>
    jQuery(document).ready(function(){

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
						paymentReceived.execute(response);

						}});
			
			}

  })   
  </script>
	<?php 
}

/**
 * This function replaces the standard woocommerce payment-method.php page with an exact replica. This replacement is needed
 * because there is a bug in either woocommerce or braintree that prevents the dropin ui from staying within the html container
 * on the checkout page. 
 * @param unknown $template_name
 * @param unknown $template_path
 * @param unknown $default_path
 */
/* function replace_woocommerce_payment_methods_page($located, $template_name, $args, $template_path, $default_path){
	if($template_name !== 'checkout/payment-method.php') {
		return $located;
	}
	$settings = get_option('woocommerce_braintree_payment_gateway_settings', true);
	if($settings['braintree_dropin_ui'] == 'yes' || $settings['braintree_custom_ui']){
	$located = WC_BRAINTREE_GATEWAY.'includes/templates/checkout/payment-method.php';
		return $located;
	}
	return $located;
}
add_filter('wc_get_template', replace_woocommerce_payment_methods_page, 10, 5); */

function get_hidden_cart_total(){
	$cart = WC()->cart;
	if($cart->prices_include_tax){
		return $cart->cart_contents_total;
	}
	else {
		return ($cart->cart_contents_total + $cart->tax_total);
	}
}
add_filter('get_hidden_cart_total', 'get_hidden_cart_total');

function get_braintree_template_scripts($located, $handle, $script){
	$template_path = get_template_directory();
	
	if(!$template_path){
		return $located;
	}
	if(file_exists($template_path.'/braintree-template/assets/'.$script)){
		return get_template_directory_uri().'/braintree-template/assets/';
	}
	return $located;
}
add_filter('get_braintree_template_scripts', 'get_braintree_template_scripts', 10, 3);

function has_braintree_id($user_id){
	try {
		if($customer = Braintree_Customer::find($user_id)){
			return true;
		}
	}
	catch(Exception $e){
		return false;
	}
}

function get_current_braintree_user($user_id){
	try{
	$user = Braintree_Customer::find($user_id);
		return $user;
	}
	catch(Exception $e){
		return false;
	}
}

function get_paypal_screen($gateway){
	$paypal = 'yes' == $gateway->get_option('braintree_paypal_enable') ? true : false;
	
	if($paypal){
		?>
		<div class="paypal-div"><h3><?php echo __('Paypal', 'woocommerce')?></h3></div>
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
		  					<?php }
	}

?>