<?php

namespace PaymentPlugins\Stripe\Assets;

class AssetsApi {

	private $base_path;

	private $assets_url;

	private $version;

	/**
	 * @param $path       Base path to the directory
	 * @param $assets_url Directory to the assets
	 * @param $version
	 */
	public function __construct( $path, $assets_url, $version ) {
		$this->base_path  = $path;
		$this->assets_url = $assets_url;
		$this->version    = $version;
	}


	public function register_script( $handle, $relative_path, $deps = [], $version = null, $footer = true ) {
		$file_name = str_replace( '.js', '.asset.php', $relative_path );
		$file      = $this->base_path . $file_name;
		$version   = is_null( $version ) ? $this->version : $version;
		if ( file_exists( $file ) ) {
			$assets  = include $file;
			$version = isset( $assets['version'] ) ? $assets['version'] : $version;
			if ( isset( $assets['dependencies'] ) ) {
				$deps = array_merge( $assets['dependencies'], $deps );
			}
		}
		$deps = apply_filters( 'wc_stripe_script_dependencies', $deps, $handle );

		wp_register_script( $handle, $this->assets_url( $relative_path ), $deps, $version, $footer );
	}

	public function register_style( $handle, $relative_path, $deps = [], $version = null ) {
		$version = is_null( $version ) ? $this->version : $version;
		wp_register_style( $handle, $this->assets_url( $relative_path ), $deps, $version );
	}

	public function assets_url( $relative_path ) {
		return $this->assets_url . trim( $relative_path, '/' );
	}

}