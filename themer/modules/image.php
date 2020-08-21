<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_image extends WPCOM_Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'image' => array(
                    'name' => '图片',
                    'type' => 'upload',
                    'desc' => '图片将会根据模块宽度100%显示',
                    'value'  => ''
                ),
                'alt' => array(
                    'name' => '替代文本',
                    'type' => 'text',
                    'desc' => '可选，图片alt属性，图片替代文本，利于SEO',
                    'value'  => ''
                ),
                'url' => array(
                    'name' => '链接地址',
                    'type' => 'text',
                    'desc' => '可选',
                    'value'  => ''
                ),
                'target' => array(
                    'name' => '打开方式',
                    'type' => 'select',
                    'desc' => '链接打开方式',
                    'value'  => '0',
                    "options" => array(
                        "0" => "当前窗口",
                        "1" => "新窗口打开",
                    )
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
                    'value'  => '20px'
                )
            )
        );
        parent::__construct( 'image', '图片', $options, 'image' );
    }

    function template($atts, $depth){
        if(isset($atts['url']) && $atts['url']){ ?>
            <a href="<?php echo esc_url($atts['url']);?>"<?php echo isset($atts['target']) && $atts['target']=='1'?' target="_blank"':''?>>
                <img src="<?php echo $atts['image']; ?>" alt="<?php echo esc_attr(isset($atts['alt'])?$atts['alt']:''); ?>">
            </a>
        <?php } else { ?>
            <img src="<?php echo $atts['image']; ?>" alt="<?php echo esc_attr(isset($atts['alt'])?$atts['alt']:''); ?>">
        <?php }
    }
}

register_module( 'WPCOM_Module_image' );