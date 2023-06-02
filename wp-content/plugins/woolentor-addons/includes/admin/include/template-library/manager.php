<?php  
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Woolentor_Template_Library_Manager{

    const TRANSIENT_KEY = 'woolentor_template_info';
    const TRANSIENT_GUTENBERG_KEY = 'woolentor_gutenberg_template_info';

    public static $endpoint = 'https://library.shoplentor.com/wp-json/woolentor/v1/templates';
    public static $templateapi = 'https://library.shoplentor.com/wp-json/woolentor/v1/templates/%s';

    // public static $endpoint_gutenberg = 'http://plugindev.test/wp-json/woolentor/v1/gutenbergtemplates';
    public static $endpoint_gutenberg = 'https://library.shoplentor.com/wp-json/woolentor/v1/gutenbergtemplates';

    private static $_instance = null;
    public static function instance(){
        if( is_null( self::$_instance ) ){
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    // Get Endpoint
    public static function get_api_endpoint(){
        if( is_plugin_active('woolentor-addons-pro/woolentor_addons_pro.php') && function_exists('woolentor_pro_template_endpoint') ){
            self::$endpoint = woolentor_pro_template_endpoint();
        }
        return self::$endpoint;
    }

    // Get Endpoint
    public static function get_gutenberg_api_endpoint(){
        return self::$endpoint_gutenberg;
    }
    
    // Get Template API
    public static function get_api_templateapi(){
        if( is_plugin_active('woolentor-addons-pro/woolentor_addons_pro.php') && function_exists('woolentor_pro_template_url') ){
            self::$templateapi = woolentor_pro_template_url();
        }
        return self::$templateapi;
    }

    // Set data to transient
    public static function set_templates_info( $url = '', $transient_key = '', $force_update = false ) {
        $transient = get_transient( $transient_key );
        if ( ! $transient || $force_update ) {
            $info = self::get_content_remote_request( $url );
            set_transient( $transient_key, wp_json_encode( $info ) , DAY_IN_SECONDS );
        }
    }

    // Get Template data
    public static function get_templates_info( $force_update = false ) {
        if ( !get_transient( self::TRANSIENT_KEY ) || $force_update ) {
            self::set_templates_info( self::get_api_endpoint(), self::TRANSIENT_KEY, true );
        }
        return is_array( get_transient( self::TRANSIENT_KEY ) ) ? get_transient( self::TRANSIENT_KEY ) : json_decode( get_transient( self::TRANSIENT_KEY ), JSON_OBJECT_AS_ARRAY );
    }

    // Get Gutenberg Template data
    public static function get_gutenberg_templates_info( $force_update = false ) {
        if ( !get_transient( self::TRANSIENT_GUTENBERG_KEY ) || $force_update ) {
            self::set_templates_info( self::get_gutenberg_api_endpoint(), self::TRANSIENT_GUTENBERG_KEY, true );
        }
        return is_array( get_transient( self::TRANSIENT_GUTENBERG_KEY ) ) ? get_transient( self::TRANSIENT_GUTENBERG_KEY ) : json_decode( get_transient( self::TRANSIENT_GUTENBERG_KEY ), JSON_OBJECT_AS_ARRAY );
    }

    // Request remote data
    public static function get_content_remote_request( $request_url ){
        global $wp_version;

        $response = wp_remote_get( 
			$request_url,
			array(
				'timeout'    => 25,
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url()
			) 
		);

        if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
            return [];
        }

        $result = json_decode( wp_remote_retrieve_body( $response ), true );
        return $result;

    }


}