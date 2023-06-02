<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @package Stripe/Admin
 * @author User
 *
 */
class WC_Stripe_Admin_Settings {

	public static function init() {
		add_action( 'woocommerce_settings_checkout', array( __CLASS__, 'output' ) );
		add_filter( 'wc_stripe_settings_nav_tabs', array( __CLASS__, 'admin_settings_tabs' ), 20 );
		add_action( 'woocommerce_update_options_checkout', array( __CLASS__, 'save' ) );
	}

	public static function output() {
		global $current_section;
		do_action( 'woocommerce_stripe_settings_checkout_' . $current_section );
	}

	/**
	 * @deprecated
	 */
	public static function output_advanced_settings() {
		self::output_custom_section( '' );
	}

	/**
	 * @deprecated
	 */
	public static function output_local_gateways() {
		self::output_custom_section( 'stripe_ideal' );
	}

	/**
	 * @deprecated
	 */
	public static function output_custom_section( $sub_section = '' ) {
		global $current_section, $wc_stripe_subsection;
		$wc_stripe_subsection = isset( $_GET['stripe_sub_section'] ) ? sanitize_title( $_GET['stripe_sub_section'] ) : $sub_section;
		do_action( 'woocommerce_stripe_settings_checkout_' . $current_section . '_' . $wc_stripe_subsection );
	}

	/**
	 * @deprecated
	 */
	public static function save_local_gateway() {
		self::save_custom_section( 'stripe_ideal' );
	}

	/**
	 * @deprecated
	 */
	public static function save_custom_section( $sub_section = '' ) {
		global $current_section, $wc_stripe_subsection;
		$wc_stripe_subsection = isset( $_GET['stripe_sub_section'] ) ? sanitize_title( $_GET['stripe_sub_section'] ) : $sub_section;
		do_action( 'woocommerce_update_options_checkout_' . $current_section . '_' . $wc_stripe_subsection );
	}

	public static function save() {
		global $current_section;
		if ( $current_section && ! did_action( 'woocommerce_update_options_checkout_' . $current_section ) ) {
			do_action( 'woocommerce_update_options_checkout_' . $current_section );
		}
	}

	public static function admin_settings_tabs( $tabs ) {
		$tabs['stripe_afterpay'] = __( 'Local Gateways', 'woo-stripe-payment' );

		return $tabs;
	}

	/**
	 * @deprecated
	 */
	public static function before_options() {
		global $current_section, $wc_stripe_subsection;
		do_action( 'wc_stripe_settings_before_options_' . $current_section . '_' . $wc_stripe_subsection );
	}

	/**
	 * @param        $settings
	 * @param string $section_id
	 *
	 * @return mixed
	 * @deprecated
	 */
	public static function get_email_settings( $settings, $section_id = '' ) {
		if ( ! $section_id ) {
			$settings[] = array(
				'type'  => 'title',
				'title' => __( 'Stripe Email Options', 'woo-stripe-payment' ),
			);
			$settings[] = array(
				'type'     => 'checkbox',
				'title'    => __( 'Email Receipt', 'woo-stripe-payment' ),
				'id'       => 'woocommerce_stripe_email_receipt',
				'autoload' => false,
				'desc'     => __( 'If enabled, an email receipt will be sent to the customer by Stripe when the order is processed.',
					'woo-stripe-payment' ),
			);
			$settings[] = array(
				'type' => 'sectionend',
				'id'   => 'stripe_email',
			);
		}

		return $settings;
	}

}

WC_Stripe_Admin_Settings::init();
