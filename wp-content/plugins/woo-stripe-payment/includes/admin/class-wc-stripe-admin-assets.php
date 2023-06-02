<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 */
class WC_Stripe_Admin_Assets {

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_print_scripts', array( __CLASS__, 'localize_scripts' ) );
		add_action( 'admin_footer', array( __CLASS__, 'localize_scripts' ) );
		add_action( 'wc_stripe_localize_stripe_advanced_settings', array( __CLASS__, 'localize_advanced_scripts' ) );
	}

	public function enqueue_scripts() {
		$screen    = get_current_screen();
		$screen_id = $screen ? $screen->id : '';
		$js_path   = stripe_wc()->assets_url() . 'js/';
		$css_path  = stripe_wc()->assets_url() . 'css/';

		wp_register_script( 'wc-stripe-help-widget', $js_path . 'admin/help-widget.js', array( 'jquery' ), stripe_wc()->version(), true );

		wp_register_script( 'wc-stripe-admin-settings', $js_path . 'admin/admin-settings.js', array(
			'jquery',
			'jquery-blockui'
		), stripe_wc()->version, true );
		wp_register_script( 'wc-stripe-meta-boxes-order', $js_path . 'admin/meta-boxes-order.js', array(
			'jquery',
			'jquery-blockui'
		), stripe_wc()->version, true );
		wp_register_script(
			'wc-stripe-product-data',
			$js_path . 'admin/meta-boxes-product-data.js',
			array(
				'jquery',
				'jquery-blockui',
				'jquery-ui-sortable',
				'jquery-ui-widget',
				'jquery-ui-core',
				'jquery-tiptip',
			),
			stripe_wc()->version(),
			true
		);
		wp_register_style( 'wc-stripe-admin-style', $css_path . 'admin/admin.css', array(), stripe_wc()->version );
		wp_register_style( 'wc-stripe-admin-main-style', $css_path . 'admin/main.css', array( 'woocommerce_admin_styles' ), stripe_wc()->version );

		if ( strpos( $screen_id, 'wc-settings' ) !== false ) {
			if ( isset( $_REQUEST['section'] ) && preg_match( '/stripe_[\w]*/', $_REQUEST['section'] ) ) {
				wp_enqueue_script( 'wc-stripe-admin-settings' );
				wp_enqueue_style( 'wc-stripe-admin-style' );
				wp_style_add_data( 'wc-stripe-admin-style', 'rtl', 'replace' );
				wp_localize_script(
					'wc-stripe-admin-settings',
					'wc_stripe_setting_params',
					array(
						'routes'     => array(
							'apple_domain'      => WC_Stripe_Rest_API::get_admin_endpoint( stripe_wc()->rest_api->settings->rest_uri( 'apple-domain' ) ),
							'create_webhook'    => WC_Stripe_Rest_API::get_admin_endpoint( stripe_wc()->rest_api->settings->rest_uri( 'create-webhook' ) ),
							'delete_webhook'    => WC_Stripe_Rest_API::get_admin_endpoint( stripe_wc()->rest_api->settings->rest_uri( 'delete-webhook' ) ),
							'connection_test'   => WC_Stripe_Rest_API::get_admin_endpoint( stripe_wc()->rest_api->settings->rest_uri( 'connection-test' ) ),
							'delete_connection' => WC_Stripe_Rest_API::get_admin_endpoint( stripe_wc()->rest_api->settings->rest_uri( 'delete-connection' ) )
						),
						'rest_nonce' => wp_create_nonce( 'wp_rest' ),
						'messages'   => array(
							'delete_connection' => __( 'Are you sure you want to delete your connection data?', 'woo-stripe-payment' )
						)
					)
				);
			}
		}
		if ( $screen_id === 'shop_order' || $screen_id === 'woocommerce_page_wc-orders' ) {
			wp_enqueue_style( 'wc-stripe-admin-style' );
		}
		if ( $screen_id === 'product' ) {
			wp_enqueue_script( 'wc-stripe-product-data' );
			wp_enqueue_style( 'wc-stripe-admin-style' );
			wp_localize_script(
				'wc-stripe-product-data',
				'wc_stripe_product_params',
				array(
					'_wpnonce' => wp_create_nonce( 'wp_rest' ),
					'routes'   => array(
						'enable_gateway' => stripe_wc()->rest_api->product_data->rest_url( 'gateway' ),
						'save'           => stripe_wc()->rest_api->product_data->rest_url( 'save' ),
					),
				)
			);
		}
		if ( $screen_id === 'woocommerce_page_wc-stripe-main' ) {
			wp_enqueue_style( 'wc-stripe-admin-main-style' );
			wp_enqueue_script( 'wc-stripe-main-script', $js_path . 'admin/main.js', array( 'jquery' ), stripe_wc()->version, true );
			if ( isset( $_GET['section'] ) ) {
				if ( $_GET['section'] === 'support' ) {
					wp_enqueue_script( 'wc-stripe-help-widget' );
				}
			}
		}
	}

	public static function localize_scripts() {
		global $current_section, $wc_stripe_subsection;
		if ( ! empty( $current_section ) ) {
			$wc_stripe_subsection = isset( $_GET['sub_section'] ) ? sanitize_title( $_GET['sub_section'] ) : '';
			do_action( 'wc_stripe_localize_' . $current_section . '_settings' );
			// added for WC 3.0.0 compatability.
			remove_action( 'admin_footer', array( __CLASS__, 'localize_scripts' ) );
		}
	}

	public static function localize_advanced_scripts() {
		global $current_section, $wc_stripe_subsection;
		do_action( 'wc_stripe_localize_' . $wc_stripe_subsection . '_settings' );
	}

}

new WC_Stripe_Admin_Assets();
