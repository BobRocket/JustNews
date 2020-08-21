<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Member {
    function __construct(){
        global $options;
        $this->member_path = FRAMEWORK_PATH . '/member';

        add_shortcode( 'wpcom-member', array( $this, 'shortcode' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
        add_action( 'wp_ajax_wpcom_cropped_upload', array( $this, 'cropped_upload' ) );
        add_action( 'wpcom_options_updated', array( $this, 'flush_rewrite_rules' ) );
        add_action( 'save_post_page', array( $this, 'flush_rewrite_rules' ) );
        add_action( 'wpcom_cron_flush_rewrite_rules', array( $this, 'cron_flush_rewrite_rules' ) );
        add_action( 'wp_ajax_wpcom_user_posts', array( $this, 'user_posts' ) );
        add_action( 'wp_ajax_nopriv_wpcom_user_posts', array( $this, 'user_posts' ) );
        add_action( 'wp_ajax_wpcom_user_comments', array( $this, 'user_comments' ) );
        add_action( 'wp_ajax_nopriv_wpcom_user_comments', array( $this, 'user_comments' ) );
        add_action( 'wp_logout', array( $this, 'after_logout' ) );
        add_action( 'template_redirect', array( $this, 'action_before_echo' ) );
        add_action( 'wpcom_register_form', array( $this, 'register_form' ) );
        add_action( 'wpcom_login_form', array( $this, 'login_form' ) );
        add_action( 'wpcom_lostpassword_form_default', array( $this, 'lostpassword_form_default' ) );
        add_action( 'wpcom_lostpassword_form_send_success', array( $this, 'lostpassword_form_send_success' ) );
        add_action( 'wpcom_lostpassword_form_reset', array( $this, 'lostpassword_form_reset' ) );
        add_action( 'wpcom_lostpassword_form_finished', array( $this, 'lostpassword_form_finished' ) );
        add_action( 'wpcom_social_login', array( $this, 'social_login' ) );
        add_action( 'wpcom_approve_resend_form', array( $this, 'approve_resend_form' ) );
        add_action( 'user_register', array( $this, 'user_register' ) );
        add_action( 'wpcom_social_new_user', array( $this, 'social_new_user' ) );
        add_action( 'wpcom_user_meta_updated', array( $this, 'user_meta_updated'), 10, 4 );
        add_action( 'login_form_register', array( $this, 'disable_default_register'), 10 );

        add_filter( 'wpcom_localize_script', array($this, 'localize_script') );
        add_filter( 'upload_dir', array($this, 'upload_dir') );
        add_filter( 'get_avatar_url', array($this, 'get_avatar_url'), 10, 3 );
        add_filter( 'pre_get_avatar', array($this, 'pre_get_avatar'), 10, 3 );
        add_filter( 'rewrite_rules_array', array($this, 'rewrite_rules'), 10 );
        add_filter( 'query_vars', array($this, 'query_vars'), 10, 1 );
        add_filter( 'register_url', array($this, 'register_url'), 20 );
        add_filter( 'login_url', array($this, 'login_url'), 20, 2 );
        add_filter( 'logout_url', array($this, 'logout_url'), 20, 2 );
        add_filter( 'lostpassword_url', array($this, 'lostpassword_url'), 20, 2 );
        add_filter( 'author_link', array($this, 'author_link'), 20, 3 );
        add_filter( 'show_admin_bar', array($this, 'show_admin_bar') );
        add_filter( 'wp_title_parts', array($this, 'title_parts'), 5 );
        add_filter( 'user_has_cap', array( $this, 'user_has_cap' ), 10, 4 );
        add_filter( 'authenticate', array( $this, 'authenticate' ), 50, 3 );
        add_filter( 'views_users', array( $this, 'views_users' ) );
        add_filter( 'pre_get_users', array( $this, 'filter_users' ) );
        add_filter( 'bulk_actions-users', array( $this, 'bulk_actions_users' ) );
        add_filter( 'handle_bulk_actions-users', array( $this, 'handle_bulk_actions_users' ), 10, 3 );
        add_filter( 'body_class', array( $this, 'body_class' ), 10);

        add_filter( 'manage_users_columns', array( $this, 'user_registered' ) );
        add_filter( 'manage_users_custom_column', array( $this, 'user_registered_value' ), 10, 3 );
        add_filter( 'manage_users_sortable_columns', array( $this ,'user_registered_sortable') );

        $account_tabs = wpcom_account_default_tabs();
        foreach ($account_tabs as $tab){
            add_action( 'wpcom_account_tabs_' . $tab['slug'], array( $this, 'account_tabs_' . $tab['slug'] ) );
        }

        $profile_tabs = wpcom_profile_default_tabs();
        foreach ($profile_tabs as $tab){
            add_action( 'wpcom_profile_tabs_' . $tab['slug'], array( $this, 'profile_tabs_' . $tab['slug'] ) );
        }

        if( isset($options['social_login_on']) && $options['social_login_on']=='1' ) {
            new social_login();
        }

        $show_profile = apply_filters( 'wpcom_member_show_profile' , true );
        if( $show_profile ) {
            new WPCOM_User_Groups();
            add_action( 'admin_init', array( $this, 'block_access_wpadmin' ) );
        }
    }

    function flush_rewrite_rules(){
        $args = array();
        $args[] = mt_rand(1000, 99999) . '_' . time();
        wp_schedule_single_event( time() + 2, 'wpcom_cron_flush_rewrite_rules', $args );
    }

    function cron_flush_rewrite_rules(){
        flush_rewrite_rules();
    }

    function rewrite_rules( $rules ) {
        global $options, $permalink_structure;
        if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');
        $new_rules = array();
        $pre = preg_match( '/^\/index\.php\//i', $permalink_structure) ? 'index.php/' : '';

        if( isset($options['member_page_account']) && $options['member_page_account'] ) {
            $page_uri = get_page_uri( $options['member_page_account'] );
            if( function_exists('WC') ){ // woocommerce 规则优化，用于订单分页、订单详情
                $new_rules[ $pre . $page_uri . '/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&subpage=$matches[1]&pageid=$matches[2]';
            }
            $new_rules[ $pre . $page_uri . '/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&subpage=$matches[1]';
        }

        if( isset($options['member_page_profile']) && $options['member_page_profile'] ){
            $page_uri = get_page_uri( $options['member_page_profile'] );
            $new_rules[ $pre . $page_uri . '/([^/]+)/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&user=$matches[1]&subpage=$matches[2]';
            $new_rules[ $pre . $page_uri . '/([^/]+)/?$'] = 'index.php?pagename='.$page_uri.'&user=$matches[1]';
        }

        return $new_rules + $rules;
    }

    function query_vars($public_query_vars) {
        $public_query_vars[] = 'subpage';
        $public_query_vars[] = 'user';
        $public_query_vars[] = 'pageid';
        return $public_query_vars;
    }

    function upload_dir( $array ){
        if( isset($array['subdir']) && ( $array['subdir'] == '/1234/06' || $array['subdir'] =='' ) ){
            $type = $GLOBALS['image_type'] ? 'covers' : 'avatars';
            $array['subdir'] = '/member/' . $type;
            $array['path'] = $array['basedir'] . '/member/' . $type;
            $array['url'] = $array['baseurl'] . '/member/' . $type;
        }
        return $array;
    }

    function get_avatar_url( $url, $id_or_email, $args ){
        global $pagenow, $options;
        if( $pagenow == 'options-discussion.php' ) return $url;

        $user_id = 0;
        if ( is_numeric( $id_or_email ) ) {
            $user_id = absint( $id_or_email );
        } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
            $user = get_user_by( 'email', $id_or_email );
            if( isset($user->ID) && $user->ID ) $user_id = $user->ID;
        } elseif ( $id_or_email instanceof WP_User ) {
            $user_id = $id_or_email->ID;
        } elseif ( $id_or_email instanceof WP_Post ) {
            $user_id = $id_or_email->post_author;
        } elseif ( $id_or_email instanceof WP_Comment ) {
            $user_id = $id_or_email->user_id;
            if( !$user_id ){
                $user = get_user_by( 'email', $id_or_email->comment_author_email );
                if( isset($user->ID) && $user->ID ) $user_id = $user->ID;
            }
        }

        if ( $user_id && $avatar = get_user_meta( $user_id, 'wpcom_avatar', 1) ) {
            if(preg_match('/^(http|https|\/\/)/i', $avatar)){
                $url = $avatar;
            }else{
                $uploads = wp_upload_dir();
                $url = $uploads['baseurl'] . $avatar;
            }
        }else if( isset($options['member_avatar']) && $options['member_avatar'] ){
            $url = esc_url($options['member_avatar']);
        }

        $url = preg_replace('/^(http|https):/i', '', $url);
        return $url;
    }

    function pre_get_avatar( $avatar, $id_or_email, $args ){
        $url = $this->get_avatar_url( $avatar, $id_or_email, $args );
        if($url){
            $class = array( 'avatar', 'avatar-' . (int) $args['size'], 'photo' );
            if ( $args['class'] ) {
                if ( is_array( $args['class'] ) ) {
                    $class = array_merge( $class, $args['class'] );
                } else {
                    $class[] = $args['class'];
                }
            }
            $avatar = sprintf(
                    "<img alt='%s' src='%s' class='%s' height='%d' width='%d' %s/>",
                    esc_attr( $args['alt'] ),
                    esc_url( $url ),
                    esc_attr( join( ' ', $class ) ),
                    (int) $args['height'],
                    (int) $args['width'],
                    $args['extra_attr']
            );
        }
        return $avatar;
    }

    function enqueue_scripts(){
        global $profile;
        if( is_wpcom_member_page( 'account' ) ||
            ( is_wpcom_member_page('profile') && ( get_current_user_id() == $profile->ID || current_user_can( 'edit_users' ) ) )
        ){
            wp_enqueue_style( 'crop', FRAMEWORK_URI . '/assets/css/cropper.min.css', array('stylesheet'), THEME_VERSION );
            wp_enqueue_script( 'crop', FRAMEWORK_URI . '/assets/js/cropper.min.js', array( 'jquery' ), THEME_VERSION, true );
        }else if( is_wpcom_member_page( 'login' ) || is_wpcom_member_page( 'register' ) || is_wpcom_member_page('lostpassword' ) ){
            wp_enqueue_script( 'login', FRAMEWORK_URI . '/assets/js/login.js', array( 'jquery' ), THEME_VERSION, true );
        }
    }

    function localize_script( $scripts ){
        global $options;
        if( isset($options['nc_appkey']) && $options['nc_appkey']!='' && $options['nc_access_id']!=''  && $options['nc_access_secret']!='' ) {
            $nc_scene = '';
            if ( is_wpcom_member_page('login' ) || is_wpcom_member_page('lostpassword' ) ) {
                $nc_scene = 'nc_login';
            }else if( is_wpcom_member_page('register' ) ){
                $nc_scene = 'nc_register';
            }

            $nc_scene = apply_filters( 'wpcom_no_captcha_type', $nc_scene );

            if( $nc_scene != ''){
                if( wp_is_mobile() ){
                    $nc_scene = $nc_scene . '_h5';
                }
                $lang = get_locale();
                $lang_nc = array( 'ja' => 'ja_JP', 'zh_CN' => 'cn', 'zh_HK' => 'tw', 'zh_TW' => 'tw',);
                if(preg_match('/^en_/i', $lang)) $lang_nc[$lang] = 'en';

                $scripts['noCaptcha'] = array(
                    'scene' => $nc_scene,
                    'appkey' => $options['nc_appkey'],
                    'language' => isset($lang_nc[$lang]) ? $lang_nc[$lang] : $lang
                );
            }
        }

        if ( is_wpcom_member_page('login' ) || is_wpcom_member_page('register' ) || is_wpcom_member_page('lostpassword' ) ){
            $scripts['errors'] = apply_filters( 'wpcom_member_errors', array() );
        }

        if( is_wpcom_member_page( 'account' ) || (is_wpcom_member_page('profile') && get_current_user_id()) ){
            $scripts['cropper'] = array(
                'title' => __('Select photo', 'wpcom'),
                'desc_0' => __('Select your profile photo', 'wpcom'),
                'desc_1' => __('Select your cover photo', 'wpcom'),
                'btn' => __('Select photo', 'wpcom'),
                'loading' => __('Uploading...', 'wpcom'),
                'apply' => __('Apply', 'wpcom'),
                'cancel' => __('Cancel', 'wpcom'),
                'alert_size' => __('This image is too large!', 'wpcom'),
                'alert_filetype' => __('Sorry this is not a valid image.', 'wpcom'),
                'err_nonce' => __('Nonce check failed!', 'wpcom'),
                'err_fail' => __('Image upload failed!', 'wpcom'),
                'err_login' => __('You must login first!', 'wpcom'),
                'err_empty' => __('Please select a photo!', 'wpcom'),
                'ajaxerr' => __('Request failed!', 'wpcom')
            );
        }

        return $scripts;
    }

    function title_parts( $part ){
        if( is_wpcom_member_page('profile') ){
            global $wp_query, $options;
            $user_slug = isset($wp_query->query['user']) && $wp_query->query['user'] ? $wp_query->query['user'] : '';
            if( !$user_slug ) return $part;

            if( isset($options['member_user_slug']) && $options['member_user_slug']=='2' ) {
                $profile = get_user_by( 'ID', $user_slug );
            } else {
                $profile = get_user_by( 'slug', $user_slug );
            }
            if( $profile ) $part[] = $profile->display_name;
        }
        return $part;
    }

    function shortcode( $atts ){
        if( isset( $atts ['type'] ) && $atts ['type'] != '' && method_exists( $this, 'shortcode_' . $atts ['type'] ) ){
            return $this->{'shortcode_'.$atts ['type']}( $atts );
        }
    }

    function shortcode_account(){
        global $wp_query;
        if( !current_user_can('read') ) return false;

        if( isset($wp_query->query_vars['subpage']) && $wp_query->query_vars['subpage'] != '' ) {
            $subpage = $wp_query->query_vars['subpage'];
        }else{
            $subpage = 'general';
        }

        $tabs = apply_filters( 'wpcom_account_tabs', array() );
        ksort($tabs);

        $atts = array(
            'subpage' => $subpage,
            'user' => wp_get_current_user(),
            'tabs' => $tabs
        );

        $atts['args'] = apply_filters( 'wpcom_account_args', array() );
        return $this->load_template('account', $atts) ;
    }

    function shortcode_lostpassword(){
        global $wp_query;
        $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : 'default';

        $atts = array(
            'subpage' => $subpage
        );
        return $this->load_template('lostpassword', $atts) ;
    }

    function shortcode_profile(){
        if( isset( $GLOBALS['profile'] ) ){
            global $wp_query;
            $tabs = apply_filters( 'wpcom_profile_tabs', array() );
            ksort($tabs);
            $default = current($tabs);
            $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : $default['slug'];

            $atts = array(
                'profile' => $GLOBALS['profile'],
                'subpage' => $subpage,
                'tabs' => $tabs
            );

            $tabs_slug = array();
            foreach ( $tabs as $t){
                $tabs_slug[] = $t['slug'];
            }

            if( ! in_array( $subpage, $tabs_slug) ) {
                status_header(404);
            }

            return $this->load_template('profile', $atts) ;
        }
    }

    function shortcode_userlist( $atts ) {
        global $wpdb, $options;
        $paged = get_query_var('paged') ? get_query_var('paged') : (get_query_var('page') ? get_query_var('page') : 1);
        $users = null; $user_ids=array();
        $number = isset($atts['per_page']) && $atts['per_page'] ? $atts['per_page'] : 10;
        $offset = ($paged-1) * $number;
        $orderby = isset($atts['orderby']) && $atts['orderby'] ? $atts['orderby'] : 'registered';
        $order = isset($atts['order']) && $atts['order'] ? $atts['order'] : 'DESC';
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : '2';
        if( $cols!='2' && $cols!='3' && $cols!='4' ) $cols = 2;

        $args = array('number' => $number, 'offset' => $offset, 'paged' => $paged, 'orderby' => $orderby, 'order' => $order);

        if( isset($options['member_reg_active']) && $options['member_reg_active']!='0' ){
            // 开启审核则只显示审核通过的用户
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => $wpdb->get_blog_prefix() . '_wpcom_metas',
                    'value' => 's:7:"approve";i:0;',
                    'compare' => 'NOT LIKE'
                ),
                array(
                    'key' => $wpdb->get_blog_prefix() . '_wpcom_metas',
                    'compare' => 'NOT EXISTS'
                )
            );
        }

        if( isset($atts['group']) && $atts['group'] ) {
            $user_ids = get_objects_in_term( $atts['group'], 'user-groups' );
        }else if( isset($atts['users']) && $atts['users'] ){
            $user_ids = explode(',', $atts['users']);
        }

        if( $user_ids ) $args['include'] = $user_ids;

        $users_query = new WP_User_Query( $args );
        $users = $users_query->get_results();

        ob_start();
        if( !$users || is_wp_error($users) ){
            echo '<p style="text-align: center;">' . __( 'No user found.', 'wpcom' ) . '</p>';
        }else{
            $atts['users'] = $users;
            $atts['cols'] = $cols;
            echo $this->load_template( 'user-list', $atts ) ;
            $pagi_args = array( 'paged'=> $paged, 'numpages' => ceil($users_query->total_users / $number) );
            wpcom_pagination( 5, $pagi_args );
        }
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    function account_tabs_general(){
        $metas = apply_filters('wpcom_account_tabs_general_metas', array() );
        ksort($metas);
        ?>
        <form class="member-account-form" action="" method="post">
            <?php wp_nonce_field( 'member_form_general', 'member_form_general_nonce' ); ?>
            <?php foreach ($metas as $meta){ echo $this->account_field_item($meta); } ?>

            <div class="member-account-item">
                <input class="btn btn-primary" type="submit" value="<?php _e( 'Save Changes', 'wpcom' ); ?>">
            </div>
        </form>
    <?php }

    function account_tabs_password(){
        $metas = apply_filters('wpcom_account_tabs_password_metas', array() );
        ksort($metas);
        ?>
        <form class="member-account-form" action="" method="post">
            <?php wp_nonce_field( 'member_form_password', 'member_form_password_nonce' ); ?>
            <?php foreach ($metas as $meta){ echo $this->account_field_item($meta); } ?>

            <div class="member-account-item">
                <input class="btn btn-primary" type="submit" value="<?php _e( 'Save Changes', 'wpcom' ); ?>">
            </div>
        </form>
    <?php }

    function account_field_item( $args ){
        $validation = isset($GLOBALS['validation']) ? $GLOBALS['validation'] : null;

        if( isset($validation['error']) && isset($validation['error']['existing_user_email'])){
            $validation['error']['user_email'] = $validation['error']['existing_user_email'];
        }

        $html = '';
        if( $args && isset($args['type']) ){
            $label = isset($args['label']) ? $args['label'] : '';
            $name = isset($args['name']) ? $args['name'] : '';
            $value = isset($args['value']) ? $args['value'] : '';
            $disabled = isset($args['disabled']) ? $args['disabled'] : false;
            $maxlength = isset($args['maxlength']) ? $args['maxlength'] : '';
            $desc = isset($args['desc']) ? $args['desc'] : '';
            $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';

            $error = $validation && isset($validation['error'][$name]) ? $validation['error'][$name] : '';
            $value = $validation && isset($validation['value'][$name]) ? $validation['value'][$name] : $value;

            switch ($args['type']) {
                case 'textarea':
                    $html = '<div class="member-account-item'.($error?' error':'').'"><label class="member-account-label">'.$label.'</label>';
                    $html .= '<textarea class="member-account-input" name="'.$name.'"'.($disabled?' disabled':'') . ($maxlength?' maxlength="'.$maxlength.'"':'') . ' placeholder="'.$placeholder.'">'.esc_attr($value).'</textarea>';
                    if($error) $html .= '<div class="member-account-desc error">'.$error.'</div>';
                    if($desc) $html .= '<div class="member-account-desc">'.$desc.'</div>';
                    $html .= '</div>';
                    break;
                case 'password':
                    $html = '<div class="member-account-item'.($error?' error':'').'"><label class="member-account-label">'.$label.'</label>';
                    $html .= '<input type="password" class="member-account-input" name="'.$name.'" value="'.esc_attr($value).'"'.($disabled?' disabled':'').' placeholder="'.$placeholder.'">';
                    if($error) $html .= '<div class="member-account-desc error">'.$error.'</div>';
                    if($desc) $html .= '<div class="member-account-desc">'.$desc.'</div>';
                    $html .= '</div>';
                    break;
                case 'text':
                case 'default':
                    $html = '<div class="member-account-item'.($error?' error':'').'"><label class="member-account-label">'.$label.'</label>';
                    $html .= '<input type="text" class="member-account-input" name="'.$name.'" value="'.esc_attr($value).'"'.($disabled?' disabled':'') . ($maxlength?' maxlength="'.$maxlength.'"':'') . ' placeholder="'.$placeholder.'">';
                if($error) $html .= '<div class="member-account-desc error">'.$error.'</div>';
                if($desc) $html .= '<div class="member-account-desc">'.$desc.'</div>';
                    $html .= '</div>';
                    break;
            }
        }
        return $html;
    }

    function profile_tabs_posts(){
        global $post, $profile, $is_author;
        $is_author = 0;
        $current_user = wp_get_current_user();
        if( $current_user->ID && $profile->ID == $current_user->ID ) {
            $is_author = 1;
        }

        wp_reset_query();
        $per_page = get_option('posts_per_page');
        $args = array(
            'posts_per_page' => $per_page,
            'author' => $profile->ID,
            'post_status' => $is_author ? array( 'draft', 'pending', 'publish' ) : array( 'publish' )
        );
        $posts = new WP_Query($args);
        $class = apply_filters( 'wpcom_profile_tabs_posts_class', 'profile-posts-list clearfix' );
        ?>
        <?php if( $posts->have_posts() ) : ?>
            <ul class="<?php echo esc_attr($class); ?>" data-user="<?php echo $profile->ID;?>">
                <?php while( $posts->have_posts() ) : $posts->the_post();?>
                    <?php echo $this->load_template('post', array( 'post' => $post ));?>
                <?php endwhile; wp_reset_postdata(); ?>
            </ul>
            <?php if($posts->max_num_pages>1){ ?><div class="load-more-wrap"><a href="javascript:;" class="load-more j-user-posts"><?php _e( 'Load more posts', 'wpcom' );?></a></div><?php } ?>
        <?php else : ?>
            <div class="profile-no-content">
                <?php if( get_current_user_id()==$profile->ID ){ _e( 'You have not created any posts.', 'wpcom' ); }else{ _e( 'This user has not created any posts.', 'wpcom' ); } ?>
            </div>
        <?php endif; ?>
    <?php }

    function user_posts(){
        global $post, $is_author;
        if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
            $is_author = 0;
            $current_user = wp_get_current_user();
            if( $current_user->ID && $user->ID == $current_user->ID ) {
                $is_author = 1;
            }

            $per_page = get_option('posts_per_page');
            $page = $_POST['page'];
            $page = $page ? $page : 1;
            $arg = array(
                'posts_per_page' => $per_page,
                'paged' => $page,
                'author' => $user->ID,
                'post_status' => array( 'publish' )
            );
            $posts = new WP_Query($arg);

            if( $posts->have_posts() ) {
                while ($posts->have_posts()) : $posts->the_post();
                    echo $this->load_template('post', array('post' => $post));
                endwhile;
                wp_reset_postdata();
            }else{
                echo 0;
            }
        }
        exit;
    }

    function profile_tabs_comments(){
        global $profile;
        $is_user = get_current_user_id() == $profile->ID;
        $number = 10;

        $args = array(
            'number' => $number,
            'user_id' => $profile->ID,
            'status' => $is_user ? 'all':'approve',
            'offset' => 0
        );

        $comments_query = new WP_Comment_Query;
        $comments = $comments_query->query($args);
        $count = $comments_query->query($args+array('count'=>1));
        ?>
        <?php if( $comments ) : ?>
            <ul class="profile-comments-list clearfix" data-user="<?php echo $profile->ID;?>">
                <?php foreach($comments as $comment) : ?>
                    <?php echo $this->load_template('comment', array( 'comment' => $comment ));?>
                <?php endforeach; ?>
            </ul>
            <?php if($count/$number>1){ ?><div class="load-more-wrap"><a href="javascript:;" class="load-more j-user-comments"><?php _e( 'Load more comments', 'wpcom' );?></a></div><?php } ?>
        <?php else : ?>
            <div class="profile-no-content">
                <?php if( get_current_user_id()==$profile->ID ){ _e( 'You have not made any comments.', 'wpcom' ); }else{ _e( 'This user has not made any comments.', 'wpcom' ); } ?>
            </div>
        <?php endif; ?>
    <?php }

    function user_comments(){
        if( isset($_POST['user']) && is_numeric($_POST['user']) && $user = get_user_by('ID', $_POST['user'] ) ){
            $is_user = get_current_user_id() == $user->ID;
            $number = 10;
            $page = $_POST['page'];
            $page = $page ? $page : 1;
            $args = array(
                'number' => $number,
                'user_id' => $user->ID,
                'status' => $is_user ? 'all':'approve',
                'offset' => ($page-1) * $number
            );

            $comments_query = new WP_Comment_Query;
            $comments = $comments_query->query($args);

            if( $comments ) {
                foreach($comments as $comment) :
                    echo $this->load_template('comment', array( 'comment' => $comment ));
                endforeach;
            }else{
                echo 0;
            }
        }
        exit;
    }

    function after_logout(){
        wp_redirect( home_url() );
    }

    function register_form(){
        $items = apply_filters( 'wpcom_register_form_items', array() );
        ksort($items);?>
        <div class="wpcom-errmsg j-errmsg"></div>
        <form id="register-form" class="member-form j-member-form" method="post">
            <?php foreach ( $items as $item ){ echo $this->login_field_item( $item ); } ?>
            <?php wp_nonce_field( 'member_form_register', 'member_form_register_nonce' ); ?>
            <div class="last" style="margin-top: 25px;"> <input class="btn btn-login btn-block btn-lg" type="submit" id="submit" value="<?php _e('Create an account', 'wpcom');?>" data-loading-text="<?php _e('Creating account…', 'wpcom');?>"></div>
        </form>
    <?php }

    function login_form(){
        $items = apply_filters( 'wpcom_login_form_items', array() );
        ksort($items);?>
        <div class="wpcom-errmsg j-errmsg"></div>
        <form id="login-form" class="member-form j-member-form" method="post">
            <?php foreach ( $items as $item ){ echo $this->login_field_item( $item ); } ?>
            <?php wp_nonce_field( 'member_form_login', 'member_form_login_nonce' ); ?>
            <div class="checkbox">
                <label><input type="checkbox" id="remember" name="remember" value="true"><?php _e('Remember me', 'wpcom');?></label>
            </div>
            <div class="last"> <input class="btn btn-login btn-block btn-lg" type="submit" id="submit" value="<?php _e('Sign In', 'wpcom');?>" data-loading-text="<?php _e('Signing In...', 'wpcom');?>"></div>
        </form>
        <div class="member-form-footer">
            <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Forgot password?', 'wpcom');?></a>
        </div>
    <?php }

    function lostpassword_form_default(){
        $items = apply_filters( 'wpcom_lostpassword_form_items', array() );
        ksort($items);?>
        <form id="lostpassword-form" class="member-form lostpassword-form j-member-form" method="post">
            <div class="wpcom-errmsg j-errmsg"></div>
            <?php foreach ( $items as $item ){ echo $this->login_field_item( $item ); } ?>
            <?php wp_nonce_field( 'member_form_lostpassword', 'member_form_lostpassword_nonce' ); ?>
            <div class="last"> <input class="btn btn-login btn-block btn-lg" type="submit" id="submit" value="<?php _e( 'Submit', 'wpcom' );?>" data-loading-text="<?php _e( 'Processing...', 'wpcom' );?>"></div>
        </form>
    <?php }

    function lostpassword_form_send_success(){  ?>
        <div class="member-form lostpassword-form">
            <h3 class="lostpassword-success"><?php _e( 'Password reset email send successfully!', 'wpcom'); ?></h3>
            <p><?php _e( 'Check your email for a link to reset your password. If it doesn’t appear within a few minutes, check your spam folder.', 'wpcom'); ?></p>
        </div>
    <?php }

    function lostpassword_form_reset(){
        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
        if ( isset( $_GET['key'] ) ) {
            $value = sprintf( '%s:%s', wp_unslash( $_GET['login'] ), wp_unslash( $_GET['key'] ) );
            setcookie( $rp_cookie, $value, 0, '/', COOKIE_DOMAIN, is_ssl(), true );
            wp_safe_redirect( remove_query_arg( array( 'key', 'login' ) ) );
            exit;
        }

        if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
            list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
            $user = check_password_reset_key( $rp_key, $rp_login );
        } else {
            $user = false;
        }

        if( ! $user || is_wp_error( $user ) ){
            setcookie( $rp_cookie, ' ', time() - YEAR_IN_SECONDS, '/', COOKIE_DOMAIN, is_ssl(), true );
            if ( $user && $user->get_error_code() === 'expired_key' )
                $error = __('Your password reset link has expired', 'wpcom');//'您的密码重置链接已过期，请重新请求新链接。';
            else
                $error = __('Your password reset link appears to be invalid', 'wpcom');//'您的密码重设链接无效，请重新请求新链接。';
            ?>
            <div class="member-form lostpassword-form">
                <h3 class="lostpassword-failed"><?php _e('Password reset link invalid', 'wpcom');?></h3>
                <p><?php echo $error; ?><br>
                    <a href="<?php echo wp_lostpassword_url(); ?>"><?php _e('Click here to resend password reset email', 'wpcom');?></a></p>
            </div>
        <?php }else{
            $items = apply_filters( 'wpcom_resetpassword_form_items', array() );
            ksort($items);?>
            <form id="resetpassword-form" class="member-form resetpassword-form lostpassword-form j-member-form" method="post">
                <div class="wpcom-errmsg j-errmsg"></div>
                <?php foreach ( $items as $item ){ echo $this->login_field_item( $item ); } ?>
                <?php wp_nonce_field( 'member_form_resetpassword', 'member_form_resetpassword_nonce' ); ?>
                <div class="last"> <input class="btn btn-login btn-block btn-lg" type="submit" id="submit" value="<?php _e( 'Submit', 'wpcom' );?>" data-loading-text="<?php _e( 'Processing...', 'wpcom' );?>"></div>
            </form>
        <?php }
    }

    function lostpassword_form_finished(){ ?>
        <div class="member-form lostpassword-form">
            <h3 class="lostpassword-success"><?php _e('Password reset successfully', 'wpcom');?></h3>
            <p><?php _e('Your password has been reset successfully! ', 'wpcom');?><br>
            <a href="<?php echo wpcom_login_url();?>"><?php _e(' Click here to return to the login page', 'wpcom');?></a></p>
        </div>
    <?php }

    function login_field_item( $args ){
        global $options;
        $html = '';
        if( $args && isset($args['type']) ){
            $label = isset($args['label']) ? $args['label'] : '';
            $name = isset($args['name']) ? $args['name'] : '';
            $icon = isset($args['icon']) ? $args['icon'] : '';
            $require = isset($args['require']) ? $args['require'] : false;
            $maxlength = isset($args['maxlength']) ? $args['maxlength'] : '';
            $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
            $validate = isset($args['validate']) ? $args['validate'] : '';

            switch ($args['type']) {
                case 'noCaptcha':
                    if( isset($options['nc_appkey']) && $options['nc_appkey']!='' && $options['nc_access_id']!=''  && $options['nc_access_secret']!='' ) {
                        $html = '<div class="form-group"><div id="j-nc" class="nc-container"></div></div><input type="hidden" id="csessionid" name="csessionid"><input type="hidden" id="sig" name="sig"><input type="hidden" id="token" name="token"><input type="hidden" id="scene" name="scene">';
                    }
                    break;
                case 'password':
                case 'text':
                case 'default':
                    $html = '<div class="form-group"><label><i class="fa fa-'.$icon.'"></i> <input type="'.$args['type'].'" class="form-input'.($require?' require':'').'" id="'.$name.'" name="'.$name.'" placeholder="'.$placeholder.'"'.($maxlength?' maxlength="'.$maxlength.'"':'').($validate?' data-rule="'.$validate.'"':'').' data-label="'.$label.'"></label></div>';
                    break;
            }
        }
        return $html;
    }

    function shortcode_form( $atts ){
        if( isset($atts['action']) && $atts['action'] ){
            global $options;
            $member_reg_active = isset($options['member_reg_active']) && $options['member_reg_active'] ? $options['member_reg_active']: 0;

            if( $atts['action'] == 'register' && $member_reg_active=='1' && isset($_GET['approve']) && $_GET['approve'] ){
                if( $_GET['approve'] =='false' ){
                    $atts['notice'] = isset($options['member_reg_notice']) && $options['member_reg_notice'] ? $options['member_reg_notice']: '';
                } else if($_GET['approve'] =='pending' && isset($_GET['login']) && isset($_GET['key']) ) {
                    $login = wp_unslash( $_GET['login'] );
                    $key = wp_unslash( $_GET['key'] );
                    if( $login && $key ){
                        $user = check_password_reset_key( $key, $login );
                        if( !$user || is_wp_error($user) ) {
                            if ( $user && $user->get_error_code() === 'expired_key' )
                                $error = __( 'Your activation link has expired.', 'wpcom' );
                            else
                                $error = __( 'Your activation link is invalid.', 'wpcom' );

                            $resend_url = add_query_arg( 'approve', 'resend', wpcom_register_url() );
                            $atts['notice'] = $error . '<p><a href="'.$resend_url.'">'.__( 'Resend activation email', 'wpcom' ).'</a></p>';
                        }else if( $user->ID ) {
                            update_user_meta( $user->ID, 'wpcom_approve', 1 );
                            $url = wpcom_register_url();
                            $url = add_query_arg( 'approve', 'true', $url );
                            wp_redirect( $url );
                            exit;
                        }else{
                            exit;
                        }
                    }else{
                        exit;
                    }
                } else if($_GET['approve'] =='true') {
                    $atts['notice'] = __( 'Your account has been activated successfully.', 'wpcom' );
                    $atts['notice'] .= '<p><a href="'.wpcom_login_url().'">'.__( 'Click here to login', 'wpcom' ).'</a></p>';
                } else if($_GET['approve'] =='resend') {
                    return $this->load_template('approve-resend', $atts);
                }
                return $this->load_template('approve-notice', $atts);
            }else if( $atts['action'] == 'register' && $member_reg_active=='2' && isset($_GET['approve']) && $_GET['approve'] == 'false' ){
                $atts['notice'] = isset($options['member_reg_notice']) && $options['member_reg_notice'] ? $options['member_reg_notice']: '';
                return $this->load_template('approve-notice', $atts);
            } else{
                return $this->load_template($atts['action'], $atts);
            }
        }
    }

    function social_login(){
        $socials = apply_filters( 'wpcom_socials', array() );
        ksort($socials);
        if( $socials ){ ?>
            <ul class="member-social-list">
                <?php foreach ( $socials as $social ){ if( $social['id'] && $social['key'] ) { ?>
                <li class="social-item social-<?php echo $social['name'];?>">
                    <a href="<?php echo esc_url(wpcom_social_login_url($social['name']));?>" target="_blank"><i class="fa fa-<?php echo $social['icon'];?>"></i> <?php echo $social['title'];?></a>
                </li>
                <?php } } ?>
            </ul>
        <?php }
    }

    function approve_resend_form(){
        $items = apply_filters( 'wpcom_approve_resend_form_items', array() );
        ksort($items);?>
        <div class="wpcom-errmsg j-errmsg"></div>
        <form id="approve_resend-form" class="member-form j-member-form" method="post">
            <?php foreach ( $items as $item ){ echo $this->login_field_item( $item ); } ?>
            <?php wp_nonce_field( 'member_form_approve_resend', 'member_form_approve_resend_nonce' ); ?>
            <div class="last"> <input class="btn btn-login btn-block btn-lg" type="submit" id="submit" value="<?php _e( 'Resend activation email', 'wpcom' );?>" data-loading-text="<?php _e( 'Loading...', 'wpcom' );?>"></div>
        </form>
    <?php }

    function load_template( $template, $atts ) {
        $theme_file = get_stylesheet_directory() . '/member/' . $template . '.php';
        if (file_exists($theme_file)) {
            $file = $theme_file;
        }else{
            $file = $this->member_path . '/templates/' . $template . '.php';
        }

        if ( file_exists( $file ) ) {
            extract($atts);
            ob_start();
            include $file;
            $output = ob_get_contents();
            ob_end_clean();
            return $output;
        }
    }

    function cropped_upload(){
        $nonce = $_POST['nonce'];
        $res = array();
        $res['result'] = '';

        if ( ! wp_verify_nonce( $nonce, 'wpcom_cropper' ) )
            $res['result'] = -1;

        if( $res['result']=='' ) {
            $user = wp_get_current_user();
            if ($user->ID) {
                $img = isset($_POST['image']) ? $_POST['image'] : '';
                $type = isset($_POST['type']) ? $_POST['type'] : 0;
                $type = $type ? $type : 0; // 0: 头像； 1: 封面
                $uid = isset($_POST['user']) ? $_POST['user'] : 0;
                $corp_user = $user->ID;
                if ($uid && $uid != $user->ID && current_user_can('edit_users')) {
                    $corp_user = $uid;
                }

                $GLOBALS['image_type'] = $type;

                $filename = substr(md5($corp_user), 5, 16) . '.' . time() . '.jpg';
                $mirror = wp_upload_bits($filename, '', base64_decode(str_replace('data:image/jpeg;base64,', '', $img)), '1234/06');
                if (!$mirror['error']) {
                    $res['result'] = 1;
                    $res['url'] = $mirror['url'];

                    $key = $type ? 'wpcom_cover' : 'wpcom_avatar';
                    $pre_img = get_user_meta($corp_user, $key, 1);
                    $uploads = wp_upload_dir();
                    if ($pre_img) {
                        $pre_img = str_replace($uploads['baseurl'], '', $pre_img);
                        @unlink($uploads['basedir'] . $pre_img);
                    }
                    update_user_meta($corp_user, $key, str_replace($uploads['baseurl'], '', $res['url']));
                } else {
                    $res['result'] = -2;
                }
            } else {
                $res['result'] = -3;
            }
        }

        echo json_encode($res);
        exit;
    }

    function action_before_echo(){
        global $wp_query, $options;
        $user = wp_get_current_user();
        if ( is_wpcom_member_page( 'account' ) ) {
            $subpage = isset($wp_query->query['subpage']) && $wp_query->query['subpage'] ? $wp_query->query['subpage'] : 'general';
            // 登录判断
            if(!$user->ID){
                wp_redirect( wpcom_login_url( wpcom_subpage_url( $subpage ) ) );
                exit;
            }

            if( $_SERVER['REQUEST_METHOD'] == 'POST' ){ //表单提交
                do_action( 'wpcom_account_' . $subpage . '_post' );
            }else if( $subpage == 'logout' ){
                wp_logout();
                exit;
            }
        } else if( is_wpcom_member_page('profile') ){
            $user_slug = isset($wp_query->query['user']) && $wp_query->query['user'] ? $wp_query->query['user'] : '';

            if( $user_slug && isset($options['member_user_slug']) && $options['member_user_slug']=='2' ) {
                $profile = get_user_by( 'ID', $user_slug );
            } elseif( $user_slug ) {
                $profile = get_user_by( 'slug', $user_slug );
            }

            if( !$user_slug && $profile = wp_get_current_user() ){
                if( $profile->ID ){
                    wp_redirect( wpcom_author_url( $profile->ID, $profile->user_nicename ) );
                    exit;
                }
            }

            if( !$user_slug || !isset($profile) || !$profile ) {
                $wp_query->set_404();
                status_header(404);
            } else {
                $GLOBALS['profile'] = $profile;
            }
        } else if( $user->ID && (is_wpcom_member_page( 'login' ) || is_wpcom_member_page( 'register' ) ) ){
            wp_redirect( wpcom_subpage_url() );
            exit;
        } else if( is_wpcom_member_page('login') && ( !isset($options['login_redirect']) || $options['login_redirect']=='') ){
            $redirect_url = isset( $_SERVER['HTTP_REFERER'] ) && $_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : '';
            if( !isset($_GET['redirect_to']) && $redirect_url ){
                $pu = parse_url($redirect_url);
                if(isset($pu['query']) && $pu['query']){
                    parse_str( $pu['query'],$data );
                    if( isset($data['redirect_to']) && $data['redirect_to'] ){
                        $redirect_url = $data['redirect_to'];
                    }
                }
                $site_domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
                $red_domain = parse_url($redirect_url, PHP_URL_HOST);
                if( $site_domain == $red_domain ) {
                    // 去除域名，改成路径，防止某些服务器安全配置导致无法识别的问题
                    $redirect_url = preg_replace('/^(http|https):\/\/[^\/]+\//i', '/', $redirect_url);
                    wp_redirect(wpcom_login_url($redirect_url));
                    exit;
                }
            }
        }
    }

    function register_url( $url ){
        if( $register_url = wpcom_register_url() ){
            $url = $register_url;
        }
        return $url;
    }
    function login_url( $url, $redirect ){
        if( $login_url = wpcom_login_url($redirect) ){
            $url = $login_url;
        }
        return $url;
    }

    function logout_url( $url, $redirect ){
        if( $logout_url = wpcom_logout_url($redirect) ){
            $url = $logout_url;
        }
        return $url;
    }

    function lostpassword_url( $url, $redirect ){
        if( $lostpassword_url = wpcom_lostpassword_url($redirect) ){
            $url = $lostpassword_url;
        }
        return $url;
    }

    function author_link( $link, $author_id, $author_nicename ){
        if( $author_link = wpcom_author_url( $author_id, $author_nicename ) ){
            $link = $author_link;
        }
        return $link;
    }

    function block_access_wpadmin(){
        global $current_user, $pagenow;
        $can_access = array( 'admin-ajax.php', 'async-upload.php', 'media-upload.php');
        if( in_array($pagenow, $can_access) ) return false;
        if($current_user->ID) {
            $group = wpcom_get_user_group($current_user->ID);
            if($group){
                $wpadmin = get_term_meta($group->term_id, 'wpcom_wpadmin', true);
                if (!(current_user_can('manage_options') || $wpadmin == '1')) {
                    wp_redirect(home_url());
                    exit;
                }
            }else if( !current_user_can('manage_options') ){
                wp_redirect(home_url());
                exit;
            }
        }
    }

    function body_class( $classes ){
        if( is_wpcom_member_page('account') ){
            $classes[] = 'wpcom-member member-account';
        }else if( is_wpcom_member_page('profile') ){
            $classes[] = 'wpcom-member member-profile';
        }else if( is_wpcom_member_page('login') ){
            $classes[] = 'wpcom-member member-login';
        }else if( is_wpcom_member_page('register') ){
            $classes[] = 'wpcom-member member-register';
        }else if( is_wpcom_member_page('lostpassword') ){
            $classes[] = 'wpcom-member member-lostpassword';
        }
        return $classes;
    }

    function show_admin_bar( $show ){
        global $current_user;
        if($current_user->ID) {
            $group = wpcom_get_user_group($current_user->ID);
            if($group) {
                $adminbar = get_term_meta($group->term_id, 'wpcom_adminbar', true);
                if ($adminbar != '1') {
                    $show = false;
                }
            }else if( !current_user_can('edit_published_posts') ){
                $show = false;
            }
        }
        return $show;
    }

    function user_has_cap( $allcaps, $caps, $args, $user ){
        global $pagenow, $current_user, $options, $cap_checked;
        if( !isset($cap_checked) ) $cap_checked = array();
        if( $user->ID && in_array($user->ID, $cap_checked) ) return $allcaps;

        if( $user->ID && ( $pagenow=='user-edit.php' || $pagenow=='users.php' || is_wpcom_member_page() ) ) {
            $cap_checked[] = $user->ID;
            // 自己是超级管理员的话，不能取消自己的超级管理员权限
            if( $current_user->ID && $current_user->ID==$user->ID && is_super_admin( $user->ID ) ) return $allcaps;

            $group = wpcom_get_user_group($user->ID);

            if($group) {
                $allcaps = $this->set_default_role($user->ID, $group->term_id);
            }else if( isset($options['member_group']) && $options['member_group'] ){
                // 无用户组则分配默认用户组
                wp_set_object_terms( $user->ID, array( (int)$options['member_group'] ), 'user-groups', false );
            }
        }
        return $allcaps;
    }

    function user_register( $user_id ){
        global $options;
        if( isset($options['member_group']) && $options['member_group'] ){
            // 分配默认用户组
            wp_set_object_terms( $user_id, array( (int)$options['member_group'] ), 'user-groups', false );

            // 分配默认系统角色
            $this->set_default_role($user_id);
        }
        if( isset($options['member_reg_active']) && $options['member_reg_active']!='0' ){
            // 注册用户需要验证
            update_user_meta( $user_id, 'wpcom_approve', 0 );

            if( !session_id() ) session_start();
            if( !isset($_SESSION['user']) ) { // 非社交登录渠道
                if ($options['member_reg_active'] == '1') { // 如果是邮件激活方式，则发送激活邮件给用户
                    wpcom_send_active_email($user_id);
                } else if ($options['member_reg_active'] == '2') { // 如果是后台审核，则发送审核邮件给管理员
                    wpcom_send_active_to_admin($user_id);
                }
            }
        }
    }

    private function set_default_role($user_id, $term_id=''){
        global $options;
        if( $term_id || (isset($options['member_group']) && $options['member_group']) ) {
            $term_id = $term_id ? $term_id : $options['member_group'];
            $user = get_user_by('ID', $user_id);
            $sys_role = get_term_meta($term_id, 'wpcom_sys_role', true);
            $default_roles = array('subscriber', 'contributor', 'author', 'editor', 'administrator');
            $roles = $user->roles;
            if (!$roles) $roles = array();
            if (in_array($sys_role, $default_roles) && !in_array($sys_role, $roles)) { // 权限和当前用户组权限不一样
                foreach ($roles as $role) {
                    if (in_array($role, $default_roles)) {
                        $user->remove_role($role);
                    }
                }
                if (in_array($sys_role, $default_roles)) $user->add_role($sys_role);
            }
            return $user->allcaps;
        }
    }

    function social_new_user( $user_id ){
        global $options;
        $this->set_default_role($user_id);
        if( isset($options['member_reg_active']) && $options['member_reg_active']!='0' ){
            // 注册用户需要验证的情况，对社交登录注册的用户默认验证审核通过
            update_user_meta( $user_id, 'wpcom_approve', 1 );
        }
    }

    function authenticate( $user, $username ){
        if( $user instanceof WP_User && $username ){
            $get_user = get_user_by( 'login', $username );
            if ( ! $get_user && strpos( $username, '@' ) ) {
                $get_user = get_user_by( 'email', $username );
            }
            if( $get_user->ID ){
                global $options;
                $approve = get_user_meta( $get_user->ID, 'wpcom_approve', true );
                if( $approve=='0' && isset($options['member_reg_active']) && $options['member_reg_active']!='0' ){
                    $err = '';
                    if($options['member_reg_active']=='1'){
                        $resend_url = add_query_arg( 'approve', 'resend', wpcom_register_url() );
                        $err = sprintf( __( 'Please activate your account. <a href="%s" target="_blank">Resend activation email</a>', 'wpcom' ), $resend_url );
                    }else if($options['member_reg_active']=='2'){
                        $err = __( 'Account awaiting approval.', 'wpcom' );
                    }
                    if($err) $user = new WP_Error( 'not_approve', $err );
                }
            }
        }

        return $user;
    }

    function views_users( $views ){
        global $wpdb;
        if( !current_user_can( 'edit_users' ) ) return $views;

        $current = '';
        if ( isset($_REQUEST['status']) && $_REQUEST['status'] == 'unapproved' ) $current = 'class="current"';

        $meta_key = $wpdb->get_blog_prefix() . '_wpcom_metas';
        $users = get_users(array(
            'meta_query' => array(
                array(
                    'key' => $meta_key,
                    'value' => 's:7:"approve";i:0;',
                    'compare' => 'LIKE'
                )
            )
        ) );

        $count = count($users);

        $views[ 'unapproved' ] = '<a href="'.admin_url('users.php').'?status=unapproved" ' . $current . '>'. __( 'Unapproved', 'wpcom' ) . ' <span class="count">（'.$count.'）</span></a>';
        return $views;
    }

    function filter_users( $query ){
        global $pagenow;
        if (is_admin() && 'users.php' == $pagenow && isset($_REQUEST['status']) && $_REQUEST['status']=='unapproved' ) {
            global $wpdb;
            $query->set('meta_query', array(
                array(
                    'key' => $wpdb->get_blog_prefix() . '_wpcom_metas',
                    'value' => 's:7:"approve";i:0;',
                    'compare' => 'LIKE'
                )
            ));
        }
        return $query;
    }

    function bulk_actions_users( $actions ){
        if( current_user_can( 'edit_users' ) ) {
            $actions['approve'] = __('Approve', 'wpcom');
            $actions['disapprove'] = __('Disapprove', 'wpcom');
        }
        return $actions;
    }

    function handle_bulk_actions_users( $redirect_to, $doaction, $ids ){
        if( !$ids || !current_user_can( 'edit_users' ) ) return $redirect_to;
        if( $doaction=='approve' ){
            foreach ( $ids as $id ){
                update_user_meta( $id, 'wpcom_approve', 1 );
            }
        }else if( $doaction=='disapprove' ){
            foreach ( $ids as $id ){
                update_user_meta( $id, 'wpcom_approve', 0 );
            }
        }
        return $redirect_to;
    }

    function user_meta_updated( $user_id, $key, $value, $pre_value ){
        global $options;
        // 后台管理员审核，发送审核通过邮件
        if( $key == 'wpcom_approve' && !$pre_value && $value == 1 && isset($options['member_reg_active']) && $options['member_reg_active']=='2' ){
            wpcom_send_actived_email( $user_id );
        }
    }

    function disable_default_register(){
        $url = wpcom_register_url();
        if($url){
            wp_redirect( $url );
            exit;
        }
    }

    function user_registered( $columns ) {
        $columns['registered'] = __('Registered', 'wpcom');
        return $columns;
    }

    function user_registered_value( $val, $column_name, $user_id ) {
        $user = get_user_by( 'ID', $user_id );
        $date_formatted = new DateTime($user->user_registered);

        switch ($column_name) {
            case 'registered' :
                return $date_formatted->format('Y.m.d H:i:s');
                break;
            default:
        }
        return $val;
    }

    function user_registered_sortable( $columns ){
        $columns['registered'] = 'registered';
        return $columns;
    }
}

$GLOBALS['wpcom_member'] = new WPCOM_Member();