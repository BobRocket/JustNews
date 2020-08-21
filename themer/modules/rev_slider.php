<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_rev_slider extends WPCOM_Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'alias' => array(
                    'name' => '选择滑块',
                    'type' => 'select',
                    'desc' => '',
                    'value'  => 'home',
                    'options' => wpcom::get_all_sliders()
                )
            ),
            array(
                'tab-name' => '风格样式',
                'margin-top' => array(
                    'name' => '上外边距',
                    'type' => 'text',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-top 值，例如： 10px',
                    'value'  => '0'
                ),
                'margin-bottom' => array(
                    'name' => '下外边距',
                    'type' => 'text',
                    'desc' => '模块离上一个模块/元素的间距，单位建议为px。即 margin-bottom 值，例如： 10px',
                    'value'  => '0'
                )
            )
        );
        parent::__construct( 'rev_slider', 'Slider Revolution', $options, 'desktop' );
    }

    function template($atts, $depth){
        if($atts['alias']) {
            echo do_shortcode('[rev_slider alias="' . $atts['alias'] . '"]');
        }
    }
}

if(shortcode_exists("rev_slider")) register_module( 'WPCOM_Module_rev_slider' );