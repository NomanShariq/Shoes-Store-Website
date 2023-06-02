=== Payment Plugins for Stripe WooCommerce ===
Contributors: mr.clayton
Tags: stripe, ach, klarna, credit card, apple pay, google pay, ideal, sepa, sofort
Requires at least: 3.0.1
Tested up to: 6.2
Requires PHP: 5.6
Stable tag: 3.3.39
Copyright: Payment Plugins
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==
Accept Credit Cards, Google Pay, ApplePay, Afterpay, Affirm, ACH, Klarna, iDEAL and more all in one plugin for free!

= Official Stripe Partner = 
Payment Plugins is an official partner of Stripe. 

= Boost conversion by offering product and cart page checkout =
Stripe for WooCommerce is made to supercharge your conversion rate by decreasing payment friction for your customer.
Offer Google Pay, Apple Pay, and Stripe's Browser payment methods on product pages, cart pages, and at the top of your checkout page.

= Visit our demo site to see all the payment methods in action = 
[Demo Site](https://demos.paymentplugins.com/wc-stripe/product/pullover/)

To see Apple Pay, visit the site using an iOS device. Google Pay will display for supported browsers like Chrome.

= Features =
- Credit Cards
- Google Pay
- Apple Pay
- Afterpay, Affirm, Klarna
- ACH Payments
- 3DS 2.0
- Local payment methods like Konbini, PayNow, BLIK, P24, IDEAL and many more
- WooCommerce Subscriptions
- WooCommerce Pre-Orders
- WooCommerce Blocks
- Installments for supported countries
- Integrates with [CheckoutWC](https://www.checkoutwc.com/payment-plugins-stripe-woocommerce/)

== Frequently Asked Questions ==
= How do I test this plugin? = 
 You can enable the plugin's test mode, which allows you to simulate transactions.
 
= Does your plugin support WooCommerce Subscriptions? = 
Yes, the plugin supports all functionality related to WooCommerce Subscriptions.

= Where is your documentation? = 
https://docs.paymentplugins.com/wc-stripe/config/#/

= Why isn't the Payment Request button showing on my local machine? = 
If your site is not loading over https, then Stripe won't render the Payment Request button. Make sure you are using https.

== Screenshots ==
1. Let customers pay directly from product pages
2. Apple Pay on the cart page
3. Custom credit card forms
4. Klarna on checkout page
5. Local payment methods like iDEAL and P24
6. Configuration pages
7. Payment options at top of checkout page for easy one click checkout
8. Edit payment gateways on the product page
9. Stripe Link for high conversion

== Changelog ==
= 3.3.39 =
* Fixed - Firefox event click issue with GPay. Event is now synchronous so that Firefox browser is fully supported
* Fixed - WooCommerce Blocks change to how billing and shipping address data populates
* Updated - Improved payment button performance on product pages
* Updated - Updated Alipay logic so it shows if CNY is store currency, regardless of billing country
* Updated - ACH payment icon for Blocks integration
* Updated - WC tested up to 7.5
* Added - New option where merchants can control when a Stripe customer ID is created. This is for merchants that only want to create a customer ID during payment
if they have GDPR concerns.
= 3.3.38 - 2/17/23 =
* Added - Support for BLIK payment method
* Added - Support for Konbini payment method
* Added - Support for PayNow payment method
* Fixed - Afterpay messaging not updating for variable products
* Fixed - ACH checkout page button not showing under certain conditions
= 3.3.37 - 2/8/23 =
* Added - Ability to configure the Affirm and Afterpay messaging location on product pages
* Added - Ability to configure the Affirm and Afterpay messaging location on the cart page
* Added - Ability to configure the Affirm and Afterpay messaging location on the shop/category page
* Added - For Payment Element, include list of payment_method_types to prevent Stripe from rendering multiple payment options
* Added - When displaying saved payment methods on checkout page, sort by the default payment method
* Added - Greece support for Klarna
* Fixed - If the charge ID is not in the review.opened webhook payload, use the payment intent to fetch the order ID
= 3.3.36 - 1/13/23 =
* Updated - WC tested up to 7.3
* Updated - Stripe PHP library to version 10.3.0
* Updated - Removed GPay color option since GPay now only supports the black button
* Added - Affirm and Afterpay messaging option for displaying on shop/category page
* Added - Webhook ID to payment intent metadata. This will reduce webhook log clutter for merchants that use multiple webhooks for one Stripe account.
* Added - Optimized classmap which improves plugin performance
* Added - Link support for accounts in Canada, Japan, Liechtenstein, Mexico
* Added - Link icon option for the billing email field. When enabled, a Link icon shows in the email field which indicates to customers that Link is supported.
= 3.3.36 =
* Fixed - ACH mandate text incorrectly displaying business name
= 3.3.35 - 12/17/22 =
* Fixed - Ensure Affirm and Afterpay messaging updates on product page when variation is selected
* Added - GPay option for rounded button corners. Google recently changed their API so the default button has rounded corners. We prefer
to give merchants the option on what button style they use.
* Added - Stripe fee for FunnelKit Upsell order
= 3.3.34 - 12/02/22 =
* Added - Support for Affirm payments. Affirm messaging can be added on product pages, cart page, and checkout page
* Added - Afterpay title to the checkout page. There was feedback that this title was needed in addition to the Afterpay messaging
* Updated - afterpay.php template file was modified to use the offsite-notice.php template
* Fixed - Installment plans not loading when Payment Form active
= 3.3.33 - 11/23/22 =
* Fixed - Afterpay payment method title not showing in order received emails and on order details page
* Updated - Removed "beta" from Payment form label in settings
* Added - Link support for FunnelKit/WooFunnels Upsells
= 3.3.32  - 11/11/22 =
* Updated - Improved Payment form (beta) performance
* Updated - Improved Link integration
* Updated - WC tested up to 7.1
= 3.3.31 - 10/19/22 =
* Fixed - Potential PHP error caused by PHP 8.1+ in Blocks class GooglePayPayment
* Fixed - Incorrect text domain for installment text
= 3.3.30 - 10/18/22 =
* Updated - WC tested up to 7.0
* Updated - Improved integration with FunnelKit (WooFunnels One Click Upsell)
* Added - Support for WooCommerce custom order tables (HPOS)
* Added - Filter "wc_stripe_show_admin_metaboxes" and "wc_stripe_show_pay_order_section" if custom logic is needed to show the charge view or Pay for Order view
* Fixed - Brazil installments minimum amount should be 1 BRL.
= 3.3.28 - 9/26/22 =
* Fixed - ACH javascript files not included in version 3.3.27 build
* Updated - Removed admin feedback code
= 3.3.27  - 9/25/22 =
* Fixed - Cast float to payment balance fee and net values
* Fixed - If refund is created in stripe.com dashboard, always take the latest refund from the list of refunds.
* Updated - WC tested up to 6.9
* Updated - WeChat Pay logo
* Updated - Alipay logo
* Updated - GPay WooCommerce Blocks integration performance improvements
* Added - Installments for Brazil Stripe accounts
* Added - Stripe Link support for EU countries
* Added - Stripe ACH Connections integration which replaces Plaid. Plaid can still be used but has been deprecated in favor of the new ACH integration.
= 3.3.26 - 8/24/22 =
* Updated - WC Tested up to 6.8
* Updated - Afterpay can now be used to purchase digital goods
* Added  - Afterpay support for France and Spain.
* Fixed - WC_Stripe_Utils::get_order_from_payment_intent()
* Fixed - Boleto WooCommerce Blocks checkout error
* Removed - Feedback modal on plugin deactivation
= 3.3.25 - 8/3/22 =
* Added - Advanced Settings option so merchants can control if the processing or completed status triggers the capture for an authorized order
* Fixed - Customer pay for order page error caused by Stripe payment form (beta)
* Added - Improved Subscription retry logic
= 3.3.24 - 7/5/22 =
* Fixed - Remove payment intent from session when intent's status is processing
* Updated - If payment intent's status transitions to requires_payment_method, update order status to pending
* Added - Filter wc_stripe_asynchronous_payment_method_order_status which can be used to change the order's status before redirect to payment page like Sofort, etc
* Added - Filter wc_stripe_get_api_error_messages which can be used to customize error messages
= 3.3.23 - 6/27/22 =
* Fixed - Conflict with the WooCommerce Pay Trace plugin
* Updated - Billing phone required if Stripe payment form card design used.
* Updated - Improved WooCommerce Blocks Link integration
* Updated - Check terms and conditions on when Plaid ACH button clicked on checkout page
= 3.3.22 - 6/16/22 =
* Updated - WC Tested up to 6.6
* Fixed - Error that could be triggered on plugins page if WooCommerce deactivated
* Fixed - WooCommerce Blocks Link integration autofill of shipping address
= 3.3.21 - 6/4/22 =
* Fixed - Error on checkout page when Payment Element is active and saved card used for payment
* Fixed - Don't hide "save card" checkbox when Link is active on checkout page
* Updated - Improved Payment Element integration with WooCommerce Blocks
= 3.3.20 - 6/3/22 =
* Added - PaymentElement for card payments
* Added - Link with Stripe which uses the PaymentElement to increase conversion rates
* Added - Additional messaging for the installments option located on the Advanced Settings page
* Added - Loader on Blocks ACH when initializing Plaid
* Added - Ability to delete connection to Stripe account on API Settings page
* Added - payment_intent.requires_action event for OXXO and Boleto
* Fixed - ACH fee option not being included in order on checkout page
* Fixed - Correctly display AfterPay messaging on product pages if prices are shown including tax
= 3.3.19 - 4/7/22 =
* Added - Support for installments for Mexico based accounts
* Added - Afterpay messaging in order summary of Checkout Block
* Fixed - GPay gateway 3DS error where authentication screen wasn't appearing.
= 3.3.18 =
* Fixed - Elementor template editor error when Woofunnels Checkout design is being edited in Admin.
* Updated - Mini cart code so it's more fault tolerant when using 3rd party mini-cart plugins.
* Updated - SEPA, GiroPay, Bancontact, EPS, Sofort, Alipay migrated to PaymentIntent API from Sources API
* Added - SEPA support for Pre Orders
* Added - Support for WooFunnels One Click Upsell Subscriptions
* Added - BECS support for WooCommerce Subscriptions and Pre-Orders
* Added - BECS company name option so merchant can customize the company name that appears in the mandate.
= 3.3.17 =
* Added - Stripe Customer ID field in the WooCommerce Subscription edit subscription page so it can be easily updated if necessary.
* Added - Plugin main page that contains link to support and documentation
* Added - WooFunnels AeroCheckout Express button compatibility.
* Updated - WC Tested up to 5.6
* Updated - Moved the Admin menu link for Stripe plugin to the WooCommerce menu section.
* Updated - Clearpay transaction limit set to 1000 GBP
* Updated - VISA and Maestro icons.
* Updated - Express checkout on checkout page now includes "OR" text separator. HTML was updated and new css class names used.
= 3.3.16 =
* Added - Option to specify where card form validation errors are shown. Previously they were shown at the top of the checkout page. The default is now above the credit card form.
* Added - Boleto voucher expiration days option
* Added - Option to include Boleto voucher link in order on-hold email sent to customer.
* Fixed - Don't remove coupon notice on checkout page if there is an error message that should be displayed.
* Fixed - Rare error that can occur when processing a subscription with a free trial on checkout page using Credit Card.
* Fixed - If local payment method is unsupported due to currency or billing country on checkout page, select next available payment method so place order button text updates.
= 3.3.15 =
* Added - France and Ireland to Klarna
* Added -Notice in capture modal if the capture amount is less than the order total. This notice is a reminder that merchants should update
the order line items if the capture amount is less than the order amount as it leads to better accounting.
* Added - OXXO voucher expiration days option
* Added - Option to include OXXO voucher link in order on-hold email sent to customer.
* Updated - WeChat redirect to thank you page after qrcode is scanned and user clicks "complete order".

= 3.3.14 =
* Added - Locale setting in Advanced Settings where you can use the site's locale or let Stripe determine it using the 'auto' feature.
* Added - Statement descriptor option in the Advanced Settings section
* Added - GPay button type options such as "Pay", "Order", "Checkout", "Subscribe"
* Added - GPay button locale filter. Default is to use locale setting in Wordpress if it's a supported GPay locale.
* Fixed - Afterpay error if 100% added to checkout page
* Fixed - JS error in IE11 when 3DS processing
= 3.3.13 =
* Fixed - Error message when using saved payment method for zero total subscription.
* Fixed - Undefined variable: gateways message on edit product page if no Stripe payment gateways are enabled.
* Added - Webhooks can be created from API Settings page.
* Added - Advanced Settings page where new options like Stripe fee, email receipt, etc can be managed.
* Added - Option to refund on order cancellation
* Added - Webhook charge.dispute.created, charge.dispute.closed, review.opened, review.closed functionality.
* Added - Klarna PaymentIntent integration. This new integration redirects to the Klarna payment page rather then the Klarna widget.
= 3.3.12 =
* Fixed - Klarna bug on checkout where sometimes the form wouldn't submit after confirming payment in the Klarna modal.
* Updated - Removed "browser icons" and replaced with the corresponding wallet icon. Example, if Browser Payments enabled and customer
is using chrome, GPay icon will show instead of chrome icon.
* Fixed - Only show the Stripe email option on the WooCommerce Email Settings page and not on individual email sections.
* Fixed - Error on Blocks checkout page if custom form has not been implemented.
* Added - Added new filter wc_stripe_should_save_payment_method
= 3.3.11 =
* Updated - Changed plugin name to "Payment Plugins for Stripe WooCommerce" at the request of Wordpress.org Plugin Review Team.
* Updated - If user is logged out and guest checkout is disabled, show save card checkbox if enabled.
* Added - Filter "wc_stripe_api_request_error_message" for more control over custom error messages.
* Added - Filter "wc_stripe_pay_order_statuses" so merchants can control when the Pay for Order button appears on the order details page.
= 3.3.10 =
* Added - Save card checkbox now shows (if enabled in Credit Card Setting) for guest users that select to create new account on checkout page.
* Added - Capability check to Admin profile
* Added - Option in WeChat settings to control QRCode size
* Added - Klarna test mode sms code "123456"
* Added - Filter "wc_stripe_get_element_options" which can be used to control the locale.
* Removed - Filter "wc_stripe_local_element_options" since "wc_stripe_get_element_options" can be used instead.
* Fixed - WooCommerce Blocks express checkout error when currency's number of decimals set to 0
* Updated - wpml-config.xml now includes additional admin-text entries
= 3.3.9 =
* Added - OXXO payment method support for standard checkout and [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/)
* Added - WeChat support for currencies CNY, DKK, NOK, SEC, CHF
* Added - Validate that Boleto CPF / CNPJ field has been populated before placing order
* Added - Filter "wc_stripe_local_element_options" which can be used to customize the locale of the local payment method
* Updated - Don't rely on WooCommerce Setting "Number of decimals" when converting amount to cents.
= 3.3.8 =
* Added - Boleto payment method support for standard checkout and [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/)
* Added - Better support for WooCommerce One Page Checkout plugin. 3DS is now requested for cards when required on product page.
* Added - Currencies with 3 decimal places (BHD, IQD, JOD, KWD, LYD, OMR, TND) to function wc_stripe_get_currencies
* Updated - WC Tested up to 5.6
* Fixed - Mini cart unblock if there is a payment processing error
= 3.3.7 =
* Added - If locale is "fi" change to "fi-FI"
* Added - Filter "wc_stripe_get_klarna_args"
* Updated - PHP8, replace round with wc_format_decimal for GPay
= 3.3.6 =
* Added - WooFunnels integration that supports credit cards, Apple Pay, GPay, and Payment Request gateway
* Added - Support for "WooCommerce All Products For Subscriptions" plugin
* Added - Round GPay totals to 2 decimals points since GPay will only accept a maximum of 2 decimals
* Fixed - Klarna ensure item totals always add up to order total to prevent "Bad Value" error.
= 3.3.5 =
* Fixed - BECS not always redirecting to the order received thank you page
* Fixed - Mini-cart issue where GPay button wasn't rendering due to styling change
* Fixed - Apple Pay all white button which was rendering as a black button
* Fixed - FPX "return_url" required when confirming payment intent error that could occasionally occur in test mode.
* Fixed - Invalid argument supplied for foreach() notice if no credit card icons selected for the Credit Card Gateway
* Fixed - If a saved SEPA payment method exists on checkout page, it was being selected instead of a new account.
* Fixed - Credit card icons not rendering in IE11 browser.
= 3.3.4 =
* Fixed - Plaid charge.succeeded webhook
* Fixed - Warning message when all credit card icons are disabled on checkout page
* Added - Afterpay messaging to mini-cart
* Added - Afterpay option to hide messaging if the items in the cart are not eligible for Afterpay
* Updated - Afterpay can only process payments in the currency that maps to the Stripe account's registered country. Examples: US > USD AU > AUD. This is a requirement of Afterpay.
* Updated - Made improvements to [Cartflows](https://wordpress.org/plugins/cartflows/) integration
= 3.3.3 =
* Added - [Cartflows](https://wordpress.org/plugins/cartflows/) support added for Credit Cards, Apple Pay, and Google Pay
* Fixed - Klarna order status remaining as pending in some scenarios and checkout page would not redirect to thank you page
* Fixed - Klarna [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) object comparison error
= 3.3.2 =
* Added - Render Clearpay logo when billing country is GB or currency is GBP
* Updated - GPay buttonSizeMode set to fill
* Fixed - Notice "handleCardAction: The PaymentIntent supplied is not in the requires_action state" if payment method uses 3DS and there are insufficient funds
= 3.3.1 =
* Added - Afterpay payment integration. Afterpay messaging can be added to the product pages, cart page, and checkout page
* Fixed - Compatibility between new WooCommerce Blocks integration, Elementor, & Divi.
* Updated - Converted P24 to use Payment Intents. Previously the integration used Sources. Stripe's API now requires that P24 use email address.
* Updated - Only load WooCommerce Blocks integration if WooCommerce Blocks is installed as a feature plugin
* Updated - Klarna disable place order button while payment options load. [Support request](https://wordpress.org/support/topic/klarna-problem-loading-frame/)
= 3.3.0 =
* Added - [WooCommerce Blocks](https://wordpress.org/plugins/woo-gutenberg-products-block/) plugin support. All Stripe plugin payment methods are supported.
= 3.2.15 =
* Fixed - Setup intent confirmation error if order contains subscription trail period and checkout fields fail validation.
* Fixed - CheckoutWC plugin compatibility on checkout page load.
* Added - Shortcode [wc_stripe_payment_buttons] for payment buttons so they can be rendered anywhere on product or cart pages.
= 3.2.14 =
* Added - Klarna pink background image
* Added - Klarna locale prevent unsupported formats. Example: convert de-DE-formal to de-DE
* Added - Klarna support for Spain, Belgium, and Italy
* Added - Filter wc_stripe_klarna_get_required_parameters so available country and currencies can be  customized
* Added - Filter wc_stripe_get_card_custom_field_options so attributes like placeholder can be added to custom forms
* Fixed - Payment Wallets: split full name by spaces and pass last value as last name. Prevents issue where some users have name entered as Mr. John Smith and order name comes out as Mr John.
= 3.2.13 =
* Added - Save new payment method to subscription when failed renewal order is paid for.
* Added - Permission check "manage_woocommerce" added to Edit Product page for rendering Stripe settings
* Added - If shipping phone field exists and is empty, populate using GPay/Apple Pay phone number returned
* Updated - WC Tested up to 5.0
* Updated - Stripe PHP SDK 7.72.0
* Updated - Fix change_payment_method request logic so it's processed in the process_payment function
* Updated - Adjust cart page buttons so last button doesn't have bottom margin
* Updated - Performance improvement on checkout page when saving a payment method and processing order
* Updated - Don't show payment method buttons on external products
= 3.2.12 =
* Added - billing_details property to local payment methods
* Added - Improved support for multisite. Replaced use of get_user_meta with get_user_option
* Added - Filter wc_stripe_get_customer_id
* Added - Filter wc_stripe_save_customer
* Added - Stripe token object added to filter wc_stripe_get_token_formats
* Added - Payment method formats (Credit Card, Apple Pay, Google Pay)
* Added - Added address property to customer create and update API call
* Added - New GPay rounded corners icon
* Added - Additional credit card form error messages for translation.
* Added - Guest checkout for Pre-Order products where payment method must be saved.
* Updated - Removed stripe customer Id from metadata since it's redundant. Customer ID is already associated with charge object
* Updated - Admin Pay for order - display message if order is not created or doesn't have pending payment status
= 3.2.11 =
* Added - WC tested up to: 4.9.0
* Added - Compatibility for Cartflows redirect to global checkout when local payment method used.
* Added - Refund webhook: if refunded total equals order total, re-stock product(s)
* Fixed - Apple Pay and Payment Request button disabled on product page when variable product has multiple variations
* Fixed - 3DS issue on order pay page if credit card gateway not pre-selected
* Fixed - Setup intent not created if 100% coupon entered on checkout page for subscription
* Updated - If any required fields missing on product or cart page checkout, redirect to checkout page where customer can enter required fields then click Place Order
= 3.2.10 =
* Added - Refund webhook support so refunds created in Stripe dashboard sync with WooCommerce store
* Added - Option to control when local payment methods are visible on checkout page based on billing country
* Added - wc_stripe_refund_args filter
* Added - Support for SEPA subscription amount changes
* Added - SEPA to subscription customer change payment method page
* Added - Filter so frontend error html can be customized
* Added - Authorize option for Klarna payments so they can be authorized or captured
* Added - Handle SCA for subscriptions with free trial
* Fixed - Sorting of Apple Pay payment methods in Apple Wallet
* Updated - GPay gateway converted to payment intents for SCA.
* Updated - Credit card form moved Saved Card label to right of checkbox
= 3.2.9 =
* Updated - WP tested to 5.6
* Updated - Tested with PHP 8
* Added - GrabPay gateway
* Added - Promise polyfill for older browsers
* Added - GPay added SCA required fields (https://developers.google.com/pay/api/web/guides/resources/sca)
* Added - Order button text option for local payment methods
* Added - new filter wc_stripe_force_save_payment_method
= 3.2.8 =
* Updated - Changed function wc_stripe to stripe_wc because WooCommerce Stripe Payment Gateway introduced a function with same name in version 4.5.4 which caused a fatal error.
* Updated - Apple Pay and GPay - if billing address already populated, don't request it in wallet.
* Updated - Convert long version of state/province for GPay to abbreviation. California = CA
* Updated - Updated Klarna checkout flow for improved user experience.
= 3.2.7 =
* Added - Better support for mixed cart/checkout page using Elementor
* Added - wc_stripe_save_order_meta action added so custom data can be added to order
* Added - Improved support for WooCommerce Multilingual
= 3.2.6 =
* Fixed - Apple Pay duplicate button in express checkout
* Added - Transaction url in order details
* Added - Check for existence of WC queue when loading
= 3.2.5 =
* Added - Support for right to left (RTL) languages
* Added - WPML gateway country availability compatibility
* Added - WPML product page currency change
* Fixed - Change event for ship_to_different_address
* Fixed - Update required fields on checkout page load
= 3.2.4 =
* Fixed - Payment request button disappearing on variable product page
* Updated - Only validate visible fields on checkout page
* Update - WC tested up to 4.6.0
* Added - SEPA WooCommerce Subscriptions support
* Added - Autofocus on custom credit card forms
= 3.2.3 =
* Fixed - 3DS pop up on order pay page
* Fixed - One time use coupon error when 3DS triggered on checkout page
* Fixed - Formatting in class-wc-stripe-admin-notices.php
* Added - Apple Pay, GPay, Payment Request, do not request shipping address or shipping options on checkout page if customer has already filled out shipping fields
= 3.2.2 =
* Fixed - 403 for logged out user when link-token fetched on checkout page
* Added - Payment method format for GPay. Example: Visa 1111 (Google Pay)
* Added - Filter for product and cart page checkout so 3rd party plugins can add custom fields to checkout process
* Updated - Stripe PHP lib version to 7.52.0
= 3.2.1 =
* Updated - Plaid Link integration to use new Link Token
* Updated - Convert state long name i.e. Texas = TX in case address is not abbreviated in wallet
* Updated - On checkout page, only request phone and email in Apple Pay and GPay if fields are empty
* Fixed - Issue where JS error triggered if cart/checkout page combined using Elementor
* Fixed - Apple Pay and Payment Request wallet requiring shipping address for variable virtual products
= 3.2.0 =
* Fixed - Conflict with Checkout Field Editor for WooCommerce and JS checkout field variable
* Fixed - Mini-cart html
* Fixed - SEPA JS error on checkout page
* Added - WC tested to 4.4.1
* Updated - removed selectWoo as form-handler.js dependency
= 3.1.9 =
* Fixed - WP 5.5 rest permission_callback notice
* Fixed - Conflict with SG Optimizer plugin
* Fixed - Conflict with https://wordpress.org/plugins/woocommerce-gateway-paypal-express-checkout/ button on checkout page
= 3.1.8 =
* Fixed - Do not redirect to order received page if customer has not completed local payment process
* Fixed - Disable Apple Pay button on variation product when no default product is selected
* Added - Mini-cart integration for GPay and Apple Pay.
* Added - Filter wc_stripe_get_source_args added
* Added - Email validation for local payment methods
* Added - WC tested up to: 4.3.2
* Updated - Stripe PHP lib version 7.45.0
= 3.1.7 =
* Fixed - SEPA payment flow
* Added - BECS payment method
* Updated - Stripe php lib version 7.40.0
* Updated - AliPay display logic
= 3.1.6 = 
* Updated - WC tested to 4.3.0
* Updated - Bumped PHP min version to 5.6
* Updated - Stripe php lib version 7.39.0
* Updated - Apple domain registration check for existing domain
* Fixed - Notice on cart page when payment request button active and cart emptied
* Fixed - Google Pay fee line item in wallet
* Added - New filters for API requests
= 3.1.5 = 
* Fixed - Capture type on product page checkout
* Fixed - WP 5.4.2 deprecation message for namespaces
* Fixed - PHP 7.4 warning message
* Updated - Error message API uses $.scroll_to_notices when available
* Updated - Apple Pay and Payment Request require phone and email based on required billing fields
* Updated - Webkit autofill text color
* Added - Non numerical check to wc_stripe_add_number_precision function
= 3.1.4 = 
* Updated - WC 4.2.0 support
* Added - Validation for local payment methods to ensure payment option cannot be empty
* Added - Account ID to Stripe JS initialization
* Added - Local payment method support for manual subscriptions
* Fixed - Exception that occurs after successful payment of a renewal order retry. 
= 3.1.3 = 
* Added - WC 4.1.1 support
* Added - Klarna payment categories option
* Added - Order ID filter added
* Added - Local payment button filter so text on buttons can be changed
* Update - Webhook controller returns 401 for failed signature
= 3.1.2 = 
* Added - Merchants can now control which payment buttons appear for each product and their positioning
* Added - VAT tax display for Apple Pay, GPay, Payment Request
* Added - Optional Stripe email receipt
* Updated - Stripe API version to 2020-03-02
* Fixed - iDEAL not redirecting on order pay page.
= 3.1.1 = 
* Fixed - Error when changing WCS payment method to new ACH payment method
* Fixed - Error when payment_intent status 'success' and order cancelled status applied
* Added - Recipient email for payment_intent
* Added - Translations for credit card decline errors
* Added - Option to force 3D secure for all transactions
* Added - Option to show generic credit card decline error
* Added - SEPA mandate message on checkout page
* Updated - Google Pay documentation
= 3.1.0 = 
* Added - FPX payment method
* Added - Alipay payment method
* Updated - Stripe connect integration
* Updated - WeChat support for other countries besides CN
* Updated - CSS so prevent theme overrides
* Fixed - WeChat QR code
= 3.0.9 = 
* Added - Payment methods with payment sheets like Apple Pay now show order items on order pay page instead of just total.
* Fixed - Error if 100% off coupon is used on checkout page.
= 3.0.8 = 
* Updated - billing phone and email check added for card payment
* Updated - template checkout/payment-method.php name changed to checkout/stripe-payment-method.php
* Updated - cart checkout button styling
* Added - Connection test in admin API settings
* Misc - WC 3.9.1
= 3.0.7 = 
* Added - WPML support for gateway titles and descriptions
* Added - ACH fee option
* Added - Webhook registration option in Admin
* Updated - Cart one click checkout buttons
* Updated - WC 3.9
= 3.0.6 = 
* Added - ACH subscription support
* Updated - Top of checkout styling
* Updated =Positioning of cart buttons. They are now below cart checkout button
= 3.0.5 =
* Added - ACH payment support
* Added - New credit card form
* Fixed - Klarna error if item totals don't equal order total.
* Updated - API version to 2019-12-03
* Updated - Local payment logic.
= 3.0.4 =
* Added - Bootstrap form added
* Updated - WC 3.8.1
* Fixed - Check for customer object in Admin pages for local payment methods
= 3.0.3 = 
* Fixed - Check added to wc_stripe_order_status_completed function to ensure capture charge is only called when Stripe is the payment gateway for the order.
* Updated - Stripe API version to 2019-11-05
= 3.0.2 = 
* Added - Klarna payments now supported
* Added - Bancontact
* Updated - Local payments webhook
= 3.0.1 = 
* Updated - Google Pay paymentDataCallbacks in JavaScript
* Updated - Text domain to match plugin slug
* Added - Dynamic price option for Google Pay
* Added - Pre-orders support
= 3.0.0 = 
* First commit

== Upgrade Notice ==
= 3.0.0 = 