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
}
?>
<div class="<?php echo $classes;?>">
    <div class="member-form-inner">
        <div class="member-form-head clearfix">
            <h3 class="member-form-title"><?php _e('Sign In', 'wpcom');?>
            <span class="member-switch pull-right"><?php _e('No account?', 'wpcom');?> <a href="<?php echo wp_registration_url();?>"><?php _e('Create one!', 'wpcom');?></a></span></h3>
        </div>
        <?php do_action( 'wpcom_login_form' ); ?>
    </div>
    <?php if( $social_login_on ){ ?>
        <div class="member-form-social">
            <div class="member-form-head clearfix">
                <h3 class="member-form-title"><?php _e('Sign in with', 'wpcom');?></h3>
            </div>
            <?php do_action( 'wpcom_social_login' );?>
        </div>
    <?php } ?>
</div>
