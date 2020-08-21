<?php
defined( 'ABSPATH' ) || exit;

// Pagenavi
function wpcom_pagination( $range = 9, $args = array() ) {
    global $paged, $wp_query, $page, $numpages, $multipage;

    if ( ($args && $args['numpages'] > 1) || ( isset($multipage) && $multipage && is_single() ) ) {
        if($args) {
            $page = $args['paged'];
            $numpages = $args['numpages'];
        }
        echo ' <div class="pagination clearfix">';
        $prev = $page - 1;
        if ( $prev > 0 ) {
            echo str_replace('<a', '<a class="prev"', _wp_link_page( $prev ) . __('&laquo; Previous', 'wpcom') . '</a>');
        }

        if($numpages > $range){
            if($page < $range){
                for($i = 1; $i <= ($range + 1); $i++){
                    if($i==$page){
                        echo str_replace('<a', '<a class="current"', _wp_link_page($i)) . $i . "</a>";
                    } else {
                        echo _wp_link_page($i) . $i . "</a>";
                    }
                }
            } elseif($page >= ($numpages - ceil(($range/2)))){
                for($i = $numpages - $range; $i <= $numpages; $i++){
                    if($i==$page){
                        echo str_replace('<a', '<a class="current"', _wp_link_page($i)) . $i . "</a>";
                    } else {
                        echo _wp_link_page($i) . $i . "</a>";
                    }
                }
            } elseif($page >= $range && $page < ($numpages - ceil(($range/2)))){
                for($i = ($page - ceil($range/2)); $i <= ($page + ceil(($range/2))); $i++){
                    if($i==$page){
                        echo str_replace('<a', '<a class="current"', _wp_link_page($i)) . $i . "</a>";
                    } else {
                        echo _wp_link_page($i) . $i . "</a>";
                    }
                }
            }
        }else{
            for ( $i = 1; $i <= $numpages; $i++ ) {
                if($i==$page){
                    echo str_replace('<a', '<a class="current"', _wp_link_page($i)) . $i . "</a>";
                } else {
                    echo _wp_link_page($i) . $i . "</a>";
                }
            }
        }

        $next = $page + 1;
        if ( $next <= $numpages ) {
            echo str_replace('<a', '<a class="next"', _wp_link_page( $next ) . __('Next &raquo;', 'wpcom') . '</a>');
        }
        echo '</div>';
    }else if( ($max_page = $wp_query->max_num_pages) > 1 ){
        echo ' <div class="pagination clearfix">';
        if(!$paged) $paged = 1;
        echo '<span>'.$paged.' / '.$max_page.'</span>';
        previous_posts_link(__('&laquo; Previous', 'wpcom'));
        if($max_page > $range){
            if($paged < $range){
                for($i = 1; $i <= ($range + 1); $i++){
                    echo "<a href='" . get_pagenum_link($i) ."'";
                    if($i==$paged) echo " class='current'";
                    echo ">".$i."</a>";
                }
            } elseif($paged >= ($max_page - ceil(($range/2)))){
                for($i = $max_page - $range; $i <= $max_page; $i++){
                    echo "<a href='" . get_pagenum_link($i) ."'";
                    if($i==$paged) echo " class='current'";
                    echo ">".$i."</a>";
                }
            } elseif($paged >= $range && $paged < ($max_page - ceil(($range/2)))){
                for($i = ($paged - ceil($range/2)); $i <= ($paged + ceil(($range/2))); $i++){
                    echo "<a href='" . get_pagenum_link($i) ."'";
                    if($i==$paged) echo " class='current'";
                    echo ">".$i."</a>";
                }
            }
        } else {
            for($i = 1; $i <= $max_page; $i++){
                echo "<a href='" . get_pagenum_link($i) ."'";
                if($i==$paged) echo " class='current'";
                echo ">$i</a>";
            }
        }
        next_posts_link(__('Next &raquo;', 'wpcom'));
        echo '</div>';
    }
}

add_filter('previous_posts_link_attributes', 'wpcom_prev_posts_link_attr');
function wpcom_prev_posts_link_attr($attr){
    return $attr.' class="prev"';
}
add_filter('next_posts_link_attributes', 'wpcom_next_posts_link_attr');
function wpcom_next_posts_link_attr($attr){
    return $attr.' class="next"';
}