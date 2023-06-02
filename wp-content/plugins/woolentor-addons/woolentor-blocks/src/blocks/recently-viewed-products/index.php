<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass, 'woolentor_block_recently_viewed_product' );
!empty( $settings['align'] ) ? $areaClasses[] = 'align'.$settings['align'] : '';
!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

!empty( $settings['columns']['desktop'] ) ? $areaClasses[] = 'woolentor-grid-columns-'.$settings['columns']['desktop'] : 'woolentor-grid-columns-4';
!empty( $settings['columns']['laptop'] ) ? $areaClasses[] = 'woolentor-grid-columns-laptop-'.$settings['columns']['laptop'] : 'woolentor-grid-columns-laptop-3';
!empty( $settings['columns']['tablet'] ) ? $areaClasses[] = 'woolentor-grid-columns-tablet-'.$settings['columns']['tablet'] : 'woolentor-grid-columns-tablet-2';
!empty( $settings['columns']['mobile'] ) ? $areaClasses[] = 'woolentor-grid-columns-mobile-'.$settings['columns']['mobile'] : 'woolentor-grid-columns-mobile-1';


$title_html_tag = woolentor_validate_html_tag( $settings['titleTag'] );

$products_list = woolentor_get_track_user_data();

if( $block['is_editor'] && empty( $products_list ) ){
    echo '<div class="elementor-panel" style="margin-bottom:10px;"><div class="elementor-panel-alert elementor-panel-alert-warning">'. __( 'You haven\'t viewed at any of the products yet. Below are demo product for the editing mode.', 'woolentor' ) . '</div></div>';
}else{
    if ( empty( $products_list ) ) {
        if( $settings['showEmptyMessage'] ){
            echo '<div class="woolentor-no-view-product">'. trim( $settings['emptyMessage'] ) .'</div>';
        }
        return '';
    }
}

echo '<div class="'.implode(' ', $areaClasses ).'">';

    $products_list_value = array_values( $products_list );

    if( $settings['order'] == 'DESC' ){
        $products_list_value = array_reverse( $products_list_value );
    }

    $args = array(
        'post_type'            => 'product',
        'ignore_sticky_posts'  => 1,
        'no_found_rows'        => 1,
        'posts_per_page'       => $settings['perPage'],
        'orderby'              => 'post__in',
        'post__in'             => isset( $products_list_value ) ? $products_list_value : [],
    );
    $products = new \WP_Query( $args );

    if ( $products->have_posts() ) {
        echo '<div class="woolentor-grid '.( $settings['noGutter'] ? 'woolentor-no-gutters' : '' ).'">';

        while( $products->have_posts() ): $products->the_post();
            ?>
                <div class="woolentor-grid-column">
                    <div class="woolentor-recently-viewed-product">
                        <div class="woolentor-recently-view-image">
                            <?php
                                if( class_exists('WooCommerce') && $settings['showBadge'] == true ){ 
                                    woolentor_custom_product_badge(); 
                                    woolentor_sale_flash();
                                }
                            ?>
                            <a href="<?php the_permalink();?>"> 
                                <?php woocommerce_template_loop_product_thumbnail(); ?> 
                            </a>
                        </div>
                        
                        <?php if( $settings['showTitle'] == true || $settings['showPrice'] == true || $settings['showAddToCart'] == true ): ?>
                            <div class="woolentor-recently-view-content">
                                <?php
                                    if( $settings['showTitle'] == true ){
                                        echo sprintf( "<%s class='woolentor-recently-view-title'><a href='%s'>%s</a></%s>", $title_html_tag, get_the_permalink(), get_the_title(), $title_html_tag );
                                    }
                                    if( $settings['showPrice'] == true ){
                                        echo '<div class="woolentor-recently-view-price">';
                                            woocommerce_template_loop_price();
                                        echo '</div>';
                                    }
                                    if( $settings['showAddToCart'] == true ){
                                        woocommerce_template_loop_add_to_cart();
                                    }
                                ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
        endwhile;
        echo '</div>';
    }

echo '</div>';