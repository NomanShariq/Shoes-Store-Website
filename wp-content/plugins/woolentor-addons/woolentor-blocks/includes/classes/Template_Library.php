<?php
namespace WooLentorBlocks;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Library
 */
class Template_Library {

    /**
     * [$_instance]
     * @var null
     */
    private static $_instance = null;

    /**
     * [instance] Initializes a singleton instance
     * @return [Template_Library]
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

	/**
	 * The Constructor.
	 */
	public function __construct() {
		
	}

	/**
	 * Resgister API routes
	 */
	public function register_routes( $namespace ){

		register_rest_route( $namespace, 'importtemplate',
			[
				[
					'methods'  => 'POST',
					'callback' => [ $this, 'import_template' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args' => []
				]
			]
		);

	}

	/**
     * Api permission check
     */
    public function permission_check() {
        if( current_user_can( 'edit_posts' ) ){
            return true;
        }else{
            return false;
        }
    }

    //Template data import
    public function import_template( $request ){
        $params = $request->get_params();

        $response_data = [];
        if ( !isset( $params['template_id'] ) ) {
            return rest_ensure_response( $response_data );
        }

        $templateurl    = sprintf( \Woolentor_Template_Library_Manager::get_gutenberg_api_endpoint().'/%s', $params['template_id'] );
        $response_data  = \Woolentor_Template_Library_Manager::get_content_remote_request( $templateurl );

        return rest_ensure_response( $response_data );

    }


}