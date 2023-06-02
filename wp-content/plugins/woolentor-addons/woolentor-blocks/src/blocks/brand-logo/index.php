<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

	$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
	$classes 	 = array( $uniqClass, 'ht-brand-wrap' );
	$areaClasses = array( 'woolentor-brand-area' );

	!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';
	!empty( $settings['align'] ) ? $areaClasses[] = 'align'.$settings['align'] : '';

	!empty( $settings['columns']['desktop'] ) ? $areaClasses[] = 'woolentor-grid-columns-'.$settings['columns']['desktop'] : 'woolentor-grid-columns-4';
	!empty( $settings['columns']['laptop'] ) ? $areaClasses[] = 'woolentor-grid-columns-laptop-'.$settings['columns']['laptop'] : 'woolentor-grid-columns-laptop-3';
	!empty( $settings['columns']['tablet'] ) ? $areaClasses[] = 'woolentor-grid-columns-tablet-'.$settings['columns']['tablet'] : 'woolentor-grid-columns-tablet-2';
	!empty( $settings['columns']['mobile'] ) ? $areaClasses[] = 'woolentor-grid-columns-mobile-'.$settings['columns']['mobile'] : 'woolentor-grid-columns-mobile-1';

	$default_img = '<img src="'.WOOLENTOR_BLOCK_URL.'/src/assets/images/brand.png'.'" alt="'.esc_html__('Brand Logo','woolentor').'">';
	$brands = $settings['brandLogoList'];

	// Slider Options
	$slider_settings = [];
	if( $settings['slider'] === true ){
		$is_rtl = is_rtl();
		$direction = $is_rtl ? 'rtl' : 'ltr';
		$slider_settings = [
			'arrows' => ( true === $settings['arrows'] ),
			'dots' => ( true === $settings['dots'] ),
			'autoplay' => ( true === $settings['autoplay'] ),
			'autoplay_speed' => absint( $settings['autoplaySpeed'] ),
			'animation_speed' => absint( $settings['animationSpeed'] ),
			'pause_on_hover' => ( true === $settings['pauseOnHover'] ),
			'rtl' => $is_rtl,
		];

		$slider_responsive_settings = [
			'product_items' => $settings['sliderItems'],
			'scroll_columns' => $settings['scrollColumns'],
			'tablet_width' => $settings['tabletWidth'],
			'tablet_display_columns' => $settings['tabletDisplayColumns'],
			'tablet_scroll_columns' => $settings['tabletScrollColumns'],
			'mobile_width' => $settings['mobileWidth'],
			'mobile_display_columns' => $settings['mobileDisplayColumns'],
			'mobile_scroll_columns' => $settings['mobileScrollColumns'],

		];
		$slider_settings = array_merge( $slider_settings, $slider_responsive_settings );
	}
		
?>
<div class="<?php echo implode(' ', $areaClasses ); ?>">
	<div class="<?php echo implode(' ', $classes ); ?>">
		<?php
			if( is_array( $brands ) ){

				if( $settings['slider'] === true ){
					echo '<div id="product-slider-' . $settings['blockUniqId'] . '" dir="'.$direction.'" class="product-slider" data-settings=\'' . wp_json_encode( $slider_settings ) . '\' style="display:none">';
				}else{
					echo '<div class="woolentor-grid '.( $settings['noGutter'] === true ? 'woolentor-no-gutters' : '' ).'">';
				}
					foreach ( $brands as $key => $brand ) {
	
						$image = !empty( $brand['image']['id'] ) ? wp_get_attachment_image( $brand['image']['id'], 'full' ) : $default_img;
						$logo  = !empty( $brand['link'] ) ? sprintf('<a href="%s" target="_blank">%s</a>',esc_url( $brand['link'] ), $image ) : $image;
	
						?>
							<div class="woolentor-grid-column">
								<div class="wl-single-brand">
									<?php echo wp_kses_post( $logo ); ?>
								</div>
							</div>
						<?php
					}
				echo '</div>';
			}
		?>
	</div>
</div>