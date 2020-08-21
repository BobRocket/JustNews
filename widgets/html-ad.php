<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_html_ad_widget extends WPCOM_Widget {
    public function __construct() {
        $this->widget_cssclass = 'widget_html_ad';
        $this->widget_description = '适合添加html广告代码，无边框';
        $this->widget_id = 'html-ad';
        $this->widget_name = '#广告代码';
        $this->settings = array(
            'html'       => array(
                'type'  => 'textarea',
                'std'   => '',
                'label' => '代码',
            )
        );
        parent::__construct();
    }

    public function widget( $args, $instance ) {
        if ( $this->get_cached_widget( $args ) ) return;
        ob_start();
        $html  = empty( $instance['html'] ) ? '' : $instance['html'];
        $this->widget_start( $args, $instance );
        echo do_shortcode($html);
        $this->widget_end( $args );
        echo $this->cache_widget( $args, ob_get_clean() );
    }
}

// register widget
function wpcom_html_ad_widget() {
    register_widget( 'WPCOM_html_ad_widget' );
}
add_action( 'widgets_init', 'wpcom_html_ad_widget' );