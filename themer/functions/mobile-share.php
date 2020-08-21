<?php
defined( 'ABSPATH' ) || exit;

add_action('wp_ajax_wpcom_mobile_share', 'wpcom_mobile_share');
add_action('wp_ajax_nopriv_wpcom_mobile_share', 'wpcom_mobile_share');
function wpcom_mobile_share(){
    global $options, $post;
    if(isset($_POST['id']) && $_POST['id'] && $post = get_post($_POST['id'])){
        setup_postdata( $post );
        $img_url = WPCOM::thumbnail_url($post->ID);
        $share_head = $img_url ? $img_url : (isset($options['wx_thumb']) ? $options['wx_thumb'] : '');
        $share_logo = isset($options['mobile_share_logo']) && $options['mobile_share_logo'] ? $options['mobile_share_logo'] : $options['logo'];
        $excerpt = rtrim( trim( strip_tags( apply_filters( 'the_excerpt', get_the_excerpt() ) ) ), '[原文链接]');
        $excerpt = preg_replace('/\\s+/', ' ', $excerpt );

        $res = array(
            'head' => wpcom_image_to_base64($share_head),
            'logo' => wpcom_image_to_base64($share_logo),
            'title' => $post->post_title,
            'excerpt' => $excerpt,
            'timestamp' => get_post_time('U', true)
        );

        wp_reset_postdata();

        echo wp_json_encode($res);
        exit;
    }
}

function wpcom_image_to_base64( $image ){
    $site_domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);
    $img_domain = parse_url($image, PHP_URL_HOST);
    if ( $img_domain != $site_domain ) {
        $http_options = array(
            'httpversion' => '1.0',
            'timeout' => 20,
            'redirection' => 20,
            'sslverify' => FALSE,
            'user-agent' => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0; MALC)'
        );
        if(preg_match('/^\/\//i', $image)) $image = 'http:' . $image;
        $get = wp_remote_get($image, $http_options);
        if (!is_wp_error($get) && 200 === $get ['response'] ['code']) {
            $img_base64 = 'data:' . $get['headers']['content-type'] . ';base64,' . base64_encode($get ['body']);
            return $img_base64;
        }
    }
    $image = preg_replace('/^(http:|https:)/i', '', $image);
    return $image;
}