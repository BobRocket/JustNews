<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_kuaixun_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_kuaixun';
        $this->widget_description = '快讯展示';
        $this->widget_id = 'kuaixun';
        $this->widget_name = '#快讯';
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
        global $options;
        $num = empty( $instance['number'] ) ? $this->settings['number']['std'] : absint( $instance['number'] );

        echo $args['before_widget'];
        $url = '';
        if( isset($options['kx_page']) && $options['kx_page'] && $kx = get_post($options['kx_page']) )
            $url = get_permalink($kx->ID);

        if ( ! empty( $instance['title'] ) ) {
            if($url){
                $url = '<a class="widget-title-more" href="'.$url.'" target="_blank">更多 &raquo;</a>';
            }
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $url . $args['after_title'];
        }

        $arg = array(
            'posts_per_page' => $num,
            'post_status' => array( 'publish' ),
            'post_type' => 'kuaixun'
        );
        $posts = new WP_Query($arg);
        global $post;
        if( $posts->have_posts() ) { ?>
            <ul class="widget-kx-list">
            <?php  while ( $posts->have_posts() ) { $posts->the_post(); ?>
                <li class="kx-item" data-id="<?php the_ID();?>">
                    <a class="kx-title" href="javascript:;"><?php the_title();?></a>
                    <div class="kx-content">
                        <?php the_excerpt();?>
                        <?php if(get_the_post_thumbnail()){ ?>
                            <?php the_post_thumbnail(); ?>
                        <?php } ?>
                    </div>
                    <div class="kx-meta clearfix">
                        <span class="kx-time"><?php echo format_date(get_post_time( 'U', false, $post ));?></span>
                        <div class="kx-share" data-url="<?php echo urlencode(get_permalink());?>">
                            <span>分享到</span>
                            <span class="share-icon wechat">
                                <i class="fa fa-wechat"></i>
                                <span class="wechat-img">
                                    <span class="j-qrcode" data-text="<?php the_permalink();?>"></span>
                                </span>
                            </span>
                            <span class="share-icon weibo" href="javascript:;"><i class="fa fa-weibo"></i></span>
                            <span class="share-icon qq" href="javascript:;"><i class="fa fa-qq"></i></span>
                            <span class="share-icon copy"><i class="fa fa-file-text"></i></span>
                        </div>
                    </div>
                </li>
            <?php }
            echo '</ul>';
        }
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function register_wpcom_kuaixun_widget() {
    register_widget( 'WPCOM_kuaixun_widget' );
}
add_action( 'widgets_init', 'register_wpcom_kuaixun_widget' );