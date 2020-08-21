<?php
/**
 * 分类模板信息设置
 * 在分类添加和编辑页面新增模板选择选项
 */
defined( 'ABSPATH' ) || exit;

// 分类列表选项
function add_cat_tpl_column( $columns ){
    $columns['tpl'] = '分类模板';
    return $columns;
}

// 分类列表选项 模板
function add_cat_tpl_column_content( $content, $column_name, $term_id ){

    if( $column_name !== 'tpl' ){
        return $content;
    }

    $term_id = absint( $term_id );
    $val = get_term_meta( $term_id, 'wpcom_tpl', true );

    $content .= $val ? '<category-tpl data-tpl="'.$val.'" />' : '默认模板';

    return $content;
}

// 分类列表选项 排序
function add_cat_tpl_column_sortable( $sortable ){
    $sortable[ 'tpl' ] = 'tpl';
    return $sortable;
}

function category_posts_per_page( $query ) {
    if( $query->is_main_query() && is_category() && ! is_admin() ) {
        global $options;
        $cat_obj = $query->get_queried_object();
        $tpl = get_term_meta( $cat_obj->term_id, 'wpcom_tpl', true );

        if($tpl && isset($options[$tpl.'_shows']) && $options[$tpl.'_shows']){
            $query->set( 'posts_per_page', $options[$tpl.'_shows'] );
        }
    }
}

add_action('pre_get_posts', 'category_posts_per_page' );
add_filter('manage_edit-category_columns', 'add_cat_tpl_column' );
add_filter('manage_category_custom_column', 'add_cat_tpl_column_content', 10, 3 );
add_filter('manage_edit-category_sortable_columns', 'add_cat_tpl_column_sortable' );