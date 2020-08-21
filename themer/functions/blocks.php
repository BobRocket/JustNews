<?php
/**
 * Created by PhpStorm.
 * User: Lomu
 * Date: 18/8/8
 * Time: 下午6:12
 */
defined( 'ABSPATH' ) || exit;

if ( function_exists('register_block_type') ) {
    add_action('init', 'wpcom_gutenberg_blocks');
    function wpcom_gutenberg_blocks() {
        wp_register_script('wpcom-blocks', FRAMEWORK_URI . '/assets/js/blocks.js', array('wp-blocks', 'wp-element'), FRAMEWORK_VERSION, true);
        wp_register_style('wpcom-blocks', FRAMEWORK_URI . '/assets/css/blocks.css', array('wp-edit-blocks'), FRAMEWORK_VERSION);

        register_block_type('wpcom/blocks', array(
            'editor_script' => 'wpcom-blocks',
            'editor_style' => 'wpcom-blocks'
        ));
    }

    add_filter( 'block_categories', 'wpcom_gutenberg_block_categories' );
    function wpcom_gutenberg_block_categories( $categories ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'wpcom',
                    'title' => __( 'WPCOM扩展区块', 'wpcom' ),
                ),
            )
        );
    }
}