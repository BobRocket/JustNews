<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_products_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_lastest_products';
        $this->widget_description = '适合用于显示图文列表信息，每行两张图片显示';
        $this->widget_id = 'lastest-products';
        $this->widget_name = '#图文列表（两栏）';
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
            <ul class="p-list clearfix">
                <?php while ( $posts->have_posts() ) : $posts->the_post();
                    $video = get_post_meta( $post->ID, 'wpcom_video', true );?>
                    <li class="col-xs-12 col-md-6 p-item">
                        <div class="p-item-wrap">
                            <a class="thumb<?php echo $video?' thumb-video':'';?>" href="<?php echo esc_url( get_permalink() )?>">
                                <?php the_post_thumbnail();?>
                            </a>
                            <h4 class="title">
                                <a href="<?php echo esc_url( get_permalink() )?>" title="<?php echo esc_attr(get_the_title());?>">
                                    <?php the_title();?>
                                </a>
                            </h4>
                        </div>
                    </li>
                <?php endwhile; wp_reset_postdata();?>
            </ul>
        <?php
        endif;

        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function wpcom_products_widget() {
    register_widget( 'WPCOM_products_widget' );
}
add_action( 'widgets_init', 'wpcom_products_widget' );