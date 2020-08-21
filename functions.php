<?php

define( 'THEME_ID', '5b4220be66895b87' ); // 主题ID，请勿修改！！！
define( 'THEME_VERSION', '4.3.0' ); // 主题版本号，请勿修改！！！

// Themer 框架路径信息常量，请勿修改，框架会用到
define( 'FRAMEWORK_PATH', is_dir($framework_path = get_template_directory() . '/themer') ? $framework_path : get_theme_root() . '/Themer/themer' );
define( 'FRAMEWORK_URI', is_dir($framework_path) ? get_template_directory_uri() . '/themer' : get_theme_root_uri() . '/Themer/themer' );

require FRAMEWORK_PATH . '/load.php';

function add_menu(){
    return array(
        'primary'   => '导航菜单',
        'footer'   => '页脚菜单'
    );
}
add_filter('wpcom_menus', 'add_menu');

// sidebar
if ( ! function_exists( 'wpcom_widgets_init' ) ) :
    function wpcom_widgets_init() {
        register_sidebar( array(
            'name'          => '首页边栏',
            'id'            => 'home',
            'description'   => '用户首页显示的边栏',
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h3 class="widget-title">',
            'after_title'   => '</h3>'
        ) );
    }
endif;
add_action( 'wpcom_sidebar', 'wpcom_widgets_init' );

add_filter('wpcom_image_sizes', 'justnews_image_sizes', 20);
function justnews_image_sizes($image_sizes){
    $image_sizes['post-thumbnail'] = array(
        'width' => 480,
        'height' => 300
    );
    return $image_sizes;
}

// Excerpt length
if ( ! function_exists( 'wpcom_excerpt_length' ) ) :
    function wpcom_excerpt_length() {
        global $options;
        return isset($options['excerpt_len']) && $options['excerpt_len'] ? $options['excerpt_len'] : 90;
    }
endif;
add_filter( 'excerpt_length', 'wpcom_excerpt_length', 999 );

// 左右边栏设置
function sidebar_position($echo){
    global $options;
    if(isset($options['sidebar_left']) && $options['sidebar_left']==0){
        $echo .= '<style>.main{float: left;}.sidebar{float:right;}</style>'."\n";
    }
    return $echo;
}
add_filter( 'wpcom_head', 'sidebar_position' );

function format_date($time){
    global $options;
    if(isset($options['time_format']) && $options['time_format']=='0'){
        return date(get_option('date_format').(is_single()?' '.get_option('time_format'):''), $time);
    }
    $t = current_time('timestamp') - $time;
    $f = array(
        '86400'=>'天',
        '3600'=>'小时',
        '60'=>'分钟',
        '1'=>'秒'
    );
    if($t==0){
        return '1秒前';
    }else if( $t >= 604800 || $t < 0){
        return date(get_option('date_format').(is_single()?' '.get_option('time_format'):''), $time);
    }else{
        foreach ($f as $k=>$v)    {
            if (0 !=$c=floor($t/(int)$k)) {
                return $c.$v.'前';
            }
        }
    }
}

add_action('wp_ajax_wpcom_like_it', 'wpcom_like_it');
add_action('wp_ajax_nopriv_wpcom_like_it', 'wpcom_like_it');
function wpcom_like_it(){
    $data = $_POST;
    $res = array();
    if(isset($data['id']) && $data['id'] && $post = get_post($data['id'])){
        $cookie = isset($_COOKIE["wpcom_liked_".$data['id']])?$_COOKIE["wpcom_liked_".$data['id']]:0;
        if(isset($cookie) && $cookie=='1'){
            $res['result'] = -2;
        }else{
            $res['result'] = 0;
            $likes = get_post_meta($data['id'], 'wpcom_likes', true);
            $likes = $likes ? $likes : 0;
            $res['likes'] = $likes + 1;
            // 数据库增加一个喜欢数量
            update_post_meta( $data['id'], 'wpcom_likes', $res['likes'] );
            //cookie标记已经给本文点赞过了
            setcookie('wpcom_liked_'.$data['id'], 1, time()+3600*24*365, '/');
        }
    }else{
        $res['result'] = -1;
    }
    echo wp_json_encode($res);
    die();
}

add_action('wp_ajax_wpcom_heart_it', 'wpcom_heart_it');
add_action('wp_ajax_nopriv_wpcom_heart_it', 'wpcom_heart_it');
function wpcom_heart_it(){
    $data = $_POST;
    $res = array();
    $current_user = wp_get_current_user();
    if($current_user->ID){
        if(isset($data['id']) && $data['id'] && $post = get_post($data['id'])){
            // 用户关注的文章
            $u_favorites = get_user_meta($current_user->ID, 'wpcom_favorites', true);
            $u_favorites = $u_favorites ? $u_favorites : array();
            // 文章关注人数
            $p_favorite = get_post_meta($data['id'], 'wpcom_favorites', true);
            $p_favorite = $p_favorite ? $p_favorite : 0;
            if(in_array($data['id'], $u_favorites)){ // 用户是否关注本文
                $res['result'] = 1;
                $nu_favorites = array();
                foreach($u_favorites as $uf){
                    if($uf != $data['id']){
                        $nu_favorites[] = $uf;
                    }
                }
                $p_favorite -= 1;
            }else{
                $res['result'] = 0;
                $u_favorites[] = $data['id'];
                $nu_favorites = $u_favorites;
                $p_favorite += 1;
            }
            $p_favorite = $p_favorite<0 ? 0 : $p_favorite;
            update_user_meta($current_user->ID, 'wpcom_favorites', $nu_favorites);
            update_post_meta($data['id'], 'wpcom_favorites', $p_favorite);
            $res['favorites'] = $p_favorite;
        }else{
            $res['result'] = -2;
        }
    }else{ // 未登录
        $res['result'] = -1;
    }
    echo wp_json_encode($res);
    die();
}

add_filter( 'wpcom_profile_tabs_posts_class', 'justnews_profile_posts_class' );
function justnews_profile_posts_class(){
    return 'profile-posts-list article-list clearfix';
}

add_filter( 'wpcom_profile_tabs', 'wpcom_add_profile_tabs' );
function wpcom_add_profile_tabs( $tabs ){
    global $options, $current_user, $profile;
    $tabs += array(
        30 => array(
            'slug' => 'favorites',
            'title' => __( 'Favorites', 'wpcom' )
        )
    );

    if( isset($current_user->ID) && isset($profile->ID) && $profile->ID === $current_user->ID && isset($options['tougao_on']) && $options['tougao_on']=='1') {
        $tabs += array(
            40 => array(
                'slug' => 'addpost',
                'title' => __('Add post', 'wpcom')
            )
        );
    }

    return $tabs;
}

add_action('wpcom_profile_tabs_favorites', 'wpcom_favorites');
function wpcom_favorites() {
    global $profile, $post;
    $favorites = get_user_meta($profile->ID, 'wpcom_favorites', true);

    if($favorites) {
        add_filter('posts_orderby', 'favorites_posts_orderby');
        $args = array(
            'post_type' => 'post',
            'post__in' => $favorites,
            'posts_per_page' => get_option('posts_per_page'),
            'ignore_sticky_posts' => 1
        );
        $posts = new WP_Query($args);
        if ( $posts->have_posts() ) {
            echo '<ul class="profile-posts-list profile-favorites-list article-list clearfix" data-user="'.$profile->ID.'">';
            while ($posts->have_posts()) : $posts->the_post();
                get_template_part('templates/list', 'default');
            endwhile;
            echo '</ul>';
            if ($posts->max_num_pages > 1) { ?>
                <div class="load-more-wrap"><a href="javascript:;" class="load-more j-user-favorites"><?php _e('Load more posts', 'wpcom'); ?></a></div><?php }
        } else {
            if (get_current_user_id() == $profile->ID) {
                echo '<div class="profile-no-content">' . __('You have no favorite posts.', 'wpcom') . '</span></div>';
            } else {
                echo '<div class="profile-no-content">' . __('This user has no favorite posts.', 'wpcom') . '</span></div>';
            }
        }
        wp_reset_query();
    }else{
        if( get_current_user_id()==$profile->ID ) {
            echo '<div class="profile-no-content">' . __('You have no favorite posts.', 'wpcom') . '</span></div>';
        } else {
            echo '<div class="profile-no-content">' . __('This user has no favorite posts.', 'wpcom') . '</span></div>';
        }
    }
}

add_action( 'wp_ajax_wpcom_user_favorites', 'wpcom_profile_tabs_favorites' );
add_action( 'wp_ajax_nopriv_wpcom_user_favorites', 'wpcom_profile_tabs_favorites' );
function wpcom_profile_tabs_favorites(){
    if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
        $favorites = get_user_meta($user->ID, 'wpcom_favorites', true);

        if($favorites) {
            add_filter('posts_orderby', 'favorites_posts_orderby');

            $per_page = get_option('posts_per_page');
            $page = $_POST['page'];
            $page = $page ? $page : 1;
            $arg = array(
                'post_type' => 'post',
                'posts_per_page' => $per_page,
                'post__in' => $favorites,
                'paged' => $page,
                'ignore_sticky_posts' => 1
            );
            $posts = new WP_Query($arg);

            if ($posts->have_posts()) {
                while ($posts->have_posts()) : $posts->the_post();
                    get_template_part('templates/list', 'default');
                endwhile;
                wp_reset_postdata();
            } else {
                echo 0;
            }
        }
    }
    exit;
}

function favorites_posts_orderby( $orderby ){
    global $wpdb, $profile;
    if( !isset($profile) ) return $orderby;

    $favorites = get_user_meta( $profile->ID, 'wpcom_favorites', true );
    if($favorites) $orderby = "FIELD(".$wpdb->posts.".ID, ".implode(',', $favorites).") DESC";

    return $orderby;
}

add_filter( 'wpcom_profile_tab_url', 'add_post_tab_link', 10, 3 );
function add_post_tab_link( $tab_html, $tab, $url ){
    if( $tab['slug'] == 'addpost' ){
        $tab_html = '<a target="_blank" href="' . wpcom_addpost_url() . '">'.$tab['title'].'</a>';
    }
    return $tab_html;
}

function wpcom_addpost_url(){
    global $options;
    if( isset($options['tougao_page']) && $options['tougao_page'] ){
        return get_permalink( $options['tougao_page'] );
    }
}

function post_editor_settings($args = array()){
    $img = current_user_can('upload_files');
    return array(
        'textarea_name' => $args['textarea_name'],
        //'textarea_rows' => $args['textarea_rows'],
        'media_buttons' => false,
        'quicktags' => false,
        'tinymce'       => array(
            'height'        => 350,
            'toolbar1' => 'formatselect,bold,underline,blockquote,forecolor,alignleft,aligncenter,alignright,link,unlink,bullist,numlist,'.($img?'wpcomimg,':'image,').'undo,redo,fullscreen,wp_help',
            'toolbar2' => '',
            'toolbar3' => '',
        )
    );
}

add_filter( 'mce_external_plugins', 'wpcom_mce_plugin');
function wpcom_mce_plugin($plugin_array){
    global $is_submit_page;
    if ( $is_submit_page ) {
        wp_enqueue_media();
        wp_enqueue_script('jquery.taghandler', get_template_directory_uri() . '/js/jquery.taghandler.min.js', array('jquery'), THEME_VERSION, true);
        wp_enqueue_script('edit-post', get_template_directory_uri() . '/js/edit-post.js', array('jquery'), THEME_VERSION, true);

        $plugin_array['wpcomimg'] = admin_url('admin-ajax.php?action=wpcomimg');
    }
    return $plugin_array;
}

add_action('wp_ajax_wpcomimg', 'wpcom_img');
function wpcom_img(){
    header("Content-type: text/javascript");
    echo '(function($) {
            tinymce.create("tinymce.plugins.wpcomimg", {
                init : function(ed, url) {
                    ed.addButton("wpcomimg", {
                        icon: "image",
                        tooltip : "添加图片",
                        onclick: function(){
                            var uploader;
                            if (uploader) {
                                uploader.open();
                            }else{
                                uploader = wp.media.frames.file_frame = wp.media({
                                    title: "选择图片",
                                    button: {
                                        text: "插入图片"
                                    },
                                    library : {
                                        type : "image"
                                    },
                                    multiple: true
                                });
                                uploader.on("select", function() {
                                    var attachments = uploader.state().get("selection").toJSON();
                                    var img = "";
                                    for(var i=0;i<attachments.length;i++){
                                        img += "<img src=\""+attachments[i].url+"\" width=\""+attachments[i].width+"\" height=\""+attachments[i].height+"\" alt=\""+(attachments[i].alt?attachments[i].alt:attachments[i].title)+"\">";
                                    }
                                    tinymce.activeEditor.execCommand("mceInsertContent", false, img)
                                });
                                uploader.open();
                            }
                        }
                    });
                }
        });
        // Register plugin
        tinymce.PluginManager.add("wpcomimg", tinymce.plugins.wpcomimg);
        })(jQuery);';
    exit;
}

add_action('pre_get_posts','wpcom_restrict_media_library');
function wpcom_restrict_media_library( $wp_query_obj ) {
    global $current_user, $pagenow;
    if( ! $current_user instanceof WP_User )
        return;
    if( 'admin-ajax.php' != $pagenow || $_REQUEST['action'] != 'query-attachments' )
        return;
    if( !current_user_can('edit_others_posts') )
        $wp_query_obj->set('author', $current_user->ID );
    return;
}

function wpcom_tougao_tinymce_style($content) {
    if ( ! is_admin() ) {
        global $editor_styles, $stylesheet;
        $editor_styles = (array) $editor_styles;
        $stylesheet    = (array) $stylesheet;
        $stylesheet[] = 'css/editor-style.css';
        $editor_styles = array_merge( $editor_styles, $stylesheet );
    }
    return $content;
}

add_filter('wpcom_update_post','wpcom_update_post');
function wpcom_update_post($res){

    add_filter('the_editor_content', "wpcom_tougao_tinymce_style");

    if(isset($_POST['post-title'])){ // 只处理post请求
        $nonce = $_POST['wpcom_update_post_nonce'];
        if ( wp_verify_nonce( $nonce, 'wpcom_update_post' ) ){
            $post_id = isset($_GET['post_id'])?$_GET['post_id']:'';

            $post_title = $_POST['post-title'];
            $post_excerpt = $_POST['post-excerpt'];
            $post_content = $_POST['post-content'];
            $post_category = isset($_POST['post-category'])?$_POST['post-category']:array();
            $post_tags = $_POST['post-tags'];
            $_thumbnail_id = $_POST['_thumbnail_id'];

            if($post_id){ // 编辑文章
                $post = get_post($post_id);
                if(isset($post->ID)) { // 文章要存在
                    $p = array(
                        'ID' => $post_id,
                        'post_type' => 'post',
                        'post_title' => $post_title,
                        'post_excerpt' => $post_excerpt,
                        'post_content' => $post_content,
                        'post_category' => $post_category,
                        'tags_input' => $post_tags
                    );
                    if($post->post_status=='draft' && trim($post_title)!='' && trim($post_content)!=''){
                        $p['post_status'] = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
                    }
                    $pid = wp_update_post($p, true);
                    if ( !is_wp_error( $pid ) ) {
                        update_post_meta($pid, '_thumbnail_id', $_thumbnail_id);
                    }
                }
            }else{ // 新建文章
                if(trim($post_title)=='' && trim($post_content)==''){
                    return array();
                }else if(trim($post_title)=='' || trim($post_content)=='' || empty($post_category)){
                    $post_status = 'draft';
                }else{
                    $post_status = current_user_can( 'publish_posts' ) ? 'publish' : 'pending';
                }
                $p = array(
                    'post_type' => 'post',
                    'post_title' => $post_title,
                    'post_excerpt' => $post_excerpt,
                    'post_content' => $post_content,
                    'post_status' => $post_status,
                    'post_category' => $post_category,
                    'tags_input' => $post_tags
                );
                $pid = wp_insert_post($p, true);
                if ( !is_wp_error( $pid ) ) {
                    update_post_meta($pid, '_thumbnail_id', $_thumbnail_id);
                    update_post_meta($pid, 'wpcom_copyright_type', 'copyright_tougao');
                    wp_redirect(get_edit_link($pid).'&submit=true');
                }
            }
        }
    }
    return $res;
}

function get_edit_link($id){
    $url = wpcom_addpost_url();
    $url =  add_query_arg( 'post_id', $id, $url );
    return $url;
}

function max_page(){
    global $wp_query;
    return $wp_query->max_num_pages;
}

add_action('wp_ajax_wpcom_load_posts', 'wpcom_load_posts');
add_action('wp_ajax_nopriv_wpcom_load_posts', 'wpcom_load_posts');
function wpcom_load_posts(){
    $id = isset($_POST['id']) ? $_POST['id'] : '';
    $page = isset($_POST['page']) ? $_POST['page'] : '';
    $page = $page ? $page : 1;
    $per_page = get_option('posts_per_page');
    if($id){
        $posts = new WP_Query(array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'cat' => $id,
            'post_type' => 'post',
            'post_status' => array( 'publish' ),
            'ignore_sticky_posts' => 0
        ));
    }else{
        global $options;
        $arg = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'ignore_sticky_posts' => 0,
            'post_type' => 'post',
            'post_status' => array( 'publish' ),
            'category__not_in' => isset($options['newest_exclude']) ? $options['newest_exclude'] : array()
        );
        $posts = new WP_Query($arg);

    }
    if($posts->have_posts()) {
        while ( $posts->have_posts() ) : $posts->the_post();
            get_template_part('templates/list', 'default-sticky');
        endwhile;
        wp_reset_postdata();
        if($id && $page==1 && get_category($id)->count>$per_page){
            echo '<li class="load-more-wrap"><a class="load-more j-load-more" data-id="'.$id.'" href="javascript:;">'.__('Load more posts', 'wpcom').'</a></li>';
        }
    }else{
        echo 0;
    }
    exit;
}

add_action( 'init', 'wpcom_create_special' );
function wpcom_create_special(){
    global $options;
    if(!isset($options['special_on']) || $options['special_on']=='1') { //是否开启专题功能
        $slug = isset($options['special_slug']) && $options['special_slug'] ? $options['special_slug'] : 'special';
        $labels = array(
            'name' => '专题',
            'singular_name' => '专题',
            'search_items' => '搜索专题',
            'all_items' => '所有专题',
            'parent_item' => '父级专题',
            'parent_item_colon' => '父级专题',
            'edit_item' => '编辑专题',
            'update_item' => '更新专题',
            'add_new_item' => '添加专题',
            'new_item_name' => '新专题名',
            'not_found' => '暂无专题',
            'menu_name' => '专题',
        );

        $args = array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
            'rewrite' => array('slug' => $slug),
            'show_in_rest' => true
        );
        register_taxonomy('special', 'post', $args);
    }
}

function get_special_list($num=10, $paged=1){
    $special = get_terms( array(
        'taxonomy' => 'special',
        'orderby' => 'id',
        'order' => 'DESC',
        'number' => $num,
        'hide_empty' => false,
        'offset' => $num*($paged-1)
    ) );
    return $special;
}

// 优化专题排序支持 Simple Custom Post Order 插件
add_filter( 'get_terms_orderby', 'wpcom_get_terms_orderby', 20, 3 );
function wpcom_get_terms_orderby($orderby, $args, $tax){
    if(class_exists('SCPO_Engine') && $tax && count($tax)==1 && $tax[0]=='special'){
        $orderby = 't.term_order, t.term_id';
    }
    return $orderby;
}

add_action('wp_ajax_wpcom_load_special', 'wpcom_load_special');
add_action('wp_ajax_nopriv_wpcom_load_special', 'wpcom_load_special');
function wpcom_load_special(){
    global $options, $post;
    $page = $_POST['page'];
    $page = $page ? $page : 1;
    $per_page = isset($options['special_per_page']) && $options['special_per_page'] ? $options['special_per_page'] : 10;

    $special = get_special_list($per_page, $page);
    if($special){
    foreach($special as $sp){
        $thumb = get_term_meta( $sp->term_id, 'wpcom_thumb', true );
        $link = get_term_link($sp->term_id);
        ?>
        <div class="col-md-6 col-xs-12 special-item-wrap">
            <div class="special-item">
                <div class="special-item-top">
                    <div class="special-item-thumb">
                        <a href="<?php echo $link;?>" target="_blank"><img src="<?php echo esc_url($thumb);?>" alt="<?php echo esc_attr($sp->name);?>"></a>
                    </div>
                    <div class="special-item-title">
                        <h2><a href="<?php echo $link;?>" target="_blank"><?php echo $sp->name;?></a></h2>
                        <?php echo category_description($sp->term_id);?>
                    </div>
                    <a class="special-item-more" href="<?php echo $link;?>"><?php echo _x('Read More', 'topic', 'wpcom');?></a>
                </div>
                <ul class="special-item-bottom">
                    <?php
                    $args = array(
                        'posts_per_page' => 3,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'special',
                                'field' => 'term_id',
                                'terms' => $sp->term_id
                            )
                        )
                    );
                    $postslist = get_posts( $args );
                    foreach($postslist as $post) {
                        setup_postdata($post);?>
                        <li><a title="<?php echo esc_attr(get_the_title());?>" href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a></li>
                    <?php } wp_reset_postdata(); ?>
                </ul>
            </div>
        </div>
    <?php }
    } else {
        echo 0;
    }
    exit;
}

function wpcom_post_copyright() {
    global $post, $options;
    $copyright = '';

    $copyright_type = get_post_meta($post->ID, 'wpcom_copyright_type', true);
    if(!$copyright_type){
        $copyright = isset($options['copyright_default']) ? $options['copyright_default'] : '';
    }else if($copyright_type=='copyright_tougao'){
        $copyright = isset($options['copyright_tougao']) ? $options['copyright_tougao'] : '';;
    }else if($copyright_type){
        if(isset($options['copyright_id']) && $options['copyright_id']) {
            foreach ($options['copyright_id'] as $i => $id) {
                if($copyright_type == $id && $options['copyright_text'][$i]) {
                    $copyright = $options['copyright_text'][$i];
                }
            }
        }
    }

    if(preg_match('%SITE_NAME%', $copyright)) $copyright = str_replace('%SITE_NAME%', get_bloginfo('name'), $copyright);
    if(preg_match('%SITE_URL%', $copyright)) $copyright = str_replace('%SITE_URL%', get_bloginfo('url'), $copyright);
    if(preg_match('%POST_TITLE%', $copyright)) $copyright = str_replace('%POST_TITLE%', get_the_title(), $copyright);
    if(preg_match('%POST_URL%', $copyright)) $copyright = str_replace('%POST_URL%', get_permalink(), $copyright);
    if(preg_match('%AUTHOR_NAME%', $copyright)) $copyright = str_replace('%AUTHOR_NAME%', get_the_author(), $copyright);
    if(preg_match('%AUTHOR_URL%', $copyright)) $copyright = str_replace('%AUTHOR_URL%', get_author_posts_url(get_the_author_meta( 'ID' )), $copyright);
    if(preg_match('%ORIGINAL_NAME%', $copyright)) $copyright = str_replace('%ORIGINAL_NAME%', get_post_meta($post->ID, 'wpcom_original_name', true), $copyright);
    if(preg_match('%ORIGINAL_URL%', $copyright)) $copyright = str_replace('%ORIGINAL_URL%', get_post_meta($post->ID, 'wpcom_original_url', true), $copyright);

    echo $copyright ? '<div class="entry-copyright">'.$copyright.'</div>' : '';
}

add_filter('comment_reply_link', 'wpcom_comment_reply_link', 10, 1);
function wpcom_comment_reply_link($link){
    if ( get_option( 'comment_registration' ) && ! is_user_logged_in() ) {
        $link = '<a rel="nofollow" class="comment-reply-login" href="javascript:;">回复</a>';
    }
    return $link;
}

add_action('init', 'wpcom_allow_contributor_uploads');
function wpcom_allow_contributor_uploads() {
    $user = wp_get_current_user();
    if( isset($user->roles) && $user->roles && $user->roles[0] == 'contributor' ){
        global $options;
        $allow = isset($options['tougao_upload']) && $options['tougao_upload']=='0' ? 0 : 1;
        $can_upload = isset($user->allcaps['upload_files']) ? $user->allcaps['upload_files'] : 0;

        if ( $allow && !$can_upload ) {
            $contributor = get_role('contributor');
            $contributor->add_cap('upload_files');
        } else if(!$allow && $can_upload){
            $contributor = get_role('contributor');
            $contributor->remove_cap('upload_files');
        }
    }
}

add_theme_support( 'wc-product-gallery-lightbox' );

add_action( 'wpcom_echo_ad', 'wpcom_echo_ad', 10, 1);
function wpcom_echo_ad( $id ){
    if(defined('DOING_AJAX') && DOING_AJAX) return false;
    if($id && $id=='ad_flow'){
        global $wp_query;
        if(!isset($wp_query->ad_index)) $wp_query->ad_index = rand(1, $wp_query->post_count-2);
        $current_post = $wp_query->current_post;
        if(isset($wp_query->posts->current_post)) $current_post = $wp_query->posts->current_post;
        if($current_post==$wp_query->ad_index) echo wpcom_ad_html($id);
    }else if($id) {
        echo wpcom_ad_html($id);
    }
}

function wpcom_ad_html($id){
    if($id) {
        global $options;
        $html = '';
        if( wp_is_mobile() && isset($options[$id.'_mobile']) && $options[$id.'_mobile'] ) {
            $html = '<div class="wpcom_ad_wrap">';
            $html .= $options[$id.'_mobile'];
            $html .= '</div>';
        } else if ( isset($options[$id]) && $options[$id] ) {
            $html = '<div class="wpcom_ad_wrap">';
            $html .= $options[$id];
            $html .= '</div>';
        }

        if($html && $id=='ad_flow') $html = '<li class="item item-ad">'.$html.'</li>';
        return $html;
    }
}

add_action( 'wp_head', 'wpcom_style_output', 20 );
if ( ! function_exists( 'wpcom_style_output' ) ) :
    function wpcom_style_output(){
        global $options; ?>
        <style>
            <?php
            $theme_color = WPCOM::color($options['theme_color']?$options['theme_color']:'#3ca5f6');
            $theme_color_hover = WPCOM::color($options['theme_color_hover']?$options['theme_color_hover']:'#4285f4');
            $sticky_color1 = WPCOM::color(isset($options['sticky_color1'])?$options['sticky_color1']:'');
            $sticky_color2 = WPCOM::color(isset($options['sticky_color2'])?$options['sticky_color2']:'');
            if( $theme_color!='#3ca5f6' || $theme_color_hover!='#4285f4' ) include get_template_directory() . '/css/color.php';
            if( function_exists('is_woocommerce') ) include get_template_directory() . '/css/woo-color.php';
            if(isset($options['bg_color']) && ($options['bg_color'] || $options['bg_image'])){ ?>@media (min-width: 992px){
                body{  <?php if($options['bg_color']) {echo 'background-color: '.WPCOM::color($options['bg_color']).';';};?> <?php if($options['bg_image']) {echo 'background-image: url('.$options['bg_image'].');';};?><?php if($options['bg_image_repeat']) {echo 'background-repeat: '.$options['bg_image_repeat'].';';};?><?php if($options['bg_image_position']) {echo 'background-position: '.$options['bg_image_position'].';';};?><?php if($options['bg_image_attachment']=='1') {echo 'background-attachment: fixed;';};?>}
                <?php if($options['special_title_color']){?>.special-head .special-title,.special-head p{color:<?php echo WPCOM::color($options['special_title_color']);?>;}.special-head .page-description:before{background:<?php echo WPCOM::color($options['special_title_color']);?>;}<?php } ?>
                .special-head .page-description:before,.special-head p{opacity: 0.5;}
            }<?php } if( isset($options['member_login_bg']) && $options['member_login_bg'] !='' ) { ?>
            .page-template-page-fullnotitle.member-login #wrap,.page-template-page-fullnotitle.member-register #wrap{ background-image: url('<?php echo esc_url($options['member_login_bg']);?>');}
            <?php } ?>.j-share{position: fixed!important;top: <?php echo $options['action_top']?$options['action_top']:'50%'?>!important;}
            <?php if(isset($options['logo-height']) && $logo_height = intval($options['logo-height'])){
            $logo_height = $logo_height>50 ? 50 : $logo_height;
            ?>
            .header .logo img{max-height: <?php echo $logo_height;?>px;}
            <?php } if(isset($options['logo-height-mobile']) && $mob_logo_height = intval($options['logo-height-mobile'])){
            $mob_logo_height = $mob_logo_height>40 ? 40 : $mob_logo_height;
            ?>
            @media (max-width: 767px){
                .header .logo img{max-height: <?php echo $mob_logo_height;?>px;}
            }
            <?php }
            $video_height = intval(isset($options['post_video_height']) && $options['post_video_height'] ? $options['post_video_height'] : 482);?>
            .entry .entry-video{ height: <?php echo $video_height ?>px;}
            @media (max-width: 1219px){
                .entry .entry-video{ height: <?php echo $video_height * (688/858) ?>px;}
            }
            @media (max-width: 991px){
                .entry .entry-video{ height: <?php echo $video_height * (800/858) ?>px;}
            }
            @media (max-width: 767px){
                .entry .entry-video{ height: <?php echo $video_height/1.4 ?>px;}
            }
            @media (max-width: 500px){
                .entry .entry-video{ height: <?php echo $video_height/2 ?>px;}
            }
            <?php if(get_locale()!='zh_CN'){ ?>
            .action .a-box:hover:after{padding: 0;font-family: "FontAwesome";font-size: 20px;line-height: 40px;}
            .action .contact:hover:after{content:'\f0e5';}
            .action .wechat:hover:after{content:'\f029';}
            .action .share:hover:after{content:'\f045';}
            .action .gotop:hover:after{content:'\f106';font-size: 36px;}
            <?php }
            if($sticky_color1 && $sticky_color2){ ?>
            @media screen and (-webkit-min-device-pixel-ratio: 0) {
                .article-list .item-sticky .item-title a{-webkit-background-clip: text;-webkit-text-fill-color: transparent;}
                .article-list .item-sticky .item-title a, .article-list .item-sticky .item-title a .sticky-post {
                    background-image: -webkit-linear-gradient(0deg, <?php echo $sticky_color1;?> 0%, <?php echo $sticky_color2;?> 100%);
                    background-image: linear-gradient(90deg, <?php echo $sticky_color1;?> 0%, <?php echo $sticky_color2;?> 100%);
                }
            }
            <?php } echo $options['custom_css'];?>
        </style>
    <?php }
endif;

function is_multimage( $post_id = '' ){
    global $post, $options;
    if($post_id==''){
        $post_id = $post->ID;
    }
    $multimage = get_post_meta($post_id, 'wpcom_multimage', true);
    $multimage = $multimage=='' ? (isset($options['list_multimage']) ? $options['list_multimage'] : 0) : $multimage;
    return $multimage;
}

add_action('init', 'wpcom_kx_init');
if ( ! function_exists( 'wpcom_kx_init' ) ) :
    function wpcom_kx_init(){
        global $options;
        if(isset($options['kx_on']) && $options['kx_on']=='1') {
            $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';
            $labels = array(
                'name' => '快讯',
                'singular_name' => '快讯',
                'add_new' => '添加',
                'add_new_item' => '添加',
                'edit_item' => '编辑',
                'new_item' => '添加',
                'view_item' => '查看',
                'search_items' => '查找',
                'not_found' => '没有内容',
                'not_found_in_trash' => '回收站为空',
                'parent_item_colon' => ''
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'query_var' => true,
                'capability_type' => 'post',
                'hierarchical' => true,
                'menu_position' => null,
                'rewrite' => array('slug' => $slug),
                'show_in_rest' => true,
                'supports' => array('title', 'excerpt', 'thumbnail', 'comments')
            );
            register_post_type('kuaixun', $args);


            // add post meta
            add_filter( 'wpcom_post_metas', 'wpcom_add_kx_metas' );
        }
    }
endif;

add_action( 'pre_get_posts', 'wpcom_kx_orderby' );
function wpcom_kx_orderby( $query ){
    if( function_exists('get_current_screen') && $query->is_admin ) {
        $screen = get_current_screen();
        if ( isset($screen->base) && isset($screen->post_type) && 'edit' == $screen->base && 'kuaixun' == $screen->post_type && !isset($_GET['orderby'])) {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
}

if ( ! function_exists( 'wpcom_add_kx_metas' ) ) :
    function wpcom_add_kx_metas( $metas ){
        $metas['kuaixun'] = array(
            array(
                "title" => "快讯设置",
                "option" => array(
                    array(
                        'name' => 'kx_url',
                        'title' => '快讯来源',
                        'desc' => '快讯来源链接地址',
                        'type' => 'text'
                    )
                )
            )
        );
        return $metas;
    }
endif;

add_filter( 'get_the_excerpt', 'wpcom_kx_excerpt', 20, 2 );
if ( ! function_exists( 'wpcom_kx_excerpt' ) ) :
    function wpcom_kx_excerpt( $excerpt, $post ) {
        if( $post->post_type == 'kuaixun' && $url = get_post_meta($post->ID, 'wpcom_kx_url', true ) ){
            $excerpt .= ' <a class="kx-more" href="'.esc_url($url).'" target="_blank" rel="nofollow">[原文链接]</a>';
        }
        return $excerpt;
    }
endif;

add_action( 'init', 'wpcom_kx_rewrite' );
function wpcom_kx_rewrite() {
    global $wp_rewrite, $options, $permalink_structure;
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
    if($permalink_structure){
        $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';

        $queryarg = 'post_type=kuaixun&p=';
        $wp_rewrite->add_rewrite_tag( '%kx_id%', '([^/]+)', $queryarg );
        $wp_rewrite->add_permastruct( 'kuaixun', $slug.'/%kx_id%.html', false );
    }
}

add_filter('post_type_link', 'wpcom_kx_permalink', 5, 2);
function wpcom_kx_permalink( $post_link, $id ) {
    global $wp_rewrite, $permalink_structure;
    if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
    if($permalink_structure) {
        $post = get_post($id);
        if (!is_wp_error($post) && $post->post_type == 'kuaixun') {
            $newlink = $wp_rewrite->get_extra_permastruct('kuaixun');
            $newlink = str_replace('%kx_id%', $post->ID, $newlink);
            $newlink = home_url(untrailingslashit($newlink));
            return $newlink;
        }
    }
    return $post_link;
}

// 旧版快讯链接兼容跳转新链接
add_action( 'template_redirect', 'wpcom_kx_old_link', 1 );
function wpcom_kx_old_link(){
    global $wp, $options;
    $slug = isset($options['kx_slug']) && $options['kx_slug'] ? $options['kx_slug'] : 'kuaixun';
    $url = untrailingslashit(home_url( $wp->request ));
    if( preg_match('/\/'.$slug.'\/\d+$/i', $url) ){
        wp_redirect( $url . '.html', 301 );
        exit;
    }
}

add_action('wp_ajax_wpcom_load_kuaixun', 'wpcom_load_kuaixun');
add_action('wp_ajax_nopriv_wpcom_load_kuaixun', 'wpcom_load_kuaixun');
if ( ! function_exists( 'wpcom_load_kuaixun' ) ) :
    function wpcom_load_kuaixun(){
        global $options;
        $page = $_POST['page'];
        $page = $page ? $page : 1;
        $per_page = get_option('posts_per_page');

        $arg = array(
            'posts_per_page' => $per_page,
            'paged' => $page,
            'post_status' => array( 'publish' ),
            'post_type' => 'kuaixun'
        );
        $posts = new WP_Query($arg);

        if($posts->have_posts()) {
            $cur_day = '';
            $weekarray = array("日","一","二","三","四","五","六");
            while ( $posts->have_posts() ) : $posts->the_post();
                if($cur_day != $date = get_the_date(get_option('date_format'))){
                    $cur_day = $date;
                    $pre_day = '';
                    $week = $weekarray[date('w', strtotime(get_the_date('c')) )];
                    if(date(get_option('date_format'), time()) == $date) {
                        $pre_day = '今天 • ';
                    }else if(date(get_option('date_format'), strtotime("-1 day")) == $date){
                        $pre_day = '昨天 • ';
                    }else if(date(get_option('date_format'), strtotime("-2 day")) == $date){
                        $pre_day = '前天 • ';
                    }
                    echo '<div class="kx-date">'. $pre_day .$date . ' • 星期' . $week.'</div>';
                } ?>
                <div class="kx-item" data-id="<?php the_ID();?>">
                    <span class="kx-time"><?php the_time(get_option('time_format'));?></span>
                    <div class="kx-content">
                        <h2><?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                <a href="<?php the_permalink();?>" target="_blank"><?php the_title();?></a>
                            <?php } else{ the_title(); } ?></h2>
                        <?php the_excerpt();?>
                        <?php if(get_the_post_thumbnail()){ ?>
                            <?php if(isset($options['kx_url_enable']) &&  $options['kx_url_enable'] == '1'){ ?>
                                <a class="kx-img" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank"><?php the_post_thumbnail('full'); ?></a>
                            <?php }else{ ?>
                                <div class="kx-img"><?php the_post_thumbnail('full'); ?></div>
                            <?php } ?>
                        <?php } ?>
                    </div>

                    <div class="kx-meta hidden-sm hidden-md hidden-lg clearfix">
                        <span class="j-mobile-share" data-id="<?php the_ID();?>">
                            <i class="fa fa-share-alt"></i> 生成分享图片
                        </span>
                    </div>
                    <div class="kx-meta hidden-xs clearfix" data-url="<?php echo urlencode(get_permalink());?>">
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
            <?php endwhile;
            wp_reset_postdata();
        }else{
            echo 0;
        }
        exit;
    }
endif;

add_action('wp_ajax_wpcom_new_kuaixun', 'wpcom_new_kuaixun');
add_action('wp_ajax_nopriv_wpcom_new_kuaixun', 'wpcom_new_kuaixun');
function wpcom_new_kuaixun(){
    $id = isset($_POST['id']) && $_POST['id'] ? $_POST['id'] : '';
    if($post = get_post($id)){
        $time = get_the_time('U', $post->ID);
        $args = array(
            'post_status' => array( 'publish' ),
            'post_type' => 'kuaixun',
            'date_query' => array(
                array(
                    'after'    => array(
                        'year'   => date('Y', $time),
                        'month'  => date('m', $time),
                        'day'    => date('d', $time),
                        'hour'   => date('H', $time),
                        'minute' => date('i', $time),
                        'second' => date('s', $time),
                    ),
                    'inclusive' => false
                )
            ),
            'posts_per_page' => -1,
        );
        $my_date_query = new WP_Query( $args );
        echo $my_date_query->found_posts;
    }
    exit;
}

add_action('wp_loaded', 'wpcom_tinymce_replace_start');
if ( ! function_exists( 'wpcom_tinymce_replace_start' ) ) {
    function wpcom_tinymce_replace_start() {
        if(!is_admin()) {
            global $is_IE;
            if (!$is_IE) return false;
            ob_start("wpcom_tinymce_replace_url");
        }
    }
}

add_action('shutdown', 'wpcom_tinymce_replace_end');
if ( ! function_exists( 'wpcom_tinymce_replace_end' ) ) {
    function wpcom_tinymce_replace_end() {
        if(!is_admin()) {
            global $is_IE;
            if (!$is_IE) return false;
            if (ob_get_level() > 0) ob_end_flush();
        }
    }
}

if ( ! function_exists( 'wpcom_tinymce_replace_url' ) ) {
    function wpcom_tinymce_replace_url( $str ){
        $regexp = "/\/wp-includes\/js\/tinymce/i";
        $path = get_template_directory_uri();
        $path = str_replace(get_option( 'siteurl' ), '', $path);
        $str = preg_replace( $regexp, $path . '/js/tinymce', $str );
        $str = preg_replace( '/tinymce\.Env\.ie \< 11/i', 'tinymce.Env.ie < 8', $str );
        $str = preg_replace( '/wp-editor-wrap html-active/i', 'wp-editor-wrap tmce-active', $str );
        return $str;
    }
}

add_filter( 'user_can_richedit', 'wpcom_can_richedit' );
if ( ! function_exists( 'wpcom_can_richedit' ) ) {
    function wpcom_can_richedit( $wp_rich_edit ){
        global $is_IE;
        if( !$wp_rich_edit && $is_IE && !is_admin() ){
            $wp_rich_edit = 1;
        }
        return $wp_rich_edit;
    }
}

function wpcom_post_metas( $key = '' ){
    $html = '';
    if($key){
        global $post;
        switch ($key){
            case 'h':
                $fav = get_post_meta($post->ID, 'wpcom_favorites', true);
                $fav = $fav ? $fav : 0;
                $html = '<span class="item-meta-li hearts" title="喜欢数"><i class="fa fa-heart"></i> '.$fav.'</span>';
                break;
            case 'z':
                $likes = get_post_meta($post->ID, 'wpcom_likes', true);
                $likes = $likes ? $likes : 0;
                $html = '<span class="item-meta-li likes" title="点赞数"><i class="fa fa-thumbs-up"></i> '.$likes.'</span>';
                break;
            case 'v':
                if( function_exists('the_views') ) {
                    $views = $post->views ? $post->views : 0;
                    if ($views >= 1000) $views = sprintf("%.2f", $views / 1000) . 'K';
                    $html = '<span class="item-meta-li views" title="阅读数"><i class="fa fa-eye"></i> ' . $views . '</span>';
                }
                break;
            case 'c':
                $comments = get_comments_number();
                $html = '<a class="item-meta-li comments" href="'.get_permalink($post->ID).'#comments" target="_blank" title="评论数"><i class="fa fa-comments"></i> '.$comments.'</a>';
                break;
        }
    }
    return $html;
}

// 新旧版本配置信息兼容处理
add_filter( 'option_izt_theme_options', 'wpcom_update_theme_options' );
function wpcom_update_theme_options( $value ){
    if( !(isset($value['fticon_i']) && $value['fticon_i']) ) {
        $value['fticon_i'] = array();
        $value['fticon_t'] = array();
        $value['fticon_u'] = array();
        if( isset($value['ios']) && trim($value['ios']) ){
            $value['fticon_i'][] = 'apple';
            $value['fticon_t'][] = '1';
            $value['fticon_u'][] = $value['ios'];
        }
        if( isset($value['android']) && trim($value['android']) ){
            $value['fticon_i'][] = 'android';
            $value['fticon_t'][] = '1';
            $value['fticon_u'][] = $value['android'];
        }
        if( isset($value['weixin']) && trim($value['weixin']) ){
            $value['fticon_i'][] = 'weixin';
            $value['fticon_t'][] = '1';
            $value['fticon_u'][] = $value['weixin'];
        }
        if( isset($value['weibo']) && trim($value['weibo']) ){
            $value['fticon_i'][] = 'weibo';
            $value['fticon_t'][] = '0';
            $value['fticon_u'][] = $value['weibo'];
        }
        if( isset($value['qq_weibo']) && trim($value['qq_weibo']) ){
            $value['fticon_i'][] = 'tencent-weibo';
            $value['fticon_t'][] = '0';
            $value['fticon_u'][] = $value['qq_weibo'];
        }
        if( isset($value['facebook']) && trim($value['facebook']) ){
            $value['fticon_i'][] = 'facebook';
            $value['fticon_t'][] = '0';
            $value['fticon_u'][] = $value['facebook'];
        }
        if( isset($value['twitter']) && trim($value['twitter']) ){
            $value['fticon_i'][] = 'twitter';
            $value['fticon_t'][] = '0';
            $value['fticon_u'][] = $value['twitter'];
        }
        if( isset($value['linkedin']) && trim($value['linkedin']) ){
            $value['fticon_i'][] = 'linkedin';
            $value['fticon_t'][] = '0';
            $value['fticon_u'][] = $value['linkedin'];
        }
        if( isset($value['instagram']) && trim($value['instagram']) ){
            $value['fticon_i'][] = 'instagram';
            $value['fticon_t'][] = '0';
            $value['fticon_u'][] = $value['instagram'];
        }
    }
    return $value;
}

add_shortcode('wpcom_tags', 'wpcom_shortcode_tags');
function wpcom_shortcode_tags(){
    $args = array(
        'taxonomy' => 'post_tag',
        'orderby' => 'count',
        'order'   => 'DESC'
    );
    $tags = get_terms('post_tag', $args);
    if ( empty( $tags ) || is_wp_error( $tags ) ) {
        return;
    }
    $html = '<ul class="wpcom-shortcode-tags">';
    foreach ( $tags as $key => $tag ) {
        $link = get_term_link( intval( $tag->term_id ), $tag->taxonomy );
        if ( is_wp_error( $link ) ) {
            return;
        }
        $html .= '<li><a href="'.$link.'" target="_blank" title="'.($tag->description?$tag->description:$tag->name).'">'.$tag->name.'</a><span>('.$tag->count.')</span></li>';
    }
    $html .= '</ul>';
    return $html;
}