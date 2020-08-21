<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_comments_widget extends WPCOM_Widget{
    public function __construct(){
        $this->widget_cssclass = 'widget_comments';
        $this->widget_description = '显示网站最新的评论列表';
        $this->widget_id = 'comments';
        $this->widget_name = '#最新评论';
        $this->settings = array(
            'title'       => array(
                'type'  => 'text',
                'std'   => '',
                'label' => '标题',
            ),
            'number'      => array(
                'type'  => 'number',
                'step'  => 1,
                'min'   => 1,
                'max'   => '',
                'std'   => 10,
                'label' => '显示数量',
            )
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $number = !empty( $instance['number'] ) ? absint( $instance['number'] ) : $this->settings['number']['std'];
        $comments_query = new WP_Comment_Query();
        $comments = $comments_query->query( array( 'post_status' => 'publish', 'number' => $number ) );
        $this->widget_start( $args, $instance );

        if ( $comments ) : ?>
            <ul>
                <?php foreach ( $comments as $comment ) :
                    if($comment->user_id){
                        $author_url = get_author_posts_url( $comment->user_id );
                        $userdata = get_userdata( $comment->user_id );
                        $display_name = $userdata->display_name;
                    }else{
                        $author_url = 'javascript:;';
                        $display_name = $comment->comment_author;
                    }
                    ?>
                    <li>
                        <div class="comment-info">
                            <a href="<?php echo $author_url;?>" target="_blank">
                                <?php echo get_avatar( $comment, 60, '', $display_name?$display_name:'匿名' );?>
                            </a>
                            <a class="comment-author" href="<?php echo $author_url;?>" target="_blank">
                                <?php echo $display_name?$display_name:'匿名';?>
                            </a>
                            <span><?php echo date('n月j日',strtotime($comment->comment_date)); ?></span>
                        </div>
                        <div class="comment-excerpt">
                            <p><?php echo utf8_excerpt($comment->comment_content, 55);?></p>
                        </div>
                        <p class="comment-post">
                            评论于 <a href="<?php echo get_permalink($comment->comment_post_ID); ?>" target="_blank"><?php echo get_the_title($comment->comment_post_ID);?></a>
                        </p>
                    </li>
                <?php endforeach;?>
            </ul>
        <?php
        else:
            echo '<p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;">暂无评论</p>';
        endif;
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function register_wpcom_comments_widget() {
    register_widget( 'WPCOM_comments_widget' );
}
add_action( 'widgets_init', 'register_wpcom_comments_widget' );