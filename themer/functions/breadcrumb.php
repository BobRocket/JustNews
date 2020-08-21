<?php
/**
 * 面包屑导航功能
 */
defined( 'ABSPATH' ) || exit;

// breadcrumb
function wpcom_breadcrumb( $cls='breadcrumb container' ) {
    global $post, $wp_query;
    echo '<ol class="'.$cls.'"><li class="home"><i class="fa fa-map-marker"></i> <a href="'.get_bloginfo('url').'">'.__('Home', 'wpcom').'</a></li>';

    if ( is_category() ) {
        $cat_obj = $wp_query->get_queried_object();
        $thisCat = $cat_obj->term_id;
        $thisCat = get_category($thisCat);
        $parentCat = get_category($thisCat->parent);

        if ($thisCat->parent != 0) echo wpcom_get_category_parents($parentCat);
        echo '<li class="active">';
        single_cat_title();
        echo '</li>';
    } elseif ( is_day() ) {
        echo '<li><a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a> </li>';
        echo '<li><a href="' . get_month_link(get_the_time('Y'),get_the_time('m')) . '">' . get_the_time('F') . '</a> </li>';
        echo '<li class="active">' . get_the_time('d') . '</li>';
    } elseif ( is_month() ) {
        echo '<li><a href="' . get_year_link(get_the_time('Y')) . '">' . get_the_time('Y') . '</a> </li>';
        echo '<li class="active">' . get_the_time('F') . '</li>';
    } elseif ( is_year() ) {
        echo '<li class="active">' . get_the_time('Y') . '</li>';
    } elseif( is_attachment() ) {
        echo '<li class="active">';
        the_title();
        echo '</li>';
    }elseif ( is_single() ) {
        $post_type = get_post_type();
        if($post_type == 'post'){
            $cat = get_the_category();
            $cat = isset($cat[0]) ? $cat[0] : 0;

            echo wpcom_get_category_parents($cat);
        }else if($post_type == 'product'){
            global $post;

            $taxonomy = 'product_cat';

            $terms = get_the_terms( $post->ID, $taxonomy );
            $links = array();
            if ( $terms && ! is_wp_error( $terms ) ) {

                foreach ( $terms as $c ) {

                    $parents = woo_get_term_parents( $c->term_id, $taxonomy, true, ', ', false, array( $c->term_id ) );
                    if ( $parents != '' ) {
                        $parents_arr = explode( ', ', $parents );

                        foreach ( $parents_arr as $p ) {
                            if ( $p != '' ) { $links[] = $p; }
                        }
                    }
                }
                foreach($links as $link){
                    echo '<li>'.$link.'</li>';
                }
            }
        }else{
            $obj = get_post_type_object( $post_type );
            echo '<li class="active">';
            echo $obj->labels->singular_name;
            echo '</li>';
        }
    } elseif ( is_page() && !$post->post_parent ) {
        echo '<li class="active">';
        the_title();
        echo '</li>';
    } elseif ( is_page() && $post->post_parent ) {
        $parent_id  = $post->post_parent;
        $breadcrumbs = array();
        while ($parent_id) {
            $page = get_post($parent_id);
            $breadcrumbs[] = '<li><a href="' . get_permalink($page->ID) . '">' . get_the_title($page->ID) . '</a></li>';
            $parent_id  = $page->post_parent;
        }
        $breadcrumbs = array_reverse($breadcrumbs);
        foreach ($breadcrumbs as $crumb) echo $crumb;
        echo '<li class="active">';
        the_title();
        echo '</li>';
    } elseif ( is_search() ) {
        $kw = get_search_query();
        $kw = !empty($kw) ? $kw : __('None', 'wpcom');
        echo '<li class="active">' . sprintf( __('Search for: %s', 'wpcom'), $kw) . '</li>';
    } elseif ( is_tag() ) {
        echo '<li class="active">';
        single_tag_title();
        echo '</li>';
    } elseif ( is_author() ) {
        global $author;
        $userdata = get_userdata($author);
        echo '<li class="active">' . $userdata->display_name . '</li>';
    } elseif ( is_404() ) {
        echo '<li class="active">'.__('404 ERROR', 'wpcom').'</li>';
    }

    if ( get_query_var('paged') ) {
        echo '<li class="active">';
        echo sprintf( __('Paged %s', 'wpcom'), get_query_var('paged'));
        echo '</li>';
    }

    echo '</ol>';
}

function wpcom_get_category_parents( $id, $visited = array() ) {
    if(!$id) return '';
    $chain = '';
    $parent = get_term( $id, 'category' );
    if ( is_wp_error( $parent ) )
        return '';
    $name = $parent->name;

    if ( $parent->parent && ( $parent->parent != $parent->term_id ) && !in_array( $parent->parent, $visited ) ) {
        $visited[] = $parent->parent;
        $chain .= wpcom_get_category_parents( $parent->parent, $visited );
    }
    $chain .= '<li><a href="' . esc_url( get_category_link( $parent->term_id ) ) . '">'.$name.'</a></li>';
    return $chain;
}

function woo_get_term_parents( $id, $taxonomy, $link = false, $separator = '', $nicename = false, $visited = array() ) {
    $chain = '';
    $parent = get_term( $id, $taxonomy );
    if ( is_wp_error( $parent ) )
        return $parent;
    if ( $nicename )
        $name = $parent->slug;
    else
        $name = $parent->name;
    if ( $parent->parent && ( $parent->parent != $parent->term_id ) && ! in_array( $parent->parent, $visited ) && ! in_array( $parent->term_id, $visited ) ) {
        $visited[] = $parent->parent;
        $visited[] = $parent->term_id;
        $chain .= woo_get_term_parents( $parent->parent, $taxonomy, $link, $separator, $nicename, $visited );
    }
    if ( $link ) {
        $chain .= '<a href="' . get_term_link( $parent, $taxonomy ) . '" title="' . esc_attr( $parent->name ) . '">'.$name.'</a>' . $separator;
    } else {
        $chain .= $name.$separator;
    }

    return $chain;
} // End woo_get_term_parents()