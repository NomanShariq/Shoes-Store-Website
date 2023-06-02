<?php

namespace PaymentPlugins\Stripe\Utilities;

class PaymentMethodUtils {

	/**
	 * Sort the payment tokens by the default.
	 *
	 * @param $tokens
	 *
	 * @return mixed
	 */
	public static function sort_by_default( $tokens ) {
		if ( \is_array( $tokens ) ) {
			usort( $tokens, function ( $a ) {
				if ( \is_object( $a ) && method_exists( $a, 'is_default' ) ) {
					return $a->is_default() ? - 1 : 1;
				}

				return - 1;
			} );
		}

		return $tokens;
	}

}