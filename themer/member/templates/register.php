<?php
defined( 'ABSPATH' ) || exit;

global $options;
$social_login_on = 0;
$classes = 'member-form-wrap';
if( isset($options['social_login_on']) && $options['social_login_on']=='1' ) {
    $social_login_on = 1;
    $classes .= ' member-form-full';
}
if( isset($options['member_login_bg']) && $options['member_login_bg'] !='' ) {
    $classes .= ' member-form-boxed';
} ?>
<div class="<?php echo $classes;?>">
    <div class="member-form-inner">
        <?php if ( !get_option('users_can_register') ) { ?>
        <div class="alert alert-warning text-center"><?php _e('User registration is currently not allowed.', 'wpcom');?></div>
        <?php } ?>
        <div class="member-form-head clearfix">
            <h3 class="member-form-title"><?php _e('Sign Up', 'wpcom');?>
            <span class="member-switch pull-right"><?php _e('Already have an account?', 'wpcom');?> <a href="<?php echo wp_login_url();?>"><?php echo _x('Sign in', 'sign', 'wpcom');?></a></span>
            </h3>
        </div>
        <?php do_action( 'wpcom_register_form' ); ?>
    </div>
    <?php if( $social_login_on ){ ?>
        <div class="member-form-social">
            <div class="member-form-head clearfix">
                <h3 class="member-form-title"><?php _e('Sign up with', 'wpcom');?></h3>
            </div>
            <?php do_action( 'wpcom_social_login' );?>
        </div>
    <?php } ?>
</div>
