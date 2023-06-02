<?php
/**
 * @var WP_User $user
 */

?>
<div class="wc-stripe-user-info">
    <h2><?php esc_html_e( 'Stripe Customer ID\'s', 'woo-stripe-payment' ); ?></h2>
    <p><?php esc_html_e( 'If you change a customer ID, the customer\'s payment methods will be imported from your Stripe account.' ); ?></p>
    <p><?php esc_html_e( 'If you remove a customer ID, the customer\'s payment methods will be removed from the WC payment methods table.' ); ?></p>
    <table class="form-table">
        <tbody>
        <tr>
            <th><?php esc_html_e( 'Live ID', 'woo-stripe-payment' ); ?></th>
            <td><input type="text" id="wc_stripe_live_id"
                       name="wc_stripe_live_id"
                       value="<?php echo wc_stripe_get_customer_id( $user->ID, 'live' ); ?>"/>
            </td>
        </tr>
        <tr>
            <th><?php esc_html_e( 'Test ID', 'woo-stripe-payment' ); ?></th>
            <td><input type="text" id="wc_stripe_test_id"
                       name="wc_stripe_test_id"
                       value="<?php echo wc_stripe_get_customer_id( $user->ID, 'test' ); ?>"/>
            </td>
        </tr>
        </tbody>
    </table>
    <h2><?php esc_html_e( 'Stripe Live Payment Methods', 'woo-stripe-payment' ); ?></h2>
	<?php if ( $payment_methods['live'] ) : ?>
        <table class="wc-stripe-payment-methods">
            <thead>
            <tr>
                <th><?php esc_html_e( 'Payment Gateway', 'woo-stripe-payment' ); ?></th>
                <th><?php esc_html_e( 'Payment Method', 'woo-stripe-payment' ); ?></th>
                <th><?php esc_html_e( 'Token', 'woo-stripe-payment' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'woo-stripe-payment' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $payment_methods['live'] as $token ) : ?>
                <tr>
                    <td><?php echo $token->get_gateway_id(); ?></td>
                    <td><?php echo $token->get_payment_method_title(); ?></td>
                    <td><?php echo $token->get_token(); ?></td>
                    <td><input type="checkbox" name="payment_methods[live][]"
                               value="<?php echo $token->get_id() . ':' . $token->get_token() ?>"/></td>
                </tr>
			<?php endforeach; ?>
            <tr>
                <th><?php esc_html_e( 'Action', 'delete' ); ?></th>
                <td><select name="live_payment_method_actions">
                        <option value="none" selected><?php esc_html_e( 'No Action', 'woo-stripe-payment' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Delete', 'woo-stripe-payment' ); ?></option>
                    </select></td>
            </tr>
            </tbody>
        </table>
	<?php else : ?>
		<?php esc_html_e( 'No live payment methods saved', 'woo-stripe-payment' ); ?>
	<?php endif; ?>
    <h2><?php esc_html_e( 'Stripe Test Payment Methods', 'woo-stripe-payment' ); ?></h2>
	<?php if ( $payment_methods['test'] ) : ?>
        <table class="wc-stripe-payment-methods">
            <thead>
            <tr>
                <th><?php esc_html_e( 'Payment Gateway', 'woo-stripe-payment' ); ?></th>
                <th><?php esc_html_e( 'Payment Method', 'woo-stripe-payment' ); ?></th>
                <th><?php esc_html_e( 'Token', 'woo-stripe-payment' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'woo-stripe-payment' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php foreach ( $payment_methods['test'] as $token ) : ?>
                <tr>
                    <td><?php echo $token->get_gateway_id(); ?></td>
                    <td><?php echo $token->get_payment_method_title(); ?></td>
                    <td><?php echo $token->get_token(); ?></td>
                    <td><input type="checkbox" name="payment_methods[test][]"
                               value="<?php echo $token->get_id() . ':' . $token->get_token() ?>"/></td>
                </tr>
			<?php endforeach; ?>
            <tr>
                <th><?php esc_html_e( 'Action', 'delete' ); ?></th>
                <td><select name="test_payment_method_actions">
                        <option value="none" selected><?php esc_html_e( 'No Action', 'woo-stripe-payment' ); ?></option>
                        <option value="delete"><?php esc_html_e( 'Delete', 'woo-stripe-payment' ); ?></option>
                    </select></td>
            </tr>
            </tbody>
        </table>
	<?php else : ?>
		<?php esc_html_e( 'No test payment methods saved', 'woo-stripe-payment' ); ?>
	<?php endif; ?>
	<?php printf( __( '%1$sNote:%2$s Payment methods will be deleted from your WordPress site and within Stripe.', 'woo-stripe-payment' ), '<strong>', '</strong>' ); ?>
</div>
