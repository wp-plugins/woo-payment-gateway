<?php
/*Plugin Name: Woo Payment Gateway
 Plugin URI: www.bradstreet.co
 Description: Braintree For Woocommerce allows merchants to securely process customer payments through their eCommerce sites. This plugin supports Braintree's new v.zero solution. Site admins have the ability to use the UI drop in feature which contains hosted fields or they can opt to use a custom ui for payment fields. The level of PCI compliance for this plugin ranges from SAQ A for the hosted form, to SAQ A-EP for the customer form. Please disable any other Braintree payment plugins before activating this plugin.  
 Version: 1.1.0
 Author: Clayton Rogers, mr.clayton@bradstreet.co
 Author URI: 
 Tested up to: 4.2
 */

define('WC_BRAINTREE_GATEWAY', plugin_dir_path(__FILE__));
define('WC_BRAINTREE_SCRIPT_PATH', plugin_dir_url(__FILE__).'assets/');

function init_wc_braintree_payment_gateway(){
	class Braintree_For_Woocommerce extends WC_Payment_Gateway {
		
		private $environment;
		
		private $merchant_id;
		
		private $private_key;
		
		private $public_key;
		
		private $form_type;
		
		public $paypal_enabled;
		
		public $method_title;
		
		public function __construct(){
			$this->id = 'braintree_payment_gateway';
			
			$this->icon = '';
			$this->title = __('Credit Card', 'woocommerce');
			
			$this->has_fields = true;
			
			$this->method_title = 'Braintree Payment Gateway';
			
			$this->method_description = 'Braintreet Payment Gateway uses the Braintreet PHP SDK to securely process online payments
					using your Braintree merchant account information';
			
			$this->init_form_fields();
		
			$this->init_settings();
			
			$this->load_options();
			
			$this->run_configuration();
			
			$this->load_braintree_script();
			
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
			wp_enqueue_script('wc-checkout', WC_BRAINTREE_SCRIPT_PATH.'js/checkout.js', array('jquery'), '', true);

			
		}
		
		private function load_options(){
			
			$sandbox = $this->get_option('braintree_sandbox') == 'yes' ? true : false;
			
			/*If production is enabled, check to ensure ssl is enabled. If not, then set sandbox mode to true
			so that the sandbox public and private keys are loaded instead of production.*/
			  if(!$sandbox){
				$sandbox = false == $this->is_ssl_enabled() ? true : false;
			}  
			//$sandbox = false;
			$form_type = $this->get_option('braintree_dropin_ui') == 'yes' ? true : false;
			
			$this->environment = ($sandbox) ? 'sandbox' : 'production';
			$this->merchant_id = ($sandbox)  ? $this->get_option('sandbox_merchant_id') : $this->get_option('production_merchant_id');
			$this->public_key = ($sandbox)  ? $this->get_option('sandbox_public_key') : $this->get_option('production_public_key');
			$this->private_key = ($sandbox)  ? $this->get_option('sandbox_private_key') : $this->get_option('production_private_key');
			$this->form_type = ($form_type) ? 'dropin' : 'custom';
			$this->paypal_enabled = 'yes' == $this->get_option('braintree_paypal_enable') ? true : false;
		}
		
		private function run_configuration(){
			if(class_exists('Braintree_Configuration')){
				Braintree_Configuration::environment($this->environment);
				Braintree_Configuration::merchantId($this->merchant_id);
				Braintree_Configuration::publicKey($this->public_key);
				Braintree_Configuration::privateKey($this->private_key);
			}
		}
		
		public function init_form_fields(){
			$this->form_fields = array('enabled'=>array(
					'title'=>__('Enable/Disable Braintree Gateway', 'woocommerce'), 
					'type'=>'checkbox'
			),
				'braintree_sandbox'=>array(
					'title'=>__('Enable/Disable Sandbox', 'woocommerce'),
					'type'=>'checkbox', 
					'description'=>'Enable or disable sandbox mode for testing',
					'id'=>'braintree_sandbox'
			), 'sandbox_merchant_id' => array(
					'title'       => __( 'Sandbox Merchant ID', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Get your API keys from your Braintree account by logging into the Braintree sandbox environment. Click on Settings->My Account to locate your merchant Id.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true
				),
				'sandbox_public_key' => array(
					'title'       => __( 'Sandbox Public Key', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Get your Sandbox keys by logging into your Braintree Account->My User->View API KEYS.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true
				),
				'sandbox_private_key' => array(
					'title'       => __( 'Sandbox Private Key', 'woocommerce' ),
					'type'        => 'text',
					'description' => __( 'Get your Sandbox keys by logging into your Braintree Account->My User->View API KEYS.', 'woocommerce'),
					'default'     => '',
					'desc_tip'    => true
				),
				'sandbox_cse_key' => array(
					'title'       => __( 'Sandbox CSE Key', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Get your Sandbox CSE key by logging into your Braintree Account->My User->View API KEYS.', 'woocommerce'),
					'default'     => '',
					'desc_tip'    => true), 
				'braintree_production'=>array(
						'title'=>__('Production', 'woocommerce'),
						'type'=>'checkbox',
						'label'=>__('Enable Production', 'woocommerce')
				),
				'production_merchant_id'=>array	(
						'title'=>__('Production Merchant ID', 'woocommerce'),
						'type'=>'text',
						'decsription'=>__('The production merchant account ID which can be found by loggingin into your Braintree account. When logging in, select Production. Under "Account->My User, you
								can locate your Merchant ID.', 'woocommerce')
				),
				'production_public_key'=>array(
						'title'=>__('Production public key'),
						'type'=>'text',
						'description'=>__('The public key of your production environment. This key can be located within your Braintree account.', 'woocommerce')
				),
				'production_private_key'=>array(
							'title'=>__('Production private key'),
							'type'=>'text',
						'description'=>__('The private key of your production environment. This key can be located within your Braintree account.', 'woocommerce')
			),  'production_cse_key' => array(
					'title'       => __( 'Production CSE Key', 'woocommerce' ),
					'type'        => 'textarea',
					'description' => __( 'Get your Production CSE key by logging into your Braintree Account->My User->View API KEYS.', 'woocommerce' ),
					'default'     => '',
					'desc_tip'    => true), 
				'braintree_dropin_ui'=>array(
						'title'=>'Braintree Drop In UI', 
						'description'=>__('This indicated if you would like to use the Braintree Drop in UI, 
								or use the custom forms approach. The drop in ui, is an iFrame that Braintree inserts into your checkout page. The iFrame ensures that the customer\'s credit card data
								never touches your client or server and is a very secure method. The custom form however, gives you more control over the look of your forms as you can directly access the CSS files
								that control the look and feel of the credit card form. Using the drop in UI, merchants will be SAQ A compliant. SAQ A is the mosts lacks level of compliance and only requires that a self
								assessed survery is filled out.'),
						'type'=>'checkbox',
						'desc_tip'=> true,
						'label'=>'Braintree Dropin UI'
				),
				'braintree_custom_ui'=>array(
							'title'=>'Braintree Custom UI',
							'description'=>__('This indicated if you would like to use the Braintree custom UI. The custom UI uses the credit card
									forms that are generated by the Briantree Gateway plugin. Using custom forms allow you to style the forms using CSS so they
									match the design of your website. There are more PCI considerations to be made when using custom UI vs drop in. Using this custom solution, 
									merchants will fall under SAQ A-EP compliance. This level of compliance requires that you answer roughly 120 questions of a self administered survey.'),
							'type'=>'checkbox',
							'desc_tip'=> true,
							'label'=>'Braintree Custom UI'
					),
					'braintree_paypal_enable'=>array(
							'title'=>__('Enable Paypal Payments With Custom UI', 'wwocommerce'),
							'type'=>'checkbox',
							'description'=>__('If this options is enabled, the paypal button will be available in the custom ui payment form. You must login to your Braintree account and
									configure your Braintree paypal settings in order for the payments to process', 'woocommerce'),
							'desc_tip'=>true
							
					)
			);
		}
		
		public function process_payment($order_id){
			return apply_filters('process_customer_payment', $order_id);
		}
		public function admin_options(){
			?>
			 <h2><?php _e('Braintree Payment Gateway','woocommerce'); ?></h2>
			 <div><p>The Braintree Payment Gateway has several configurable options. Before starting, ensure you have deactivated any other Braintree payment plugins as conflicts can occur. You can test the plugin using your
			 sandbox API keys to ensure everything is configured correctly before going live. The producion environment is activated by clicking the checkbox "Enable Production." Your Merchant Id, 
			 Public Key, and Private Key can all be located by logging into Braintree's sandbox and production environments. By selecting
			 "Braintree Dropin UI" the customer will see a Braintree hosted form for credit card entry. Customers have the option to save the payment
			 methods for later use. If you have configured Braintree to connect to Paypal, then a Paypal button will also appear with the drop. The Braintree Custom UI
			 is for those that want more flexibility in how the payment form looks. This form is not hosted and so subject to SAQ A-EP, which is a slightly higher level
			 than SAQ A. If the checkout page is loaded with HTTPS, then the sandbox environment will automaticlally be loaded. Ensure you have a valid SSL certificate. Along with the 
			 "Braintree Custom UI", admin's can select to enable Paypal. If you wish you change the look of the custom ui, you can create a folder in your template directory
			 and name it "braintree-template." Copy the assets folder into the "braintree-template" folder and then edit the braintree-for-woocommerce.css file.</p></div>
			 <table class="form-table">
			 <?php $this->generate_settings_html(); ?>
			 </table> <?php
			
		}
		
		/**
		 * Verify that force SSL option is set to true. If it is not, then do not allow the 
		 * customer to use the payment gateway using production keys. First, the code checks to see if woocommerce
		 * force SSL in checkout is set. If not, the header for 'HTTPS' is checked. If that is also false, then the wordpress
		 * function is_ssl() is called. If the function returns false, then Braintree is loaded using the sandbox public and private
		 * keys
		 */
		private function is_ssl_enabled(){
			$ssl_option = 'yes' == get_option('woocommerce_force_ssl_checkout', true) ? true : false;
			if(!$ssl_option){
					if(!is_ssl()){
					return false;
				}
			}
			return true;
		}
		public function payment_fields(){
			apply_filters('get_braintree_checkout_ui', $this->form_type, $this);
       }
       public function load_braintree_script(){
       	wp_enqueue_script('braintree-custom-ui-script', WC_BRAINTREE_SCRIPT_PATH.'js/braintree.js', array('jquery'), '', true );
  }
}
}
add_action('plugins_loaded', 'init_wc_braintree_payment_gateway');


function add_braintree_payment_gateway($methods){
	$methods[] = 'Braintree_For_Woocommerce';
	return $methods;
	
}
add_filter('woocommerce_payment_gateways', 'add_braintree_payment_gateway');

function load_dependencies(){
	include_once(WC_BRAINTREE_GATEWAY.'includes/payment-functions.php');
	include_once(WC_BRAINTREE_GATEWAY.'Braintree.php');
}

load_dependencies();

/**
 * Function that loads the braintree.js script
 */
function remove_wc_checkout(){
	/* $settings = get_option('woocommerce_braintree_payment_gateway_settings', true);
	$form_type = $settings['braintree_dropin_ui'];
	if($form_type == 'yes'){
		$located = apply_filters('get_braintree_template_scripts', WC_BRAINTREE_SCRIPT_PATH, 'checkout-script-dropin', 'js/checkout-dropin.js');
		wp_enqueue_script('checkout-script-dropin', $located.'js/checkout-dropin.js', array('braintree-custom-ui-script'), '', true);
	}
	
	else {
		$located = apply_filters('get_braintree_template_scripts', WC_BRAINTREE_SCRIPT_PATH, 'checkout-script-custom', 'js/checkout-custom.js');
		wp_enqueue_script('checkout-script-custom', $located.'js/checkout-custom.js', array('braintree-custom-ui-script'), '', true);
	} */
	wp_dequeue_script('wc-checkout');
		
}
add_action('after_setup_theme', 'remove_wc_checkout');

function load_scripts(){
	$settings = get_option('woocommerce_braintree_payment_gateway_settings', true);
	$form_type = $settings['braintree_dropin_ui'];
	if($form_type == 'yes'){
		$located = apply_filters('get_braintree_template_scripts', WC_BRAINTREE_SCRIPT_PATH, 'checkout-script-dropin', 'js/checkout-dropin.js');
		wp_enqueue_script('wc-checkout', $located.'js/checkout-dropin.js', array('braintree-custom-ui-script'), '', true);
	}
	
	else {
		$located = apply_filters('get_braintree_template_scripts', WC_BRAINTREE_SCRIPT_PATH, 'checkout-script-custom', 'js/checkout-custom.js');
		wp_enqueue_script('wc-checkout', $located.'js/checkout-custom.js', array('braintree-custom-ui-script'), '', true);
	}
	//wp_enqueue_script('wc-checkout', WC_BRAINTREE_SCRIPT_PATH.'js/checkout-custom.js', array('jquery'), '', true);
}
//add_action('woocommerce_before_checkout_form', 'load_scripts');

function load_braintree_style_files(){
	$located = apply_filters('get_braintree_template_scripts', WC_BRAINTREE_SCRIPT_PATH, 'checkout-styles', 'css/braintree-for-woocommerce.css');
	wp_enqueue_style('checkout-script-dropin', $located.'css/braintree-for-woocommerce.css');
}
add_action('woocommerce_before_checkout_form', 'load_braintree_style_files');

function load_admin_scripts(){
	wp_enqueue_script('braintree-admin-script', plugin_dir_url(__FILE__).'admin/js/braintree-admin.js');
}
add_action('admin_init', 'load_admin_scripts');

add_action('');
?>