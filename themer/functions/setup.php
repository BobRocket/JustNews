<?php
defined( 'ABSPATH' ) || exit;

// wpcom setup
add_action( 'after_setup_theme', 'wpcom_setup' );
if ( ! function_exists( 'wpcom_setup' ) ) :
    function wpcom_setup() {
        global $options;
        /**
         * Add text domain
         */
        load_theme_textdomain('wpcom', get_template_directory() . '/lang');
        if( is_child_theme() ) load_theme_textdomain('wpcom', get_stylesheet_directory() . '/lang');

        add_theme_support( 'woocommerce', array(
            'thumbnail_image_width' => 480,
            'single_image_width' => 800
        ) );

        add_theme_support( 'html5', array(
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
        ) );

        // gutenberg 兼容
        if( function_exists('gutenberg_init') ) {
            add_theme_support( 'wp-block-styles' );
            if( isset($options['theme_color']) && $options['theme_color']  && $options['theme_color_hover'] ) {
                add_theme_support('editor-color-palette', array(
                    array(
                        'name' => '主题默认颜色',
                        'slug' => 'theme-color',
                        'color' => $options['theme_color']
                    ),
                    array(
                        'name' => '主题悬停颜色',
                        'slug' => 'theme-hover',
                        'color' => $options['theme_color_hover']
                    ),
                    array(
                        'name' => '黑色',
                        'slug' => 'theme-black',
                        'color' => '#333'
                    ),
                    array(
                        'name' => '灰色',
                        'slug' => 'theme-gray',
                        'color' => '#999'
                    ),
                    array(
                        'name' => '淡背景色',
                        'slug' => 'theme-light',
                        'color' => '#f5f5f5'
                    )
                ));
            }
        }

        // 缩略图设置
        add_theme_support( 'post-thumbnails' );
        $sizes = apply_filters('wpcom_image_sizes', array());
        if( isset($sizes['post-thumbnail']) && $sizes['post-thumbnail'] )
            set_post_thumbnail_size( $sizes['post-thumbnail']['width'], $sizes['post-thumbnail']['height'], true );

        // 允许添加友情链接
        add_filter( 'pre_option_link_manager_enabled', '__return_true' );

        // This theme uses wp_nav_menu() in two locations.
        register_nav_menus( apply_filters( 'wpcom_menus', array() ));

        if(isset($options['wx_appid']) && $options['wx_appid'] && $options['wx_appsecret']) new WX_share();

        if( isset($options['member_enable']) && $options['member_enable']=='1' ) {
            include_once FRAMEWORK_PATH . '/member/init.php';
        }

        remove_action( 'wp_head', 'rel_canonical' );
        remove_action( 'wp_head', 'wp_generator' );
        add_filter( 'revslider_meta_generator', '__return_false' );
        add_filter( 'wp_calculate_image_srcset', '__return_false', 99999 );

        if( !isset($options['disable_rest']) || (isset($options['disable_rest']) && $options['disable_rest']=='1')) {
            remove_action('wp_head', 'rest_output_link_wp_head', 10);
            remove_action('wp_head', 'wp_oembed_add_discovery_links', 10);
        }

        if( !isset($options['disable_emoji']) || (isset($options['disable_emoji']) && $options['disable_emoji']=='1')) {
            remove_action('wp_head', 'print_emoji_detection_script', 7);
            remove_action('admin_print_scripts', 'print_emoji_detection_script');
            remove_action('wp_print_styles', 'print_emoji_styles');
            remove_action('admin_print_styles', 'print_emoji_styles');
            remove_filter('the_content_feed', 'wp_staticize_emoji');
            remove_filter('comment_text_rss', 'wp_staticize_emoji');
            remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
            add_filter( 'tiny_mce_plugins', 'wpcom_disable_emojis_tinymce' );
            add_filter( 'emoji_svg_url', '__return_false' );
        }
    }
endif;

add_action( 'admin_init', 'wpcom_admin_setup' );
function wpcom_admin_setup() {
    global $pagenow;
    if( $pagenow == 'post.php' || $pagenow == 'post-new.php' || $pagenow == 'admin-ajax.php' ) new WPCOM_Shortcodes();
    if( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
        if( file_exists( get_template_directory() . '/css/editor-style.css' ) )
            add_editor_style( 'css/editor-style.css' );
    }
    new WPCOM_Meta();
}

add_filter( 'wpcom_image_sizes', 'wpcom_image_sizes' );
function wpcom_image_sizes($image_sizes){
    global $options, $_wp_additional_image_sizes;

    if( !empty($_wp_additional_image_sizes) ) {
        foreach ($_wp_additional_image_sizes as $sk => $size) {
            if( $sk =='shop_single' || $sk =='woocommerce_single' ) $size['crop'] = 1;
            if (isset($size['crop']) && $size['crop'] == 1) {
                $image_sizes[$sk] = $size;
            }
        }
    }
    $image_sizes['post-thumbnail'] = array(
        'width' => intval(isset($options['thumb_width']) && $options['thumb_width'] ? $options['thumb_width'] : 480),
        'height' => intval(isset($options['thumb_height']) && $options['thumb_height'] ? $options['thumb_height'] : 320)
    );
    $image_sizes['default'] = array(
        'width' => intval(isset($options['thumb_default_width']) && $options['thumb_default_width'] ? $options['thumb_default_width'] : 480),
        'height' => intval(isset($options['thumb_default_height']) && $options['thumb_default_height'] ? $options['thumb_default_height'] : 320)
    );
    return $image_sizes;
}

// wp title
add_filter( 'wp_title_parts', 'wpcom_title_parts', 20 );
if ( ! function_exists( 'wpcom_title' ) ) :
    function wpcom_title_parts( $parts ){
        global $post, $options, $wp_title_parts;
        if( !isset($options['seo']) || $options['seo']=='1' ) {
            if ( is_tax() && get_queried_object()) {
                $parts = array( single_term_title( '', false ) );
            }
            $title_array = array();
            foreach ( $parts as $t ){
                if(trim($t)) $title_array[] = $t;
            }
            if ( is_singular() ) {
                $seo_title = trim(strip_tags(get_post_meta($post->ID, 'wpcom_seo_title', true)));
                if ($seo_title != '') $title_array[0] = $seo_title;
            } else if ( is_category() || is_tag() || is_tax() ) {
                $term = get_queried_object();
                $seo_title = get_term_meta($term->term_id, 'wpcom_seo_title', true);
                $seo_title = $seo_title != '' ? $seo_title : '';
                if ($seo_title != '') $title_array[0] = $seo_title;
            }
            $wp_title_parts = $title_array;
        }else{
            $wp_title_parts = $parts;
        }

        return $wp_title_parts;
    }
endif;

add_filter( 'wp_title', 'wpcom_title', 10, 3 );
if ( ! function_exists( 'wpcom_title' ) ) :
    function wpcom_title( $title, $sep, $seplocation) {
        global $paged, $page, $options, $wp_title_parts;

        if( !isset($options['seo']) || $options['seo']=='1' ) {
            if ((is_home() || is_front_page()) && isset($options['home-title']) && $options['home-title']) {
                return $options['home-title'];
            }

            $prefix = !empty($title) ? $sep : '';
            $title = $seplocation == 'right' ? implode($sep, array_reverse($wp_title_parts)).$prefix : $prefix.implode($sep, $wp_title_parts);
        }

        // 首页标题
        if ( empty($title) && (is_home() || is_front_page()) ) {
            $desc = get_bloginfo('description');
            if ($desc) {
                $title = get_option('blogname') . (isset($options['title_sep_home']) && $options['title_sep_home'] ? $options['title_sep_home'] : $sep) . $desc;
            } else {
                $title = get_option('blogname');
            }
        } else {
            if ($paged >= 2 || $page >= 2) // 增加页数
                $title = $title . sprintf(__('Page %s', 'wpcom'), max($paged, $page)) . $sep;
            if ('right' == $seplocation) {
                $title = $title . get_option('blogname');
            } else {
                $title = get_option('blogname') . $title;
            }
        }
        return $title;
    }
endif;

// 加载静态资源
if ( ! function_exists( 'wpcom_scripts' ) ) :
    function wpcom_scripts() {
        global $options;
        // 载入主样式
        $css = is_child_theme() ? '/style.css' : '/css/style.css';
        wp_enqueue_style( 'stylesheet', get_stylesheet_directory_uri() . $css, array(), THEME_VERSION );

        // 替换自带的jQuery
        wp_deregister_script( 'jquery' );
        wp_enqueue_script('jquery', get_template_directory_uri() . '/js/jquery.min.js', false, '1.12.4');

        // 载入js文件
        wp_enqueue_script( 'main', get_template_directory_uri() . '/js/main.js', array( 'jquery' ), THEME_VERSION, true );

        if ( is_singular() && isset($options['comments_open']) && $options['comments_open']=='1' && comments_open() && get_option( 'thread_comments' )) {
            wp_enqueue_script( 'comment-reply' );
        }
    }
endif;
add_action( 'wp_enqueue_scripts', 'wpcom_scripts' );
/* 静态资源结束 */

add_action( 'wp_enqueue_scripts', 'wpcom_localize_script' );
function wpcom_localize_script() {
    global $options;
    $webp = isset($options['webp_suffix']) && $options['webp_suffix'] ? $options['webp_suffix'] : '';
    $script = array(
        'webp' => $webp,
        'ajaxurl' => admin_url( 'admin-ajax.php'),
        'slide_speed' => isset($options['slide_speed'])?$options['slide_speed']: ''
    );
    if( is_singular() && (!isset($options['post_img_lightbox']) || $options['post_img_lightbox']=='1') ) {
        $script['fluidbox'] = 1;
    }
    if( isset($options['xzh-appid']) && $options['xzh-appid'] && isset($options['xzh-render-head']) && $options['xzh-render-head'] ){
        $script['xzh_head'] = 1;
    }
    $wpcom_js = apply_filters('wpcom_localize_script', $script);
    wp_localize_script( 'main', '_wpcom_js', $wpcom_js );
}

// Excerpt more
add_filter('excerpt_more', 'wpcom_excerpt_more');
if ( ! function_exists( 'wpcom_excerpt_more' ) ) :
    function wpcom_excerpt_more( $more ) {
        return '...';
    }
endif;

if ( ! function_exists( 'wpcom_disable_emojis_tinymce' ) ) :
    function wpcom_disable_emojis_tinymce( $plugins ) {
        if ( is_array( $plugins ) ) {
            return array_diff( $plugins, array( 'wpemoji' ) );
        } else {
            return array();
        }
    }
endif;

if ( ! function_exists( 'utf8_excerpt' ) ) :
    function utf8_excerpt($str, $len){
        $str = strip_tags( str_replace( array( "\n", "\r" ), ' ', $str ) );
        if(function_exists('mb_substr')){
            $excerpt = mb_substr($str, 0, $len, 'utf-8');
        }else{
            preg_match_all("/[x01-x7f]|[xc2-xdf][x80-xbf]|xe0[xa0-xbf][x80-xbf]|[xe1-xef][x80-xbf][x80-xbf]|xf0[x90-xbf][x80-xbf][x80-xbf]|[xf1-xf7][x80-xbf][x80-xbf][x80-xbf]/", $str, $ar);
            $excerpt = join('', array_slice($ar[0], 0, $len));
        }

        if(trim($str)!=trim($excerpt)){
            $excerpt .= '...';
        }
        return $excerpt;
    }
endif;

// 百度熊掌号JSON_LD数据
add_action( 'wp_enqueue_scripts', 'wpcom_xzh_scripts', 9 );
function wpcom_xzh_scripts() {
    global $options;
    $appid = isset($options['xzh-appid']) ? trim($options['xzh-appid']) : '';
    if ( !$appid ) return;
    wp_enqueue_script( 'xzh', '//xiongzhang.baidu.com/sdk/c.js?appid='.$appid, array(), false, true );
}

add_action( 'wp_footer', 'wpcom_baidu_xzh', 50);
function wpcom_baidu_xzh(){
    global $options;
    $appid = isset($options['xzh-appid']) ? trim($options['xzh-appid']) : '';
    if ( !$appid ||  ! is_singular() || is_attachment() ) return; ?>

    <script type="application/ld+json">
        {
            "@context": "https://ziyuan.baidu.com/contexts/cambrian.jsonld",
            "@id": "<?php the_permalink();?>",
            "appid": "<?php echo $appid;?>",
            "title": "<?php the_title();?>",
            "images": <?php echo wpcom_bdxzh_imgs();?>,
            "description": "<?php echo utf8_excerpt(strip_tags(get_the_excerpt()), 120);?>",
            "pubDate": "<?php the_time('Y-m-d\TH:i:s');?>",
            "upDate": "<?php the_modified_time('Y-m-d\TH:i:s');?>"
        }
    </script>
<?php }

function wpcom_bdxzh_imgs(){
    global $post;
    $imgs = '[]';

    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches, PREG_PATTERN_ORDER);

    if(isset($matches[1]) && isset($matches[1][2])){ // 有3张图片
        for($i=0;$i<3;$i++){
            if(preg_match('/^\/\//i', $matches[1][$i])) $matches[1][$i] = 'http:' . $matches[1][$i];
        }
        $imgs = '["'.$matches[1][0].'","'.$matches[1][1].'","'.$matches[1][2].'"]';
    }else if($img_url = (isset($GLOBALS['post-thumb']) ? $GLOBALS['post-thumb'] : wpcom::thumbnail_url($post->ID)) ){
        if(preg_match('/^\/\//i', $img_url)) $img_url = 'http:' . $img_url;
        $imgs = '["'.$img_url.'"]';
    }
    return $imgs;
}

add_action( 'transition_post_status', 'wpcom_xzh_submit', 10, 3 );
function wpcom_xzh_submit( $new_status, $old_status, $post ){
    if( $new_status!='publish' || $new_status==$old_status || $post->post_type!='post' ) return false;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return false;
    //if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return false;

    global $options;
    if(isset($options['xzh-submit']) && $options['xzh-submit']) {
        wp_remote_post(trim($options['xzh-submit']), array(
                'method' => 'POST',
                'timeout' => 30,
                'headers' => array('Content-Type: text/plain'),
                'body' => get_permalink($post->ID)
            )
        );
    }
}

// 文章缩进
add_filter( 'wpcom_head', 'show_indent' );
function show_indent($echo){
    if(is_single()||is_page()){
        global $post, $options;
        $em = isset($options['show_indent'])?$options['show_indent']:get_post_meta($post->ID, 'wpcom_show_indent', true);
        if($em=='1'){
            $echo .= '<style>.entry-content p{text-indent: 2em;}</style>'."\n";
        }
    }
    return $echo;
}

// wpml 多语言插件添加菜单选项
add_filter('wp_nav_menu_items', 'wpml_nav_menu_items', 10, 2);
function wpml_nav_menu_items($items, $args) {
    // get languages
    $languages = apply_filters( 'wpml_active_languages', NULL, 'skip_missing=0' );

    // add $args->theme_location == 'primary-menu' in the conditional if we want to specify the menu location.
    if ( $languages && $args->theme_location == 'primary') {
        if(!empty($languages) && count($languages)>1){
            foreach($languages as $l){
                if($l['active']){
                    $items .= '<li class="menu-item dropdown"><a href="javascript:;"><img src="' . $l['country_flag_url'] . '" height="12" alt="' . $l['language_code'] . '" width="18"> ' . $l['native_name'] . '</a><ul class="dropdown-menu">';
                }
            }
            foreach($languages as $l){
                if(!$l['active']){
                    // flag with native name
                    $items .= '<li class="menu-item"><a href="' . $l['url'] . '"><img src="' . $l['country_flag_url'] . '" height="12" alt="' . $l['language_code'] . '" width="18"> ' . $l['native_name'] . '</a></li>';
                }
            }
            $items .= '</ul></li>';
        }
    }

    return $items;
}

add_filter( 'mce_buttons_2', 'wpcom_mce_wp_page' );
function wpcom_mce_wp_page( $buttons ) {
    $buttons[] = 'wp_page';
    return $buttons;
}

add_filter( 'mce_buttons', 'wpcom_mce_buttons', 20 );
function wpcom_mce_buttons( $buttons ) {
    $res = array();
    foreach( $buttons as $bt ) {
        $res[] = $bt;
        if( $bt=='formatselect' && !in_array( 'fontsizeselect', $buttons ) ){
            $res[] = 'fontsizeselect';
        } else if( $bt=='link' && !in_array( 'unlink', $buttons ) ){
            $res[] = 'unlink';
        }
    }
    return $res;
}

add_filter( 'tiny_mce_before_init', 'wpcom_mce_text_sizes' );
function wpcom_mce_text_sizes( $initArray ){
    $initArray['fontsize_formats'] = "10px 12px 14px 16px 18px 20px 24px 28px 32px 36px 42px";
    return $initArray;
}

// 控制边栏标签云
add_filter('widget_tag_cloud_args', 'wpcom_tag_cloud_filter', 10);
function wpcom_tag_cloud_filter($args = array()) {
    global $options;
    $args['number'] = isset($options['tag_cloud_num']) && $options['tag_cloud_num'] ? $options['tag_cloud_num'] : 30;
    // $args['orderby'] = 'count';
    // $args['order'] = 'RAND';
    return $args;
}

add_filter( 'pre_update_option_sticky_posts', 'wpcom_fix_sticky_posts' );
if ( ! function_exists( 'wpcom_fix_sticky_posts' ) ) :
    function wpcom_fix_sticky_posts( $stickies ) {
        if( !class_exists('SCPO_Engine') ) {
            global $wpdb;
            $menu_order = 1;
            $count = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE `post_type` = 'post' AND `menu_order` not IN (0,1)" );
            if( $count>0 ) {
                // 先预处理防止插件设置的menu_order，主要是SCPOrder插件
                $wpdb->update($wpdb->posts, array('menu_order' => 0), array('post_type' => 'post'));
            }
        }else{
            $menu_order = -1;
        }

        $old_stickies = array_diff( get_option( 'sticky_posts' ), $stickies );
        foreach( $stickies as $sticky )
            wp_update_post( array( 'ID' => $sticky, 'menu_order' => $menu_order ) );
        foreach( $old_stickies as $sticky )
            wp_update_post( array( 'ID' => $sticky, 'menu_order' => 0 ) );

        return $stickies;
    }
endif;

if ( ! function_exists( 'wpcom_sticky_posts_query' ) && !class_exists('SCPO_Engine') ) :
    add_action( 'pre_get_posts', 'wpcom_sticky_posts_query', 20 );
    function wpcom_sticky_posts_query( $q ) {
        if( $q->get('post_type') != 'post' ) return $q;

        if( !isset( $q->query_vars[ 'ignore_sticky_posts' ] ) ){
            $q->query_vars[ 'ignore_sticky_posts' ] = 1;
        }
        if ( ( isset( $q->query_vars[ 'ignore_sticky_posts' ] ) && !$q->query_vars[ 'ignore_sticky_posts' ] ) ){
            $q->query_vars[ 'ignore_sticky_posts' ] = 1;
            if(isset($q->query_vars[ 'orderby' ]) && $q->query_vars[ 'orderby' ]) {
                $q->query_vars[ 'orderby' ] .= ' menu_order';
            }else{
                $q->query_vars[ 'orderby' ] = 'menu_order date';
            }
        }
        return $q;
    }
endif;

add_filter('wp_handle_upload_prefilter','wpcom_file_upload_rename', 10);
if ( ! function_exists( 'wpcom_file_upload_rename' ) ) :
function wpcom_file_upload_rename( $file ) {
    global $options;
    if(isset($options['file_upload_rename']) && $options['file_upload_rename']=='1') {
        $file['name'] = preg_replace('/\s/', '-', $file['name']);
        if (!preg_match('/^[0-9_a-zA-Z!@()+-.]+$/u', $file['name'])) {
            $ext = substr(strrchr($file['name'], '.'), 1);
            $file['name'] = date('YmdHis') . rand(10, 99) . '.' . $ext;
        }
    }
    return $file;
}
endif;

// 安装依赖插件
function wpcom_register_required_plugins() {
    $config = array(
        'id'           => 'wpcom',
        'default_path' => '',
        'menu'         => 'wpcom-install-plugins',
        'parent_slug'  => 'wpcom-panel',
        'capability'   => 'edit_theme_options',
        'has_notices'  => true,
        'dismissable'  => true,
        'dismiss_msg'  => '',
        'is_automatic' => false
    );

    tgmpa( $config );
}

add_action( 'tgmpa_register', 'wpcom_register_required_plugins' );

function wpcom_tgm_show_admin_notice_capability() {
    return 'edit_theme_options';
}
add_filter( 'tgmpa_show_admin_notice_capability', 'wpcom_tgm_show_admin_notice_capability' );

function wpcom_lazyimg( $img, $alt, $width='', $height='', $class='' ){
    global $options;
    $class_html = $class ? ' class="'.$class.'"' : '';
    $size = $width? ' width="'.intval($width).'"' : '';
    $size .= $height? ' height="'.intval($height).'"' : '';
    $img = esc_url($img);
    if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
        $class_html = $class ? ' class="j-lazy '.$class.'"' : ' class="j-lazy"';
        $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI.'/assets/images/lazy.png';
        $html = '<img'.$class_html.' src="'.$lazy_img.'" data-original="'.$img.'" alt="'.esc_attr($alt).'"'.$size.'>';
    }else{
        $html = '<img'.$class_html.' src="'.$img.'" alt="'.esc_attr($alt).'"'.$size.'>';
    }
    return $html;
}

function wpcom_lazybg( $img, $class='', $style='' ){
    global $options;
    if( isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='1' ){
        $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI.'/assets/images/lazy.png';
        $attr = 'class="'.$class.' j-lazy" style="background-image: url('.$lazy_img.');'.$style.'" data-original="'.$img.'"';
    }else{
        $attr = 'class="'.$class.'" style="background-image: url('.$img.');'.$style.'"';
    }
    return $attr;
}