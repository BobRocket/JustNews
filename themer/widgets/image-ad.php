<?php
defined( 'ABSPATH' ) || exit;

if( !class_exists('WPCOM_image_ad_widget') ) :
class WPCOM_image_ad_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_image_ad';
        $this->widget_description = '可以添加图片链接广告';
        $this->widget_id = 'image-ad';
        $this->widget_name = '#图片广告';
        $this->settings = array(
            'title'       => array(
                'type'  => 'text',
                'std'   => '',
                'label' => '标题',
            ),
            'image'      => array(
                'type'  => 'upload',
                'std'   => '',
                'label' => '图片',
            ),
            'url'      => array(
                'type'  => 'text',
                'std'   => '',
                'label' => '链接',
            ),
            'target'      => array(
                'type'  => 'checkbox',
                'std'   => '',
                'label' => '新窗口打开',
            ),
            'nofollow'      => array(
                'type'  => 'checkbox',
                'std'   => '',
                'label' => 'Nofollow',
            )
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $title  = empty( $instance['title'] ) ? '' : esc_attr( $instance['title'] );
        $image = empty( $instance['image'] ) ? '' : esc_url( $instance['image'] );
        $url = empty( $instance['url'] ) ? '' :  esc_url( $instance['url'] );
        $target = $instance['target'] ? ' target="_blank"' : '';
        $nofollow = $instance['nofollow'] ? ' rel="nofollow"' : '';
        echo $args['before_widget'];
        if($url){ ?>
            <a href="<?php echo $url;?>"<?php echo $target.$nofollow;?>>
                <?php echo wpcom_lazyimg($image, $title);?>
            </a>
        <?php } else { ?>
            <?php echo wpcom_lazyimg($image, $title);?>
        <?php }

        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function wpcom_image_ad_widget() {
    register_widget( 'WPCOM_image_ad_widget' );
}
add_action( 'widgets_init', 'wpcom_image_ad_widget' );
endif;