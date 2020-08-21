<?php
defined( 'ABSPATH' ) || exit;

function wpcom_account_url(){
    global $options;
    if( isset($options['member_page_account']) && $options['member_page_account'] ){
        return get_permalink( $options['member_page_account'] );
    }else{
        return get_edit_user_link();
    }
}

function wpcom_subpage_url( $subpage = '', $page = '' ){
    global $permalink_structure, $options;
    $page_id = 0;

    if( $page && is_numeric($page) ) {
        $page_id = $page;
    }else if( isset($options['member_page_account']) && $options['member_page_account'] ){
        $page_id = $options['member_page_account'];
    }

    if($page_id){
        if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');

        $page_url = get_permalink( $page_id );
        if( $permalink_structure ) {
            $url = trailingslashit($page_url) . $subpage;
        } else {
            $url =  add_query_arg( 'subpage', $subpage, $page_url );
        }
        return $url;
    }
}

function wpcom_profile_url( $user, $subpage ){
    global $options, $permalink_structure;
    if( $user && isset($options['member_page_profile']) && $options['member_page_profile'] ){
        $page_url = wpcom_author_url( $user->ID, $user->user_nicename );
        if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');

        if( $permalink_structure ) {
            $url = $subpage!='' ? trailingslashit($page_url) . $subpage : $page_url;
        } else {
            $url =  add_query_arg( 'subpage', $subpage, $page_url );
        }
        return $url;
    }
}

function wpcom_login_url( $redirect = '' ){
    global $options;
    if( isset($options['member_page_login']) && $options['member_page_login'] ){
        $login_url = get_permalink( $options['member_page_login'] );
        if ( !empty($redirect) ){
            $redirect = preg_replace('/^(http|https):\/\/[^\/]+\//i', '/', $redirect);
            $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
        }
        return $login_url;
    }
}

function wpcom_social_login_url( $type, $action = 'login' ){
    $login_url = wpcom_login_url();
    $args = array(
        'type' => $type,
        'action' => $action
    );
    return add_query_arg( $args, $login_url );
}

function wpcom_logout_url( $redirect = '' ){
    if( $logout_url = wpcom_subpage_url( 'logout' ) ){
        if ( !empty($redirect) ){
            $redirect = preg_replace('/^(http|https):\/\/[^\/]+\//i', '/', $redirect);
            $logout_url = add_query_arg('redirect_to', urlencode($redirect), $logout_url);
        }

        return $logout_url;
    }
}

function wpcom_lostpassword_url( $redirect = '' ){
    global $options;
    if( isset($options['member_page_lostpassword']) && $options['member_page_lostpassword'] ){
        $lostpassword_url = get_permalink( $options['member_page_lostpassword'] );
        if ( !empty($redirect) ) {
            $redirect = preg_replace('/^(http|https):\/\/[^\/]+\//i', '/', $redirect);
            $lostpassword_url = add_query_arg('redirect_to', urlencode($redirect), $lostpassword_url);
        }
        return $lostpassword_url;
    }
}

function wpcom_register_url(){
    global $options;
    if( isset($options['member_page_register']) && $options['member_page_register'] ){
        return get_permalink( $options['member_page_register'] );
    }
}

function wpcom_author_url( $author_id, $author_nicename ){
    global $options, $permalink_structure;
    if( isset($options['member_page_profile']) && $options['member_page_profile'] ){
        $author_url = get_permalink( $options['member_page_profile'] );
        if(!isset($permalink_structure)) $permalink_structure = get_option('permalink_structure');

        if( isset($options['member_user_slug']) && $options['member_user_slug']=='2' ) {
            $user_slug = $author_id;
        }else if ( '' == $author_nicename ) {
            $user = get_userdata($author_id);
            if ( !empty($user->user_nicename) )
                $user_slug = $user->user_nicename;
        } else {
            $user_slug = $author_nicename;
        }

        if( $permalink_structure ) {
            $url = trailingslashit( $author_url ) . $user_slug;
        } else {
            $url =  add_query_arg( 'user', $user_slug, $author_url );
        }
        return $url;
    }
}