<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$uniqClass 	 = 'woolentorblock-'.$settings['blockUniqId'];
$areaClasses = array( $uniqClass, 'woolentor_block_product_reviews' );
!empty( $settings['align'] ) ? $areaClasses[] = 'align'.$settings['align'] : '';
!empty( $settings['className'] ) ? $areaClasses[] = esc_attr( $settings['className'] ) : '';

$is_editor = ( $block['is_editor'] == 'yes' ) ? true : false;

global $product;
$product = $is_editor ? \WooLentor_Default_Data::instance()->get_product('') : wc_get_product();

echo '<div class="'.implode(' ', $areaClasses ).'">';
    ?>
        <div class="woolentor-product-review">
            <?php if( $is_editor ): ?>
                <div id="review_form_wrapper">
                    <div id="review_form">
                        <div id="respond" class="comment-respond">
                            <form action="#" method="post" id="commentform" class="comment-form" novalidate="">
                                <div class="comment-form-rating">
                                <label for="rating"><?php esc_html_e('Your rating', 'woolentor'); ?> <span class="required"><?php esc_html_e('*', 'woolentor'); ?></span></label>
                                <p class="stars">
                                    <span>
                                        <a class="star-1" href="#"><?php esc_html_e('1', 'woolentor'); ?></a>
                                        <a class="star-2" href="#"><?php esc_html_e('2', 'woolentor'); ?></a>
                                        <a class="star-3" href="#"><?php esc_html_e('3', 'woolentor'); ?></a>
                                        <a class="star-4" href="#"><?php esc_html_e('4', 'woolentor'); ?></a>
                                        <a class="star-5" href="#"><?php esc_html_e('5', 'woolentor'); ?></a>
                                    </span>
                                </p>
                                <select name="rating" id="rating" required="" style="display: none;">
                                    <option value=""><?php esc_html_e('Rate…', 'woolentor'); ?></option>
                                    <option value="5"><?php esc_html_e('Perfect', 'woolentor'); ?></option>
                                    <option value="4"><?php esc_html_e('Good', 'woolentor'); ?></option>
                                    <option value="3"><?php esc_html_e('Average', 'woolentor'); ?></option>
                                    <option value="2"><?php esc_html_e('Not that bad', 'woolentor'); ?></option>
                                    <option value="1"><?php esc_html_e('Very poor', 'woolentor'); ?></option>
                                </select>
                                </div>
                                <p class="comment-form-comment">
                                    <label for="comment">
                                        <?php esc_html_e('Your review', 'woolentor'); ?><span class="required"><?php esc_html_e('*', 'woolentor'); ?></span>
                                    </label>
                                    <textarea id="comment" name="comment" cols="45" rows="8" required=""></textarea>
                                </p>
                                <p class="form-submit">
                                    <input name="submit" type="submit" id="submit" class="submit" value="<?php esc_attr_e('Submit', 'woolentor'); ?>">
                                    <input type="hidden" name="comment_post_ID" value="307" id="comment_post_ID">
                                    <input type="hidden" name="comment_parent" id="comment_parent" value="0">
                                </p>
                            </form>
                        </div>
                        <!-- #respond -->
                    </div>
                </div>
                <div class="clear"></div>
            <?php endif; ?>
            <?php
                if ( empty( $product ) ) { return; }
                echo '<div class="woocommerce-tabs-list">';
                    if ( comments_open() && !$is_editor ) {
                        comments_template();
                    }
                echo '</div>';
            ?>
        </div>
    <?php
    
echo '</div>';