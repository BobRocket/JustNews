<?php
defined( 'ABSPATH' ) || exit;

// sidebar
add_action( 'widgets_init', 'wpcom_sidebar_init' );
if ( ! function_exists( 'wpcom_sidebar_init' ) ) :
    function wpcom_sidebar_init() {
        global $options;
        $sidebar = array('primary' => '默认边栏');
        if(isset($options['sidebar_id']) && $options['sidebar_id']) {
            foreach ($options['sidebar_id'] as $i => $id) {
                if($id && $options['sidebar_name'][$i]) {
                    $sidebar[$id] = $options['sidebar_name'][$i];
                }
            }
        }

        $sidebar = apply_filters( 'wpcom_sidebars', $sidebar );

        foreach($sidebar as $k=>$v){
            if( $k ) {
                register_sidebar(array(
                    'name' => $v,
                    'id' => $k,
                    'before_widget' => '<div id="%1$s" class="widget %2$s">',
                    'after_widget' => '</div>',
                    'before_title' => '<h3 class="widget-title"><span>',
                    'after_title' => '</span></h3>',
                ));
            }
        }
        do_action('wpcom_sidebar');
    }
endif;

add_filter('wpcom_tax_metas', 'wpcom_tax_sidebar_meta');
function wpcom_tax_sidebar_meta( $metas ){
    global $options;
    $sidebar = array('' => ' 默认边栏');

    if(isset($options['sidebar_id']) && $options['sidebar_id']) {
        foreach ($options['sidebar_id'] as $i => $id) {
            if($id && $options['sidebar_name'][$i]) {
                $sidebar[$id] = $options['sidebar_name'][$i];
            }
        }
    }

    $sidebar = apply_filters( 'wpcom_sidebars', $sidebar);

    $exclude_taxonomies = array('nav_menu', 'link_category', 'post_format', 'user-groups');
    $taxonomies = get_taxonomies();
    foreach ($taxonomies as $key => $taxonomy) {
        if( ! in_array( $key , $exclude_taxonomies ) ){
            $metas[$key] = isset($metas[$key]) && is_array($metas[$key]) ? $metas[$key] : array();
            $metas[$key][] = array(
                'title' => '显示边栏',
                'type' => 'select',
                'options' => $sidebar,
                'name' => 'sidebar',
                'desc' => '如果有边栏，则显示所选择的边栏'
            );
        }
    }
    return $metas;
}