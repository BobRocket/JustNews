<?php
/**
 * Display single product reviews (comments)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product-reviews.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     3.6.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

global $product;

if ( ! comments_open() ) {
    return;
}

?>
<div id="reviews" class="woocommerce-Reviews">
    <div id="comments" class="entry-comments">
        <?php if ( have_comments() ) : ?>

            <ol class="commentlist">
                <?php wp_list_comments( apply_filters( 'woocommerce_product_review_list_args', array( 'callback' => 'woocommerce_comments' ) ) ); ?>
            </ol>

            <?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) :
                echo '<nav class="woocommerce-pagination">';
                paginate_comments_links( apply_filters( 'woocommerce_comment_pagination_args', array(
                    'prev_text' => '&larr;',
                    'next_text' => '&rarr;',
                    'type'      => 'list',
                ) ) );
                echo '</nav>';
            endif; ?>

        <?php else : ?>

            <p class="woocommerce-noreviews"><?php esc_html_e( 'There are no reviews yet.', 'wpcom' ); ?></p>

        <?php endif; ?>
    </div>

    <?php if ( get_option( 'woocommerce_review_rating_verification_required' ) === 'no' || wc_customer_bought_product( '', get_current_user_id(), $product->get_id() ) ) : ?>

        <div id="review_form_wrapper">
            <div id="review_form">
                <?php
                $commenter = wp_get_current_commenter();
                $login_url = wp_login_url();
                $formsubmittext = '';
                if(is_user_logged_in()) {
                    $user = wp_get_current_user();
                    $user_identity = $user->exists() ? $user->display_name : '';
                    $formsubmittext = '<div class="pull-left form-submit-text">'.get_avatar( $user->ID, 60 ).'<span>'.$user_identity.'</span></div>';
                }
                $comment_form = array(
                    'title_reply'          => have_comments() ? __( 'Add a review', 'wpcom' ) : sprintf( __( 'Be the first to review &ldquo;%s&rdquo;', 'wpcom' ), get_the_title() ),
                    'title_reply_to'       => __( 'Leave a Reply to %s', 'wpcom' ),
                    'title_reply_before'   => '<span id="reply-title" class="comment-reply-title">',
                    'title_reply_after'    => '</span>',
                    'comment_notes_after'  => '',
                    'fields'               => array(
                        'author' => '<div class="comment-form-author">' . '<label for="author">' . esc_html__( 'Name: ', 'wpcom' ) . ' <span class="required">*</span></label> ' .
                            '<input id="author" name="author" type="text" value="' . esc_attr( $commenter['comment_author'] ) . '" size="30" aria-required="true" required /></div>',
                        'email'  => '<div class="comment-form-email"><label for="email">' . esc_html__( 'Email: ', 'wpcom' ) . ' <span class="required">*</span></label> ' .
                            '<input id="email" name="email" type="email" value="' . esc_attr( $commenter['comment_author_email'] ) . '" size="30" aria-required="true" required /></div>',
                    ),
                    'label_submit'  => __( 'Submit', 'woocommerce' ),
                    'submit_field' => '<div class="form-submit">'.$formsubmittext.'%1$s %2$s</div>',
                    'logged_in_as'  => '',
                    'comment_field' => '',
                );

                if ( $account_page_url = wc_get_page_permalink( 'myaccount' ) ) {
                    $comment_form['must_log_in'] = '<div class="comment-form"><div class="comment-must-login">请登录后评价...</div><div class="form-submit"><div class="form-submit-text pull-left"><a href="'.$login_url.'">登录</a>后才能评价</div> <input name="submit" type="submit" id="must-submit" class="submit" value="发表"></div></div>';
                }

                if ( wc_review_ratings_enabled() ) {
                    $comment_form['comment_field'] = '<div class="comment-form-rating"><label for="rating">' . esc_html__( 'Your rating', 'woocommerce' ) . '</label><select name="rating" id="rating" aria-required="true" required>
							<option value="">' . esc_html__( 'Rate&hellip;', 'woocommerce' ) . '</option>
							<option value="5">' . esc_html__( 'Perfect', 'woocommerce' ) . '</option>
							<option value="4">' . esc_html__( 'Good', 'woocommerce' ) . '</option>
							<option value="3">' . esc_html__( 'Average', 'woocommerce' ) . '</option>
							<option value="2">' . esc_html__( 'Not that bad', 'woocommerce' ) . '</option>
							<option value="1">' . esc_html__( 'Very poor', 'woocommerce' ) . '</option>
						</select></div>';
                }

                $comment_form['comment_field'] .= '<div class="comment-form-comment"><textarea id="comment" name="comment" cols="45" rows="8" aria-required="true" class="required" placeholder="请输入您的评价..."></textarea></div>';

                comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form ) );
                ?>
            </div>
        </div>

    <?php else : ?>

        <p class="woocommerce-verification-required alert alert-warning"><?php esc_html_e( 'Only logged in customers who have purchased this product may leave a review.', 'wpcom' ); ?></p>

    <?php endif; ?>

    <div class="clear"></div>
</div>
<?php
if(!is_user_logged_in()){
    $login_url = wp_login_url();
    $reg_url = wp_registration_url();
?>
    <div class="modal" id="login-modal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title">请登录</h4>
                </div>
                <div class="modal-body login-modal-body">
                    <p>您还未登录，请登录后再进行相关操作！</p>
                    <div class="login-btn">
                        <a class="btn btn-login" href="<?php echo $login_url;?>">登 录</a>
                        <a class="btn btn-register" href="<?php echo $reg_url;?>">注 册</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php }