<?php
defined( 'ABSPATH' ) || exit;
$can_edit = false;
if( get_current_user_id() == $profile->ID || current_user_can( 'edit_users' ) ) $can_edit = true;
?>

<div class="wpcom-profile">
    <div <?php echo wpcom_lazybg(wpcom_get_cover_url($profile->ID), 'wpcom-profile-head');?>>
        <div class="wpcom-ph-user">
            <div class="wpcom-ph-avatar">
                <?php echo get_avatar( $profile->ID, 200 );?>
                <?php if( $can_edit ){ ?><i class="fa fa-camera edit-avatar" data-user="<?php echo $profile->ID;?>"></i><?php } ?>
            </div>
            <h2 class="wpcom-ph-name"><?php echo $profile->display_name;?><?php $group = wpcom_get_user_group($profile->ID); if ( $group ) echo '<span class="author-group">'.$group->name.'</span>';?></h2>
            <?php if($profile->description){ ?><div class="wpcom-ph-desc"><?php echo $profile->description;?></div><?php } ?>
        </div>
        <?php if( $can_edit ){ ?>
            <div class="wpcom-profile-action">
                <span class="wpcom-profile-setcover edit-cover" data-user="<?php echo $profile->ID;?>">更换封面</span>
                <?php if($can_edit) wp_nonce_field( 'wpcom_cropper', 'wpcom_cropper_nonce', 0 );?>
            </div>
        <?php } ?>
    </div>
    <?php if($tabs){ ?>
        <ul class="wpcom-profile-nav">
            <?php $default = current($tabs); foreach ( $tabs as $tab ) {
                $tab_url = wpcom_profile_url( $profile, $tab['slug']==$default['slug']?'':$tab['slug'] );
                $tab_html = '<a href="' . $tab_url . '">'.$tab['title'].'</a>'; ?>
                <li<?php echo $tab['slug']==$subpage?' class="active"':'';?>>
                    <?php echo apply_filters( 'wpcom_profile_tab_url', $tab_html, $tab, $tab_url );?>
                </li>
            <?php } ?>
        </ul>
    <?php } ?>
    <div class="wpcom-profile-main profile-<?php echo $subpage;?>">
        <?php do_action( 'wpcom_profile_tabs_' . $subpage );?>
    </div>
</div>