=== Braintree For Woocommerce ===
Contributors: mr.clayton@bradstreet.co
Donate link: 
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 4.2.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Braintree For Woocommerce is a plugin that allows merchants to accept credit card and paypal payments through their eCommerce website by integrating their Braintree account. Customer’s can choose to save their payment information for easy checkout at a later date. All saved payment information is saved using the wordpress user’s ID and will be saved in the Braintree vault. Admin’s can configure the look and behavior of the payment form. By selecting “Braintree Dropin UI” merchants can use the SAQ A compliant hosted forms provided by Braintree. By selecting “Braintree Custom UI”, merchants can use a custom payment form, which falls within the SAQ A-EP compliance.  

1. Upload braintree-for-woocommerce to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to Woocommerce->Settings->Checkout and select Braintree Payment Gateway. From there, you can enter your Braintree sandbox and production keys.

== Frequently Asked Questions ==

Where can I access my public and private keys?

Login to your Braintree account and select Account->My User->View API Keys

Can I customize the look of the plugin?

Yes, create a folder “braintree-template” in your template folder and add the “assets” folder from the plugin. This will let you customize the Braintree-for-woocommerce.css style sheet that comes with the plugin. 

Are there any hooks I can use?

Yes, there are hooks called for processing payments, saving customer data etc. You can use the hooks to customize how customer information is saved and handled. 

Who can I contact for information on this plugin?

Please email mr.clayton@bradstreet.co for any questions related to this plugin.

== Screenshots ==
1. Config screen located within the Woocommerce checkout settings.
2. Config screen showing dropin ui and custom ui options. 

= Customizable =
This plugin has built in hooks that will allow you to control how custom data is stored in your Braintree vault. 
1. apply_filters('get_user_for_payment', $user_id) - This filter is called before the payment is processed. The $user_id that is passed into the filter is the wordpress user id of the logged in user. 
If you want to store your customer's payment information using an ID other than the wordpress ID, then use this filter. 

2. do_action('process_braintree_transaction_result',$result, $order) - This action is called after the payment is processed. The $result variable contains the result returned by Braintree. You can process the result however you want
using this filter. The $order is the WC_Order object. 

3. apply_filters('process_customer_payment', $order_id) - If you want full control over how the payment is processed, then implement this filter. It will override the existsing process payment functionality so that you can create your own payment
processing functionality. 


4. If you want to customize the look and feel of the custom ui, create a folder "braintree-template" in your theme directory. Copy the assets folder from the plugin, into your "braintree-template" folder. 


== Changelog ==

= 1.0 =
* A change since the previous version.
* Another change.

= 0.5 =
* List versions from most recent at top to oldest at bottom.


= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`

