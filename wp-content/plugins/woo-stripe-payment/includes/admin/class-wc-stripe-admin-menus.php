<?php

defined( 'ABSPATH' ) || exit();

/**
 *
 * @since   3.0.0
 * @package Stripe/Admin
 *
 */
class WC_Stripe_Admin_Menus {

	public static function init() {
		//add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 10 );
		add_action( 'admin_menu', array( __CLASS__, 'sub_menu' ), 20 );
	}

	public static function admin_menu() {
		add_menu_page( __( 'Stripe Gateway', 'woo-stripe-payment' ), __( 'Stripe Gateway', 'woo-stripe-payment' ), 'manage_woocommerce', 'wc_stripe', null, null, '7.458' );
	}

	public static function sub_menu() {
		add_submenu_page( 'woocommerce', __( 'Stripe by Payment Plugins', 'woo-stripe-payment' ), __( 'Stripe by Payment Plugins', 'woo-stripe-payment' ), 'manage_woocommerce', 'wc-stripe-main', array( __CLASS__, 'main_page' ) );
	}

	public static function main_page() {
		if ( isset( $_GET['section'] ) ) {
			$section = sanitize_text_field( $_GET['section'] );
			do_action( 'wc_stripe_admin_section_' . $section );
		} else {
			WC_Stripe_Admin_Welcome::output();
		}
	}

}

WC_Stripe_Admin_Menus::init();
