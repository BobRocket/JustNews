<?php
defined( 'ABSPATH' ) || exit;

global $options;
$classes = 'member-form-wrap member-form-full member-reg-notice';
if( isset($options['member_login_bg']) && $options['member_login_bg'] !='' ) {
    $classes .= ' member-form-boxed';
} ?>
<div class="<?php echo $classes;?>">
    <?php echo wpautop($notice);?>
</div>
