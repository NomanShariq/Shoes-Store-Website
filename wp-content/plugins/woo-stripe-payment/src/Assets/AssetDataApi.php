<?php

namespace PaymentPlugins\Stripe\Assets;

class AssetDataApi {

	private $data = [];

	public function add( $key, $data ) {
		$this->data[ $key ] = $data;
	}

	public function get( $key, $default = null ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : $default;
	}

	public function remove( $key ) {
		unset( $this->data[ $key ] );
	}

	public function get_data() {
		return $this->data;
	}

	public function has_data() {
		return ! empty( $this->data );
	}

	public function print_data( $name, $data ) {
		$data = rawurlencode( wp_json_encode( $data ) );
		echo "<script id=\"$name\">
				window['$name'] = JSON.parse( decodeURIComponent( '" . esc_js( $data ) . "' ) );
		</script>";
	}

}