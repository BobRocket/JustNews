<?php defined( 'ABSPATH' ) || exit;?>
<div class="member-account-wrap">
    <div class="member-account-nav">
        <div class="member-account-user">
            <div class="member-account-avatar">
                <?php echo get_avatar( $user->ID, 200 );?>
                <i class="fa fa-camera edit-avatar" data-user="<?php echo $user->ID;?>"></i>
                <?php wp_nonce_field( 'wpcom_cropper', 'wpcom_cropper_nonce', 0 );?>
            </div>
            <?php $show_profile = apply_filters( 'wpcom_member_show_profile' , true );?>
            <h3 class="member-account-name">
                <?php if($show_profile){?><a href="<?php echo get_author_posts_url($user->ID); ?>" target="_blank"><?php echo $user->display_name;?></a>
                <?php }else { echo $user->display_name; } ?>
            </h3>
        </div>
        <ul>
            <?php $current_tab = null;
            foreach ($tabs as $t){
                if( $t['slug'] == $subpage && isset($t['parent']) && $t['parent'] ) {
                    $current_tab = $t;
                    $current_tab['slug'] = $t['parent'];
                }
            }
            foreach ( $tabs as $i => $tab ) { if( $i<999 ) {
                if( !$current_tab && $tab['slug'] == $subpage ) $current_tab = $tab; ?>
                <li class="member-nav-<?php echo $tab['slug']; if( $tab['slug']==$current_tab['slug'] ) echo ' active';?>">
                    <a href="<?php echo wpcom_subpage_url($tab['slug'])?>"><i class="fa fa-<?php echo $tab['icon']?> fa-icon"></i> <?php echo $tab['title']?> <i class="fa fa-angle-right pull-right"></i></a>
                </li>
            <?php } } ?>
        </ul>
    </div>
    <div class="member-account-content">
        <h2 class="member-account-title"><?php echo $current_tab['title'];?></h2>
        <?php if( isset($GLOBALS['validation']) && empty( $GLOBALS['validation']['error'] ) ) { ?>
        <div class="alert alert-success" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <?php _e( 'Updated successfully.', 'wpcom' ); ?>
        </div>
        <?php } ?>
        <?php do_action( 'wpcom_account_tabs_' . $subpage ); ?>
    </div>
</div>
