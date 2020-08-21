<?php
defined( 'ABSPATH' ) || exit;

add_action('wp_head', 'wpcom_seo');
function wpcom_seo(){
    global $options, $post;
    $keywords = '';
    $description = '';
    if(!isset($options['seo']) || $options['seo']=='1') {
        if(!isset($options['seo'])){
            $options['keywords'] = '';
            $options['description'] = '';
            $options['fav'] = '';
        }
        if (is_home() || is_front_page()) {
            $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($options['keywords']))));
            $description = esc_attr(trim(strip_tags($options['description'])));
        } else if (is_singular()) {
            $keywords = str_replace('，', ',', esc_attr(trim(strip_tags(get_post_meta( $post->ID, 'wpcom_seo_keywords', true)))));
            if($keywords=='' && is_singular('post')){
                $post_tags = get_the_tags();
                if ($post_tags) {
                    foreach ($post_tags as $tag) {
                        $keywords = $keywords . $tag->name . ",";
                    }
                }
                $keywords = rtrim($keywords, ',');
            } else if($keywords=='' && is_singular('page')) {
                $keywords = $post->post_title;
            }else if(is_singular('product')){
                $product_tag = get_the_terms( $post->ID, 'product_tag' );
                if ($product_tag) {
                    foreach ($product_tag as $tag) {
                        $keywords = $keywords . $tag->name . ",";
                    }
                }
                $keywords = rtrim($keywords, ',');
            }
            $description = esc_attr(trim(strip_tags(get_post_meta( $post->ID, 'wpcom_seo_description', true))));
            if($description=='') {
                if ($post->post_excerpt) {
                    $description = esc_attr(strip_tags($post->post_excerpt));
                } else {
                    $content = preg_replace("/\[(\/?map.*?)\]/si", "", $post->post_content);

                    ob_start();
                    echo do_shortcode( $content );
                    $content = ob_get_contents();
                    ob_end_clean();

                    $content = str_replace(' ', '', trim(strip_tags($content)));
                    $content = preg_replace('/\\s+/', ' ', $content );

                    $description = utf8_excerpt($content, 200);
                }
            }
        } else if (is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            $keywords = get_term_meta( $term->term_id, 'wpcom_seo_keywords', true );
            $keywords = $keywords!='' ? $keywords : single_cat_title('', false);
            $keywords = str_replace('，', ',', esc_attr(trim(strip_tags($keywords))));

            $description = get_term_meta( $term->term_id, 'wpcom_seo_description', true );
            $description = $description!='' ? $description : term_description();
            $description = esc_attr(trim(strip_tags($description)));
        }
    }
    $seo = '<meta name="applicable-device" content="pc,mobile" />'."\n";
    $seo .= '<meta http-equiv="Cache-Control" content="no-transform" />'."\n";
    if($fav=$options['fav']){ $seo .= '<link rel="shortcut icon" href="'.$fav.'" />'."\n"; }
    if ($keywords) $seo .= '<meta name="keywords" content="' . esc_attr($keywords) . '" />' . "\n";
    if ($description) $seo .= '<meta name="description" content="' . esc_attr(trim(strip_tags($description))) . '" />' . "\n";
    if(is_singular() && !is_front_page()){
        global $paged;
        if(!$paged){$paged = 1;}
        $url = get_pagenum_link($paged);

        $img_url = WPCOM::thumbnail_url($post->ID, 'full');
        $GLOBALS['post-thumb'] = $img_url;
        if(!$img_url){
            preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', $post->post_content, $matches);
            if(isset($matches[1]) && isset($matches[1][0])){
                $img_url = $matches[1][0];
            }
        }

        $image = $img_url ? $img_url : (isset($options['wx_thumb']) ? $options['wx_thumb'] : '');

        $type = 'article';
        if(is_singular('page')){
            $type = 'webpage';
        }else if(is_singular('product')){
            $type = 'product';
        }
        $seo .= '<meta property="og:type" content="'.$type.'" />' . "\n";
        $seo .= '<meta property="og:url" content="'.$url.'" />' . "\n";
        $seo .= '<meta property="og:site_name" content="'.esc_attr(get_bloginfo( "name" )).'" />' . "\n";
        $seo .= '<meta property="og:title" content="'.esc_attr($post->post_title).'" />' . "\n";
        if($image) $seo .= '<meta property="og:image" content="'.esc_url($image).'" />' . "\n";
        if ($description) $seo .= '<meta property="og:description" content="'.esc_attr(trim(strip_tags($description))).'" />' . "\n";
    }else if (is_home() || is_front_page()) {
        global $page;
        if(!$page){$page = 1;}
        $url = get_pagenum_link($page);

        $image = isset($options['wx_thumb']) ? $options['wx_thumb'] : '';
        $title = isset($options['home-title']) ? $options['home-title'] : '';;

        if($title=='') {
            $desc = get_bloginfo('description');
            if ($desc) {
                $title = get_option('blogname') . (isset($options['title_sep_home']) && $options['title_sep_home'] ? $options['title_sep_home'] : ' - ') . $desc;
            } else {
                $title = get_option('blogname');
            }
        }


        $seo .= '<meta property="og:type" content="webpage" />' . "\n";
        $seo .= '<meta property="og:url" content="'.$url.'" />' . "\n";
        $seo .= '<meta property="og:site_name" content="'.esc_attr(get_bloginfo( "name" )).'" />' . "\n";
        $seo .= '<meta property="og:title" content="'.esc_attr($title).'" />' . "\n";
        if($image) $seo .= '<meta property="og:image" content="'.esc_url($image).'" />' . "\n";
        if ($description) $seo .= '<meta property="og:description" content="'.esc_attr(trim(strip_tags($description))).'" />' . "\n";
    } else if (is_category() || is_tag() || is_tax() ) {
        global $paged;
        if(!$paged){$paged = 1;}
        $url = get_pagenum_link($paged);
        $image = isset($options['wx_thumb']) ? $options['wx_thumb'] : '';

        $seo .= '<meta property="og:type" content="webpage" />' . "\n";
        $seo .= '<meta property="og:url" content="'.$url.'" />' . "\n";
        $seo .= '<meta property="og:site_name" content="'.esc_attr(get_bloginfo( "name" )).'" />' . "\n";
        $seo .= '<meta property="og:title" content="'.esc_attr(single_cat_title('', false)).'" />' . "\n";
        if($image) $seo .= '<meta property="og:image" content="'.esc_url($image).'" />' . "\n";
        if ($description) $seo .= '<meta property="og:description" content="'.esc_attr(trim(strip_tags($description))).'" />' . "\n";
    }

    if( ( (isset($options['xzh-appid']) && $options['xzh-appid']) || (isset($options['canonical']) && $options['canonical']=='1') ) && is_singular() ){
        $id = get_queried_object_id();
        if ( 0 !== $id && $url = wp_get_canonical_url( $id )) {
            $seo .= '<link rel="canonical" href="' . esc_url( $url ) . '" />' . "\n";
        }
    }

    echo apply_filters('wpcom_head', $seo);
}