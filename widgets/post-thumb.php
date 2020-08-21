<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_post_thumb_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_post_thumb';
        $this->widget_description = '带缩略图的文章列表';
        $this->widget_id = 'post-thumb';
        $this->widget_name = '#图文列表';
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
            ),
            'category'    => array(
                'type'  => 'select',
                'std'   => '0',
                'label' => '分类',
                'options' => array('0'=>'全部分类') + WPCOM::category()
            ),
            'orderby'    => array(
                'type'  => 'select',
                'std'   => '0',
                'label' => '排序',
                'options' => array(
                    '0' => '发布时间',
                    '1' => '评论数',
                    '2' => '浏览数(需安装WP-PostViews插件)',
                    '3' => '随机排序'
                )
            )
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();

        $category = $instance['category'];
        $orderby_id = empty( $instance['orderby'] ) ? $this->settings['orderby']['std'] :  $instance['orderby'];
        $number = empty( $instance['number'] ) ? $this->settings['number']['std'] : absint( $instance['number'] );

        $orderby = 'date';
        if($orderby_id==1){
            $orderby = 'comment_count';
        }else if($orderby_id==2){
            $orderby = 'meta_value_num';
        }else if($orderby_id==3){
            $orderby = 'rand';
        }

        $parg = array(
            'cat' => $category,
            'showposts' => $number,
            'orderby' => $orderby,
            'ignore_sticky_posts' => 1
        );
        if($orderby=='meta_value_num') $parg['meta_key'] = 'views';

        $posts = new WP_Query( $parg );

        $this->widget_start( $args, $instance );

        if ( $posts->have_posts() ) : global $post;?>
            <ul>
                <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
                    <li class="item">
                        <?php $has_thumb = get_the_post_thumbnail(); if($has_thumb){
                            $video = get_post_meta( $post->ID, 'wpcom_video', true );?>
                            <div class="item-img<?php echo $video?' item-video':'';?>">
                                <a class="item-img-inner" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>">
                                    <?php the_post_thumbnail(); ?>
                                </a>
                            </div>
                        <?php } ?>
                        <div class="item-content"<?php echo ($has_thumb?'':' style="margin-left: 0;"');?>>
                            <p class="item-title"><a href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>"><?php the_title();?></a></p>
                            <p class="item-date"><?php the_time(get_option('date_format'));?></p>
                        </div>
                    </li>
                <?php endwhile; wp_reset_postdata();?>
            </ul>
        <?php
        else:
            echo '<p style="color:#999;font-size: 12px;text-align: center;padding: 10px 0;margin:0;">暂无内容</p>';
        endif;
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function register_wpcom_post_thumb_widget() {
    register_widget( 'WPCOM_post_thumb_widget' );
}
add_action( 'widgets_init', 'register_wpcom_post_thumb_widget' );