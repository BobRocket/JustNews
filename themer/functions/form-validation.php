<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wpcom_account_general_post', 'wpcom_account_form_general', 20 );
if( !function_exists( 'wpcom_account_form_general' ) ){
    function wpcom_account_form_general(){
        $res = array();
        $res['result'] = 1;
        $res['error'] = array();
        $res['value'] = array();

        $res = wpcom_form_validate( $res, 'member_form_general', 'wpcom_account_tabs_general_metas' );

        $res = apply_filters( 'wpcom_account_form_general_validate', $res );

        // 全部验证通过
        if( empty($res['error']) ){
            $user = wp_get_current_user();
            if($user->ID){
                $res['value']['ID'] = $user->ID;
                $user_id = wp_update_user( $res['value'] );
                if( is_wp_error( $user_id ) ){
                    $res['error'][$user_id->get_error_code()] = $user_id->get_error_message();
                }
            }
        } else {
            $res['result'] = 0;
        }

        $GLOBALS['validation'] = $res;
    }
}

add_action( 'wpcom_account_password_post', 'wpcom_account_form_password', 20 );
if( !function_exists( 'wpcom_account_form_password' ) ){
    function wpcom_account_form_password(){
        $res = array();
        $res['result'] = 1;
        $res['error'] = array();
        $res['value'] = array();

        $res = wpcom_form_validate( $res, 'member_form_password', 'wpcom_account_tabs_password_metas' );

        $res = apply_filters( 'wpcom_account_form_password_validate', $res );

        // 全部验证通过
        if( empty($res['error']) ){
            $user = wp_get_current_user();
            if( $user->ID && wp_check_password($res['value']['old-password'], $user->user_pass, $user->ID ) ){
                wp_set_password( $res['value']['password'], $user->ID );
                $res['value']['old-password'] = '';
                $res['value']['password'] = '';
                $res['value']['password2'] = '';

                // 更新cookie，避免重新登录
                wp_set_auth_cookie($user->ID);
                wp_set_current_user($user->ID);
            }else{
                $res['error']['old-password'] = __( 'The password is incorrect', 'wpcom' );
            }
        }else{
            $res['result'] = 0;
        }

        $GLOBALS['validation'] = $res;
    }
}

add_action( 'wp_ajax_nopriv_wpcom_login', 'wpcom_ajax_login' );
if( !function_exists( 'wpcom_ajax_login' ) ) {
    function wpcom_ajax_login(){
        global $options;
        $res = array();
        $res['result'] = 1; // 0：帐号密码错误；1：登录成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
        $res['error'] = '';

        $errors = apply_filters( 'wpcom_member_errors', array() );

        $msg = array(
            '0' => __( 'The username or password is incorrect', 'wpcom' ),
            '1' => '登录成功',
            '-1' => $errors['nonce'],
            '-2' => $errors['slide_fail'],
            '-3' => $errors['slide_verify']
        );

        $res = wpcom_form_validate( $res, 'member_form_login', 'wpcom_login_form_items' );

        $res = apply_filters( 'wpcom_login_form_validate', $res );

        if ($res['result'] == 1) {
            $login = wp_signon($_POST);
            if (is_wp_error($login)){
                $res['result'] = 0;
                if( $login->get_error_code() == 'not_approve' ){
                    $res['error'] = $login->get_error_message();
                }
            }else if( !preg_match('/redirect_to=[^\s&]/i', $_SERVER['HTTP_REFERER']) && isset($options['login_redirect']) && $options['login_redirect'] != '' ){
                $res['redirect_to'] = $options['login_redirect'];
            }
        }

        if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

        echo json_encode($res);
        exit;
    }
}

add_action( 'wp_ajax_nopriv_wpcom_register', 'wpcom_ajax_register' );
if( !function_exists( 'wpcom_ajax_register' ) ) {
    function wpcom_ajax_register(){
        global $options;
        $res = array();
        $res['result'] = 1; // 0：插入失败；1：登录成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
        $res['error'] = '';

        $errors = apply_filters( 'wpcom_member_errors', array() );

        $msg = array(
            //'0' => '',
            '1' => '注册成功',
            '-1' => $errors['nonce'],
            '-2' => $errors['slide_fail'],
            '-3' => $errors['slide_verify'],
            '-4' => $errors['email'],
            '-5' => $errors['password'],
            '-6' => $errors['passcheck']
        );

        if( !get_option('users_can_register') ){ // 未开启注册
            $res['result'] = 0;
            $res['error'] = __('User registration is currently not allowed.', 'wpcom');
        }else{
            $res = wpcom_form_validate( $res, 'member_form_register', 'wpcom_register_form_items' );
            $res = apply_filters( 'wpcom_register_form_validate', $res );
        }

        if ($res['result'] == 1) {
            $user_id = wp_insert_user($_POST);
            if ( is_wp_error( $user_id ) ){
                $res['error'] = $user_id->get_error_message();
                $res['result'] = 0;
            }else{
                if( isset($options['member_reg_active']) && $options['member_reg_active']!='0' ){
                    // 注册用户需要验证
                    $url = wpcom_register_url();
                    $url = add_query_arg( 'approve', 'false', $url );
                    $res['redirect_to'] = $url;
                } else {
                    wp_set_auth_cookie($user_id);
                    wp_set_current_user($user_id);
                }
            }
        }

        if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

        echo json_encode($res);
        exit;
    }
}

add_action( 'wp_ajax_wpcom_approve_resend', 'wpcom_ajax_approve_resend' );
add_action( 'wp_ajax_nopriv_wpcom_approve_resend', 'wpcom_ajax_approve_resend' );
function wpcom_ajax_approve_resend(){
    global $options;
    if( !(isset($options['member_reg_active']) && $options['member_reg_active']=='1') ){
        return 0; // 未开启邮件认证直接推出
    }

    $res = array();
    $res['result'] = 1; // 0：帐号密码错误；1：登录成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
    $res['error'] = '';

    $errors = apply_filters( 'wpcom_member_errors', array() );

    $msg = array(
        '0' => __( 'The username does not exist', 'wpcom' ),
        '1' => '提交成功',
        '-1' => $errors['nonce'],
        '-2' => $errors['slide_fail'],
        '-3' => $errors['slide_verify']
    );

    $res = wpcom_form_validate( $res, 'member_form_approve_resend', 'wpcom_approve_resend_form_items' );

    $res = apply_filters( 'wpcom_approve_resend_form_validate', $res );

    if ($res['result'] == 1) {

        if( isset( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ){
            $user_name = wp_unslash( $_POST['user_login'] );
            $user = get_user_by( 'login', $user_name );
            if ( ! $user && strpos( $user_name, '@' ) ) {
                $user = get_user_by( 'email', $user_name );
            }

            if( $user ) {
                $approve = get_user_meta( $user->ID, 'wpcom_approve', true );
                if( $approve=='0' ){
                    $resend = wpcom_send_active_email($user->ID);
                    if ($resend !== true) {
                        $res['result'] = 0;
                        $res['error'] = $resend ? $resend : __( 'Error occurs when resend email.', 'wpcom' );
                    } else {
                        $url = wpcom_register_url();
                        $url = add_query_arg( 'approve', 'false', $url );
                        $res['redirect_to'] = $url;
                    }
                } else {
                    $res['result'] = 0;
                    $res['error'] = __( 'You have already activated your account.', 'wpcom' );
                }
            } else {
                $res['result'] = 0;
            }
        }else{
            $res['result'] = 0;
        }
    }

    if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

    echo json_encode($res);
    exit;
}

add_action( 'wp_ajax_wpcom_lostpassword', 'wpcom_ajax_lostpassword' );
add_action( 'wp_ajax_nopriv_wpcom_lostpassword', 'wpcom_ajax_lostpassword' );
function wpcom_ajax_lostpassword(){
    $res = array();
    $res['result'] = 1; // 0：帐号密码错误；1：登录成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
    $res['error'] = '';

    $errors = apply_filters( 'wpcom_member_errors', array() );

    $msg = array(
        '0' => __( 'The username does not exist', 'wpcom' ),
        '1' => '提交成功',
        '-1' => $errors['nonce'],
        '-2' => $errors['slide_fail'],
        '-3' => $errors['slide_verify']
    );

    $res = wpcom_form_validate( $res, 'member_form_lostpassword', 'wpcom_lostpassword_form_items' );

    $res = apply_filters( 'wpcom_lostpassword_form_validate', $res );

    if ($res['result'] == 1) {
        if( isset( $_POST['user_login'] ) && is_string( $_POST['user_login'] ) ){
            $user_name = wp_unslash( $_POST['user_login'] );
            $user = get_user_by( 'login', $user_name );
            if ( ! $user && strpos( $user_name, '@' ) ) {
                $user = get_user_by( 'email', $user_name );
            }

            if( $user ) {
                $reset = wpcom_retrieve_password($user);
                if ($reset !== true) {
                    $res['result'] = 0;
                    $res['error'] = $reset;
                } else {
                    $res['redirect_to'] = add_query_arg('subpage', 'send_success', $_POST['_wp_http_referer']);
                }
            } else {
                $res['result'] = 0;
            }
        }else{
            $res['result'] = 0;
        }
    }

    if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

    echo json_encode($res);
    exit;
}

add_action( 'wp_ajax_wpcom_resetpassword', 'wpcom_ajax_resetpassword' );
add_action( 'wp_ajax_nopriv_wpcom_resetpassword', 'wpcom_ajax_resetpassword' );
function wpcom_ajax_resetpassword(){
    $res = array();
    $res['result'] = 1; // 0：帐号密码错误；1：登录成功；-1：nonce校验失败；-2：滑动解锁验证失败；-3：请先滑动解锁
    $res['error'] = '';

    $errors = apply_filters( 'wpcom_member_errors', array() );

    $msg = array(
        '0' => __('Reset failed, please retry!', 'wpcom'),
        '1' => '重置成功',
        '-1' => $errors['nonce'],
        '-2' => $errors['slide_fail'],
        '-3' => $errors['slide_verify']
    );

    $res = wpcom_form_validate( $res, 'member_form_resetpassword', 'wpcom_resetpassword_form_items' );

    $res = apply_filters( 'wpcom_resetpassword_form_validate', $res );

    if ($res['result'] == 1) {
        $rp_cookie = 'wp-resetpass-' . COOKIEHASH;
        if ( isset( $_COOKIE[ $rp_cookie ] ) && 0 < strpos( $_COOKIE[ $rp_cookie ], ':' ) ) {
            list( $rp_login, $rp_key ) = explode( ':', wp_unslash( $_COOKIE[ $rp_cookie ] ), 2 );
            $user = check_password_reset_key( $rp_key, $rp_login );
        } else {
            $user = false;
        }

        if ( ! $user || is_wp_error( $user ) ) {
            $res['result'] = 0;
        }else{
            reset_password($user, $_POST['password']);
            $res['redirect_to'] = add_query_arg('subpage', 'finished', $_POST['_wp_http_referer']);
        }
    }

    if ( $res['error'] == '' && isset($msg[$res['result']]) ) $res['error'] = $msg[$res['result']];

    echo json_encode($res);
    exit;
}

function wpcom_retrieve_password( $user ) {
    $user_login = $user->user_login;
    $user_email = $user->user_email;
    $key = get_password_reset_key( $user );

    if ( is_wp_error( $key ) ) {
        return __('Generate reset key error.', 'wpcom');
    }

    if ( is_multisite() ) {
        $site_name = get_network()->site_name;
    } else {
        /*
         * The blogname option is escaped with esc_html on the way into the database
         * in sanitize_option we want to reverse this for the plain text arena of emails.
         */
        $site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
    }

    $url = add_query_arg( array(
        'subpage' => 'reset',
        'key' => $key,
        'login' => rawurlencode( $user_login )
    ), wpcom_lostpassword_url() );

    $message = __( 'Someone has requested a password reset for the following account:' ) . "<br><br>";
    /* translators: %s: site name */
    $message .= sprintf( __( 'Site Name: %s'), $site_name ) . "<br>";
    /* translators: %s: user login */
    $message .= sprintf( __( 'Username: %s'), $user_login ) . "<br><br>";
    $message .= __( 'If this was a mistake, just ignore this email and nothing will happen.' ) . "<br><br>";
    $message .= __( 'To reset your password, visit the following address:' ) . "<br>";
    $message .= '<a href="'.$url.'">'.$url.'</a>' . "<br>";

    /* translators: Password reset email subject. %s: Site name */
    $title = sprintf( __( '[%s] Password Reset' ), $site_name );

    /**
     * Filters the subject of the password reset email.
     *
     * @since 2.8.0
     * @since 4.4.0 Added the `$user_login` and `$user_data` parameters.
     *
     * @param string  $title      Default email title.
     * @param string  $user_login The username for the user.
     * @param WP_User $user_data  WP_User object.
     */
    $title = apply_filters( 'retrieve_password_title', $title, $user_login, $user );

    /**
     * Filters the message body of the password reset mail.
     *
     * If the filtered message is empty, the password reset email will not be sent.
     *
     * @since 2.8.0
     * @since 4.1.0 Added `$user_login` and `$user_data` parameters.
     *
     * @param string  $message    Default mail message.
     * @param string  $key        The activation key.
     * @param string  $user_login The username for the user.
     * @param WP_User $user_data  WP_User object.
     */
    $message = apply_filters( 'retrieve_password_message', $message, $key, $user_login, $user );
    $headers = array('Content-Type: text/html; charset=UTF-8');

    if ( $message && !wp_mail( $user_email, wp_specialchars_decode( $title ), $message, $headers ) )
        return __('The email could not be sent.', 'wpcom');

    return true;
}

function wpcom_form_validate( $res, $nonce, $filter ){
    global $options;
    $_nonce = $_POST[ $nonce . '_nonce' ];

    if (!wp_verify_nonce( $_nonce, $nonce )) {
        $res['result'] = -1;
    } else {
        // 非空验证
        $items = apply_filters( $filter, array() );

        $nc = false;
        foreach( $items as $item ){
            if( $item['type']== 'noCaptcha' ) $nc = true;

            if( ! ( isset($item['disabled']) && $item['disabled'] ) && !$nc ) {
                $val = isset($_POST[$item['name']]) ? $_POST[$item['name']] : '';

                if (isset($item['require']) && $item['require']) {
                    $item['validate'] = 'require' . (isset($item['validate']) ? ' ' . $item['validate'] : '');
                }

                if (isset($item['validate']) && $item['validate']) {
                    $validate = wpcom_form_item_validate($item['validate'], $val, $item);
                    if (isset($validate['result']) && !$validate['result']) {
                        if( isset($res['value']) ){
                            // account 页面需要返回所有错误和提交的内容
                            $res['error'][$item['name']] = $validate['error'];
                        } else {
                            // 注册登录等页面有错误则返回第一条错误信息
                            $res['result'] = 0;
                            $res['error'] = $validate['error'];
                        }
                    }
                }

                if( isset($res['value']) )
                    $res['value'][$item['name']] = $val;
                else
                    if ($res['result'] != 1) break;
            }
        }


        // 验证阿里云滑动验证码
        if ( $nc && $res['result'] ==1 && isset($options['nc_appkey']) && $options['nc_appkey'] ) {
            $csessionid = $_POST['csessionid'];
            $token = $_POST['token'];
            $sig = $_POST['sig'];
            $scene = $_POST['scene'];

            if ($csessionid != '' && $token != '' && $sig != '' && $scene != '') {
                $check = wpcom_aliyun_sdk( $csessionid, $token, $sig, $scene );
                if ($check->Code == '100') {
                    // 验证通过
                } else {
                    $res['result'] = -2;
                }
            } else {
                $res['result'] = -3;
            }
        }
    }
    return $res;
}

function wpcom_form_item_validate( $validate_type, $val, $meta ){
    $types = explode(" ", $validate_type );
    $types = array_filter($types);  // 删除空元素

    $res = array();

    if($types){
        $errors = apply_filters( 'wpcom_member_errors', array() );

        foreach ( $types as $type ) {
            $type_array = explode(":", $type );
            $type = $type_array[0];
            $filter = isset($type_array[1]) ? $type_array[1] : '';

            switch ($type) {
                case 'require':
                    if (trim($val) === '') {
                        $res['result'] = false;
                        $res['error'] = $meta['label'] . $errors['require'];
                    } else {
                        $res['result'] = true;
                    }
                    break;
                case 'email':
                    $res['result'] = is_email($val);
                    if (!$res['result']) {
                        $res['error'] = $errors['email'];
                    }
                    break;
                case 'password':
                    $res['result'] = true;
                    if( $filter ){
                        $pre = $_POST[$filter];
                        if( $pre!==$val ){
                            $res['result'] = false;
                            $res['error'] = $errors['passcheck'];
                        }
                    }else{
                        if( isset($meta['maxlength']) && $meta['maxlength'] && strlen($val) > $meta['maxlength'] ) {
                            $res['result'] = false;
                        }else if( isset($meta['minlength']) && $meta['minlength'] && strlen($val) < $meta['minlength'] ){
                            $res['result'] = false;
                        }
                        if( ! $res['result'] ) $res['error'] = $errors['password'];
                    }

                    break;
            }

            if( isset($res['result']) && !$res['result'] )
                break;
        }
    }

    return $res;
}

function wpcom_aliyun_sdk( $csessionid, $token, $sig, $scene ){
    include_once FRAMEWORK_PATH . '/member/aliyun-php-sdk/load.php';
    return AfsCheckRequest($csessionid, $token, $sig, $scene);
}