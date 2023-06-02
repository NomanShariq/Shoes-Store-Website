<?php
/**
 *
 */

?>
<div class="wc-stripe-support__page">
    <div class="wc-stripe-main__container">
		<?php include dirname( __FILE__ ) . '/html-main-navigation.php' ?>
        <div class="wc-stripe-main__header">
            <div class="description">
                <h1><?php esc_html_e( 'We\'re here to help you.', 'woo-stripe-payment' ) ?></h1>
                <p>
					<?php esc_html_e( 'Have a question? Need some help? Please submit a ticket and one of our support specialists will get back to you.', 'woo-stripe-payment' ) ?>
                    <br/>
					<?php esc_html_e( 'Note: we commit to a 3 day turnaround with free support.', 'woo-stripe-payment' ) ?>
                </p>
            </div>
            <!--<div class="wc-stripe-welcome-header__design"></div>-->
        </div>
        <div class="wc-stripe-support__content">
            <div class="wc-stripe-main__row justify-content-start">
                <div class="wc-stripe-main__card">
                    <div class="wc-stripe-main-card__content support">
                        <div class="icon-container support">
                            <!--<span class="dashicons dashicons-admin-users"></span>-->
                            <img class="icon" src="<?php echo stripe_wc()->assets_url( 'img/support.svg' ) ?>"/>
                        </div>
                        <div class="card-header">
                            <p>
								<?php esc_html_e( 'While we commit to a 3 day turnaround, most tickets receive a response within 24 hrs.', 'woo-stripe-payment' ) ?>
                            </p>
                            <p>
								<?php esc_html_e( 'Click the Create Ticket button and enter all the required information.', 'woo-stripe-payment' ) ?>
                                <br/><br/>
                            </p>
                            <button id="stripeSupportButton" class="wc-stripe-card-button"><?php _e( 'Create Ticket', 'woo-stripe-payment' ) ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>