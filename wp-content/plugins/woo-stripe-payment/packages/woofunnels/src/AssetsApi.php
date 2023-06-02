<?php

namespace PaymentPlugins\WooFunnels\Stripe;

class AssetsApi {

	private $path;

	private $assets_url;

	private $version;

	public function __construct( $path, $version ) {
		$this->path       = dirname( $path ) . '/';
		$this->assets_url = plugin_dir_url( $path );
		$this->version    = $version;
	}

	public function register_script( $handle, $relative_path, $deps = [] ) {
		$file   = $this->path . str_replace( '.js', '.asset.php', $relative_path );
		$params = [ 'dependencies' => $deps, 'version' => $this->version ];
		if ( file_exists( $file ) ) {
			$params = require_once $file;
		}
		wp_register_script( $handle, $this->assets_url . $relative_path, $params['dependencies'], $params['version'], true );
	}

	public function enqueue_script( $handle, $relative_path, $deps = [] ) {
		$this->register_script( $handle, $relative_path, $deps );
		wp_enqueue_script( $handle );
	}

	public function register_style( $handle, $relative_path ) {
		wp_register_style( $handle, $this->assets_url . $relative_path );
	}

	public function enqueue_style( $handle, $relative_path ) {
		$this->register_style( $handle, $relative_path );
		wp_enqueue_style( $handle );
	}

	public function do_script_items( $handles ) {
		global $wp_scripts;
		if ( is_string( $handles ) ) {
			$handles = [ $handles ];
		}
		$wp_scripts->do_items( $handles );
	}

}