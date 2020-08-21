<?php
defined( 'ABSPATH' ) || exit;

add_action('after_setup_theme', 'wpcom_demo_importer');
function wpcom_demo_importer(){
    new WPCOM_DEMO_Importer();
}

class WPCOM_DEMO_Importer{
    public function __construct(){
        $this->config = array();
        if(is_admin() && current_user_can( 'edit_theme_options' ) && version_compare( phpversion(), '5.3.2', '>=' )){
            global $wpcom_panel;
            $this->config = $wpcom_panel->get_demo_config();
            if(!empty($this->config)){
                $this->config = json_decode(json_encode($this->config),true);
                if(!class_exists('OCDI_Plugin')) require FRAMEWORK_PATH . '/importer/one-click-demo-import.php';
                add_filter( 'pt-ocdi/import_files', array($this, 'import_files') );
                add_action( 'pt-ocdi/after_import', array($this, 'after_import') );
            }
        }
    }

    public function import_files(){
        return $this->config;
    }

    public function after_import( $selected_import ) {
        global $wp_version, $wpdb;
        $theme_options = '';
        $args = array('timeout' => 20, 'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url());
        $result = @wp_remote_get($selected_import['import_options_file_url'], $args);
        if(is_array($result)){
            $theme_options = $result['body'];
        }


        $options = json_decode($theme_options, true);
        if($options && isset($options['options'])) {
            $izt_theme_options = $this->replace_options_images( $options['options'] );
            update_option('izt_theme_options', $izt_theme_options);
        }

        // Set homepage
        if($options['show_on_front']=='page' && $options['page_on_front']){
            $page = get_page_by_path( $options['page_on_front'] );
            if( $page && isset($page->ID) ){
                update_option( 'page_on_front', $page->ID );
                update_option( 'show_on_front', 'page' );
            }
        }else{
            update_option( 'show_on_front', 'posts' );
        }

        // menu
        $menus = $options && isset($options['menu']) ? $options['menu'] : array();

        // Get current locations
        $locations = get_theme_mod( 'nav_menu_locations' );

        // Add demo locations
        foreach ( $menus as $location => $name ) {
            $menu                 = get_term_by( 'slug', $name, 'nav_menu');
            $locations[$location] = $menu->term_id;
        }

        // Set menu locations
        set_theme_mod( 'nav_menu_locations', $locations );

        // Set widgets
        $widgets = $options && isset($options['widgets']) ? $options['widgets'] : array(); // 获取导入的小工具数据
        $sidebars = wp_get_sidebars_widgets(); // 获取边栏数据
        $widgets_options = array(); // 保存导入的小工具信息
        foreach($widgets as $k => $wgt){
            if(!empty($wgt)){
                $sItem = array();
                foreach($wgt as $i=>$v){
                    $sItem[] = $i;
                    preg_match('/(.*)-(\d+)$/i', $i, $matches);
                    if(!isset($widgets_options[$matches[1]])) $widgets_options[$matches[1]] = get_option('widget_'.$matches[1]);
                    $v = $this->replace_options_images( $v );
                    $widgets_options[$matches[1]][$matches[2]] = $v;
                    if($matches[1]=='nav_menu'){
                        $mSlug = $v['nav_menu'];
                        if($term2 = get_term_by('slug', $mSlug, 'nav_menu')){
                            $widgets_options[$matches[1]][$matches[2]]['nav_menu'] = $term2->term_id;
                        }
                    }
                }
                $sidebars[$k] = $sItem;
            }else{
                $sidebars[$k] = array();
            }
        }
        wp_set_sidebars_widgets($sidebars);

        foreach($widgets_options as $k => $wops){
            update_option( 'widget_'.$k, $wops );
        }

        // replace images url in taxonomy metas
        $table = $wpdb->prefix.'termmeta';
        $_wpcom_metas = $wpdb->get_results("SELECT * FROM `$table` WHERE meta_key = '_wpcom_metas'");
        if ($_wpcom_metas) {
            foreach ($_wpcom_metas as $metas) {
                $meta_value = maybe_unserialize($metas->meta_value);
                $meta_value = $this->replace_options_images( $meta_value );

                $where = array( 'meta_id' => $metas->meta_id, 'meta_key' => '_wpcom_metas' );
                $wpdb->update( $table, array('meta_value' => maybe_serialize($meta_value)), $where );
            }
        }

        // replace images in customize page
        $_page_modules = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_page_modules'");
        if($_page_modules) {
            foreach ($_page_modules as $modules) {
                $meta_value = maybe_unserialize($modules->meta_value);
                if($meta_value){
                    foreach ($meta_value as $i => $meta){
                        if(isset($meta['settings']) && $meta['settings'])
                            $meta['settings'] = $this->replace_options_images( $meta['settings'] );
                        $meta_value[$i] = $meta;
                    }
                }
                update_post_meta($modules->post_id, '_page_modules', $meta_value);
            }
        }
    }

    function replace_options_images( $options ){
        $new_options = array();
        if( !$options || ( !is_array($options) && !is_object($options) ) ) return $options;
        foreach ( $options as $k => $ops ){
            if( is_array($ops) || is_object($ops) ){
                $ops = $this->replace_options_images($ops);
            }else{
                $imgs = $this->get_wpcom_img($ops);
                $search = array();
                $replace = array();
                if($imgs){
                    foreach ( $imgs as $img){
                        $get_img = $this->get_file_by_url( $img );
                        if( $get_img && isset($get_img['id']) && isset($get_img['url']) ){
                            array_push($search, $img);
                            array_push($replace, $get_img['url']);
                        }
                    }
                    $ops = str_replace($search, $replace, $ops);
                }
            }
            $new_options[$k] = $ops;
        }
        return $new_options;
    }

    private function get_file_by_url( $url ){
        global $wpdb;
        $file = array();
        preg_match( '/\/([0-9]{4}\/[0-9]{2}\/[^\/\s]+\.(jpg|png|gif|jpeg|webp))/i', $url, $matches );
        if( isset($matches[1]) && $matches[1] ){
            $upload_info = wp_upload_dir();
            $name = $matches[1];

            $postmeta_table = $wpdb->prefix.'postmeta';
            $file_id = $wpdb->get_var("SELECT post_id FROM `$postmeta_table` WHERE meta_key = '_wp_attached_file' AND meta_value = '$name'");

            if( $file_id ) {
                $file['id'] = $file_id;
                $file['url'] = $upload_info['baseurl'] . '/' . $name;
            }else{
                $img = WPCOM::save_remote_img($url);
                if ( is_array($img) && isset($img['id']) ) {
                    $file = $img;
                }
            }
        }

        return $file;
    }

    private function get_wpcom_img( $str ){
        preg_match_all( '/(http:\/\/|https:\/\/|\/\/)[^\.]*\.wpcom\.cn\/[^\.]+\/[0-9]{4}\/[0-9]{2}\/[^\/\s\'"]+/i', $str, $matches );
        if( isset($matches[0]) && $matches[0] ) return $matches[0];
    }
}