<?php
defined( 'ABSPATH' ) || exit();

global $wpdb;

// delete transients that have incorrect timeouts
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '%_transient_timeout__stripe_lock_order_%', '%_transient__stripe_lock_order_%' ) );
