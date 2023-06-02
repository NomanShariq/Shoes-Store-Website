<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if( $block['is_editor'] ){
	global $product;
    \WC()->frontend_includes();
	\WC()->session = new \WC_Session_Handler();
	\WC()->session->init();
	\WC()->cart = new \WC_Cart();
	$product = wc_get_product( woolentor_get_last_product_id() );
} else{
	$product = wc_get_product();
}
if ( empty( $product ) ) { return; }

require_once ('manager.php');

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass, 'woolentor-product-addtocart' );
!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

$product_type = $product->get_type();

$button_classes = [ esc_attr($product_type) ];
( !empty( $settings['layoutStyle'] ) && $settings['layoutStyle'] != '1' ) ? $button_classes[] = 'wl-addto-cart' : '';
!empty( $settings['layoutStyle'] ) ? $button_classes[] = 'wl-style-'.$settings['layoutStyle'] : '';
$button_classes[] = !empty( $settings['quantityText'] ) ? 'wl-hasquantity-txt' : 'wl-nonequantity-txt';

( $product_type === 'external' ) ? $button_classes[] = $settings['buttonsPosition'] : '';

if( '1' != $settings['layoutStyle'] ){
	(new \WooLentorBlocks\Customize_Button())->wishlist_compare_button( $settings, $product_type );
}

echo '<div class="'.implode(' ', $areaClasses ).'" data-producttype="'.esc_attr( $product_type ).'">';
?>
	<div class="<?php echo implode(' ', $button_classes ); ?>">
		<?php woocommerce_template_single_add_to_cart(); ?>
	</div>
<?php
echo '</div>';