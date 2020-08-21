<?php
defined( 'ABSPATH' ) || exit;

global $options;
$classes = 'member-form-wrap';
if( isset($options['member_login_bg']) && $options['member_login_bg'] !='' ) {
    $classes .= ' member-form-boxed';
} ?>
<div class="<?php echo $classes;?>">
    <div class="member-form-inner">
        <?php do_action( 'wpcom_approve_resend_form' );?>
    </div>
</div>

