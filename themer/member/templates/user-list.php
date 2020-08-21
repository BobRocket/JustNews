<?php defined( 'ABSPATH' ) || exit;?>
<ul class="wpcom-user-list user-cols-<?php echo $cols;?>">
    <?php foreach ( $users as $user ){
        $cover_photo = wpcom_get_cover_url( $user->ID );
        $group = wpcom_get_user_group( $user->ID ); ?>
        <li class="wpcom-user-item">
            <div class="wpcom-user-cover"><?php echo wpcom_lazyimg($cover_photo, $user->display_name);?></div>
            <div class="wpcom-user-avatar">
                <a class="avatar-link" href="<?php echo get_author_posts_url( $user->ID );?>" target="_blank">
                    <?php echo get_avatar( $user->ID, 120 );?>
                </a>
            </div>
            <div class="wpcom-user-name">
                <p>
                    <a href="<?php echo get_author_posts_url( $user->ID );?>" target="_blank"><?php echo $user->display_name;?></a>
                    <?php if($group){ ?><span class="wpcom-user-group"><?php echo $group->name;?></span><?php } ?>
                </p>
            </div>
        </li>
    <?php } ?>
</ul>