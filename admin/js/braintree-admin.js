jQuery(document).ready(function(){
	
	var $ = jQuery;
	var prod = '#woocommerce_braintree_payment_gateway_braintree_production';
	
	var sand = '#woocommerce_braintree_payment_gateway_braintree_sandbox';
	
	$(document).on('click', sand, function(){
		
		read_only_prod();
		if($(this).prop('checked') == true) {
			alert('You have activated your Braintree sandbox environment.')
		}
		else {
		alert('You have deactivated your Braintree sandbox account.');
		}
	})
	
	$(document).on('click', prod, function(){
		
		read_only_sand();
		if($(this).prop('checked') == true){
		alert('You have activated your Braintree production account!');
		}
		else {
			alert('You have deactivated your Braintree production account!');
		}
	})
	
	function read_only_prod(){
		$(prod).attr('checked', false);
		$('#woocommerce_braintree_payment_gateway_production_merchant_id, #woocommerce_braintree_payment_gateway_production_public_key,'+
				'#woocommerce_braintree_payment_gateway_production_private_key, #woocommerce_braintree_payment_gateway_production_cse_key').prop('readonly', true);
		$('#woocommerce_braintree_payment_gateway_sandbox_merchant_id, #woocommerce_braintree_payment_gateway_sandbox_public_key,'+
				'#woocommerce_braintree_payment_gateway_sandbox_private_key, #woocommerce_braintree_payment_gateway_sandbox_cse_key').prop('readonly', false);
	}
	
	function read_only_sand(){
		$(sand).attr('checked', false);
		$('#woocommerce_braintree_payment_gateway_production_merchant_id, #woocommerce_braintree_payment_gateway_production_public_key,'+
		'#woocommerce_braintree_payment_gateway_production_private_key, #woocommerce_braintree_payment_gateway_production_cse_key').prop('readonly', false);
$('#woocommerce_braintree_payment_gateway_sandbox_merchant_id, #woocommerce_braintree_payment_gateway_sandbox_public_key,'+
		'#woocommerce_braintree_payment_gateway_sandbox_private_key, #woocommerce_braintree_payment_gateway_sandbox_cse_key').prop('readonly', true);
	

	}
	
	if('checked' == $(sand).attr('checked')){
		read_only_prod();
	}
	else {
		read_only_sand();
	}
	
	var dropin_ui = '#woocommerce_braintree_payment_gateway_braintree_dropin_ui';
	var custom_ui = '#woocommerce_braintree_payment_gateway_braintree_custom_ui';
	var paypal = '#woocommerce_braintree_payment_gateway_braintree_paypal_enable';
	$(dropin_ui).click(function(){
		$(custom_ui).prop('checked', false);

		if($(paypal).prop('checked', true)){
			$(paypal).prop('checked', false);
		}
		$(paypal).prop('readonly', true);
	})
	$(custom_ui).click(function(){
		$(dropin_ui).prop('checked', false);
		$(paypal).prop('readonly', false);
	})
	$(paypal).click(function(){
		if($(dropin_ui).prop('checked') == true){
			$(paypal).prop('checked', false);
			return;
		}
	})
	
})