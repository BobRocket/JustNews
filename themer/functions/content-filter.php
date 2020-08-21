<?php
defined( 'ABSPATH' ) || exit;

function filter_content($content=''){
    $sec = array();
    $html = '';
    preg_match_all('/<\w+\s?[^\>]*>={3,}([\s\S]*?)={3,}<\/\w+>/im', $content, $matches);
    $title = $matches[1];
    if(count($title)>0){
        for($i=0; $i<count($matches[0]); $i++){
            $f1 = str_replace('</','<\/',$matches[0][$i]);
            $f2 = isset($matches[0][$i+1]) ? str_replace('</','<\/',$matches[0][$i+1]) : '$';
            preg_match('/('.$f1.')([\s\S]*?)('.$f2.')/i', $content, $matches2);
            $sec[] = $matches2[2];
        }
        $html .='<ul class="entry-tab clearfix">';
        for($x=0; $x<count($title); $x++){
            $html .='<li class="entry-tab-item'.($x==0?' active':'').'">'.$title[$x].'</li>';
        }
        $html .= '</ul>';
        for($y=0; $y<count($sec); $y++){
            $html .='<div class="entry-tab-content'.($y==0?' active':'').'">'.$sec[$y].'</div>';
        }
        $content_array = explode($matches[0][0], $content);

        if( !empty($content_array[0]) ) $html = $content_array[0].$html;
    }else{
        $html = $content;
    }
    return $html;
}

add_filter( 'the_content', 'the_content_filter_images', 100 );
function the_content_filter_images( $content ) {
    if ( is_feed() || is_admin()
        || intval( get_query_var( 'print' ) ) == 1
        || intval( get_query_var( 'printpage' ) ) == 1
        || ( defined('DOING_AJAX') && DOING_AJAX )
        || ! is_singular()
    ) {
        return $content;
    }

    global $options, $post;
    $lazy_img = isset($options['lazyload_img']) && $options['lazyload_img'] ? $options['lazyload_img'] : FRAMEWORK_URI . '/assets/images/lazy.png';

    $respReplace = 'data-srcset=';

    $matches = array();
    $skip_images_regex = '/class=(\'|\'([^\']*)\s*|"|"([^"]*)\s*)j-lazy(\'|\s*([^\']*)\'|"|\s*([^"]*)")/';
    $placeholder_image = $lazy_img;

    preg_match_all( '/<img[^>].*?>/i', $content, $matches );

    $search = array();
    $replace = array();
    foreach ( $matches[0] as $imgHTML ) {
        if( in_array($imgHTML, $search) ){ continue; }
        $replaceHTML = $imgHTML;
        if( WPCOM::is_spider() ) {
            if(!isset($options['post_img_alt']) || $options['post_img_alt']=='1')
                $replaceHTML = filter_images_add_alt( $replaceHTML, esc_attr($post->post_title) );
        } else if ( ! ( preg_match( $skip_images_regex, $imgHTML ) ) ) {
            if(!isset($options['post_img_lazyload']) || $options['post_img_lazyload']=='1'){
                $noscriptHTML = '<noscript>'.$imgHTML.'</noscript>';
                $replaceHTML = preg_replace( '/<img(.*?)src=([\'"])/i', '<img$1src=$2' . $placeholder_image . '$2 data-original=$2', $imgHTML );
                $replaceHTML = preg_replace( '/srcset=/i', $respReplace, $replaceHTML );
                $replaceHTML = filter_images_add_class( $replaceHTML, 'j-lazy' );
            }

            if(!isset($options['post_img_alt']) || $options['post_img_alt']=='1') {
                $replaceHTML = filter_images_add_alt( $replaceHTML, esc_attr($post->post_title) );
                if( isset($noscriptHTML) )
                    $noscriptHTML = filter_images_add_alt($noscriptHTML, esc_attr($post->post_title));
            }
        }

        if( isset($noscriptHTML) ) $replaceHTML = $noscriptHTML.$replaceHTML;

        array_push( $search, $imgHTML );
        array_push( $replace, $replaceHTML );
    }
    $content = str_replace( $search, $replace, $content );
    return $content;
}

function filter_images_add_class( $image = '', $newClass ) {
    $pattern = '/class=[\'"]([^\'"]*)[\'"]/';
    // Class attribute set.
    if ( preg_match( $pattern, $image, $matches ) ) {
        $definedClasses = explode( ' ', $matches[1] );
        if ( ! in_array( $newClass, $definedClasses ) ) {
            $definedClasses[] = $newClass;
            $image = preg_replace('/(class=[\'"])([^\'"]*)([\'"])/', '$1'.implode( ' ', $definedClasses ).'$3', $image);
        }
        // Class attribute not set.
    } else {
        $image = preg_replace( '/(\<.+\s)src=([\'"])(.+\s)/', sprintf( '$1class=$2%s$2 src=$2$3', $newClass ), $image );
    }
    return $image;
}

function filter_images_add_alt( $image = '', $alt='' ){
    $pattern = '/alt=[\'"]([^\'"]*)[\'"]/';
    // alt attribute set.
    if ( preg_match( $pattern, $image, $matches ) ) {
        if ( ! trim($matches[1]) ) {
            $image = preg_replace( '/(\<.+\s)alt=([\'"])(.+\s)/', sprintf( '$1alt=$2%s$3', $alt ), $image );
        }
    } else { // alt attribute not set.
        $image = preg_replace( '/(\<.+\s)src=([\'"])(.+\s)/', sprintf( '$1alt=$2%s$2 src=$2$3', $alt ), $image );
    }
    return $image;
}

add_filter( 'the_content', 'wpcom_auto_keyword_link', 10 );
function wpcom_auto_keyword_link( $content ){
    $content = wpcom_tag_link( $content );
    $content = wpcom_keyword_link( $content );
    return $content;
}

function wpcom_keyword_link( $content ){
    global $options;
    if( isset($options['kl_keyword']) && $options['kl_keyword'] && $options['kl_keyword'][0] ) {
        foreach ($options['kl_keyword'] as $i => $keyword){
            if( !$options['kl_link'][$i] ) continue;

            //如果链接是当前页面,则跳过.
            $http_type = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
            $recent_url = $http_type . $_SERVER['SERVER_NAME'] . $_SERVER["REQUEST_URI"];
            if (esc_url($options['kl_link'][$i]) == $recent_url) continue;

            //跳过页面
            if (is_page()) continue;

            $cleankeyword = stripslashes($keyword);
            $url = '<span class="wpcom_keyword_link">';
            $url .= '<a href="'.esc_url($options['kl_link'][$i]).'" title="'.esc_attr($options['kl_title'][$i]?$options['kl_title'][$i]:$keyword).'"';
            if($options['kl_newwindow'][$i]) $url .= ' target="_blank"';
            if($options['kl_nofollow'][$i]) $url .= ' rel="nofollow"';
            $url .= '>' . addcslashes($cleankeyword, '$') . '</a>';
            $url .= '</span>';
            $limit = 1;//rand(1, 3);
            $case = "";
            $zh_CN = "1";

            // we don't want to link the keyword if it is already linked.
            $ex_word = preg_quote($cleankeyword, '\'');
            //ignore pre
            if ($pre_num = preg_match_all("/<pre.*?>.*?<\/pre>/is", $content, $ignore_pre))
                for ($i = 1; $i <= $pre_num; $i++)
                    $content = preg_replace("/<pre.*?>.*?<\/pre>/is", "%ignore_pre_$i%", $content, 1);

            $content = preg_replace('|(<img)([^>]*)(' . $ex_word . ')([^>]*)(>)|U', '$1$2%&&&&&%$4$5', $content);

            // For keywords with quotes (') to work, we need to disable word boundary matching
            $cleankeyword = preg_quote($cleankeyword, '\'');
            if ($zh_CN) {
                $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            } elseif (strpos($cleankeyword, '\'') > 0) {
                $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            } else {
                $regEx = '\'(?!((<.*?)|(<a.*?)))(\b' . $cleankeyword . '\b)(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
            }
            $content = preg_replace($regEx, $url, $content, $limit);

            //change our '%&&&&&%' things to $cleankeyword.
            $content = str_replace('%&&&&&%', stripslashes($ex_word), $content);

            //ignore pre
            if ($ignore_pre) {
                for ($i = 1; $i <= $pre_num; $i++) {
                    $content = str_replace("%ignore_pre_$i%", $ignore_pre[0][$i - 1], $content);
                }
            }
        }
    }
    return $content;
}

function wpcom_tag_link( $content ){
    global $options;
    if( isset($options['auto_tag_link']) && $options['auto_tag_link']=='1' ) {
        $posttags = get_the_tags();

        if ($posttags) {
            usort($posttags, "wpcom_sort_by_len");
            foreach ($posttags as $tag) {
                $link = get_tag_link($tag->term_id);
                $keyword = $tag->name;

                //如果链接是当前页面,则跳过.
                $http_type = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';
                $recent_url = $http_type . $_SERVER['SERVER_NAME'] . $_SERVER["REQUEST_URI"];
                if ($link == $recent_url) continue;

                //跳过页面
                if (is_page()) continue;

                $cleankeyword = stripslashes($keyword);
                $url = '<span class="wpcom_tag_link">';
                $url .= '<a href="'.$link.'" title="' . str_replace('%s', addcslashes($cleankeyword, '$'), __('%s')) . '"';
                $url .= ' target="_blank"';
                $url .= '>' . addcslashes($cleankeyword, '$') . '</a>';
                $url .= '</span>';
                $limit = 1;//rand(1, 3);
                $case = "";
                $zh_CN = "1";

                // we don't want to link the keyword if it is already linked.
                $ex_word = preg_quote($cleankeyword, '\'');
                //ignore pre
                if ($pre_num = preg_match_all("/<pre.*?>.*?<\/pre>/is", $content, $ignore_pre))
                    for ($i = 1; $i <= $pre_num; $i++)
                        $content = preg_replace("/<pre.*?>.*?<\/pre>/is", "%ignore_pre_$i%", $content, 1);

                $content = preg_replace('|(<img)([^>]*)(' . $ex_word . ')([^>]*)(>)|U', '$1$2%&&&&&%$4$5', $content);

                // For keywords with quotes (') to work, we need to disable word boundary matching
                $cleankeyword = preg_quote($cleankeyword, '\'');
                if ($zh_CN) {
                    $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
                } elseif (strpos($cleankeyword, '\'') > 0) {
                    $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
                } else {
                    $regEx = '\'(?!((<.*?)|(<a.*?)))(\b' . $cleankeyword . '\b)(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
                }
                $content = preg_replace($regEx, $url, $content, $limit);

                //change our '%&&&&&%' things to $cleankeyword.
                $content = str_replace('%&&&&&%', stripslashes($ex_word), $content);

                //ignore pre
                if ($ignore_pre) {
                    for ($i = 1; $i <= $pre_num; $i++) {
                        $content = str_replace("%ignore_pre_$i%", $ignore_pre[0][$i - 1], $content);
                    }
                }
            }
        }
    }
    return $content;
}

function wpcom_sort_by_len($a, $b){
    if ( $a->name == $b->name ) return 0;
    return ( strlen($a->name) > strlen($b->name) ) ? -1 : 1;
}