<?php
$account_id = stripe_wc()->api_settings->get_option( 'account_id' );
if ( $account_id ) {
	stripe_wc()->account_settings->save_account_settings( $account_id );
}