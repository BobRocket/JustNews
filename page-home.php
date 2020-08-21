<?php
// TEMPLATE NAME: 自定义模板
// Template Post Type: page,post

$mds = get_post_meta($post->ID, '_page_modules', true);
if(!$mds) $mds = array();
if(isset($mds[0]) && $mds[0]['type'] == 'swiper' && $mds[0]['settings']['abs']==1){
    $abs = 1;
}

get_header();
do_action('wpcom_render_page', $mds);
get_footer();