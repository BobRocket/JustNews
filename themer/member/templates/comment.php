<?php defined( 'ABSPATH' ) || exit;?>
<div class="comment-item">
    <div class="comment-item-link">
        <a target="_blank" href="<?php echo get_comment_link( $comment->comment_ID ); ?>">
            <i class="fa fa-comments"></i> <?php echo get_comment_excerpt( $comment->comment_ID ); ?>
        </a>
    </div>
    <div class="comment-item-meta">
        <?php
        $time = strtotime($comment->comment_date_gmt);
        $t = time() - $time;
        $f = array( '86400', '3600', '60', '1' );
        $human_time = '';
        if($t==0) {
            $human_time = __('1 second ago', 'wpcom');
        } else if( $t >= 86400 || $t < 0) {
            $human_time = date(get_option('date_format'), strtotime($comment->comment_date));
        } else {
            foreach ( $f as $k ) {
                if ( 0 != $c=floor($t/(int)$k) ) {
                    $is_human_time = true;
                    break;
                }
            }

            if($is_human_time) {
                $strs = array(
                    '86400' => sprintf(_n('%s day ago', '%s days ago', $c, 'wpcom'), $c),
                    '3600' => sprintf(_n('%s hour ago', '%s hours ago', $c, 'wpcom'), $c),
                    '60' => sprintf(_n('%s minute ago', '%s minutes ago', $c, 'wpcom'), $c),
                    '1' => sprintf(_n('%s second ago', '%s seconds ago', $c, 'wpcom'), $c)
                );
                $human_time = $strs[$k];
            }
        }
        ?>
        <span><?php echo $human_time;?> <?php printf(__('On <a target="_blank" href="%1$s">%2$s</a>', 'wpcom'), get_permalink($comment->comment_post_ID), get_the_title($comment->comment_post_ID) ); ?></span>
    </div>
</div>
