<?php
defined( 'ABSPATH' ) || exit;

class WPCOM_Module_video extends WPCOM_Module{
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'cover' => array(
                    'name' => '背景图',
                    'type' => 'upload',
                    'desc' => '',
                    'value'  => ''
                ),
                'video' => array(
                    'name' => '视频代码',
                    'type' => 'textarea',
                    'desc' => '可填写第三方视频分享代码（推荐通用代码）、mp4视频地址、视频短代码（简码、shortcode）',
                    'value'  => ''
                ),
                'mod-height' => array(
                    'name' => '模块高度',
                    'type' => 'text',
                    'desc' => '页面模块高度，要带单位，比如px',
                    'value'  => '200px'
                ),
                'width' => array(
                    'name' => '弹窗宽度',
                    'type' => 'text',
                    'desc' => '视频弹窗宽度，可根据视频尺寸调整，要带单位，比如px',
                    'value'  => '900px'
                ),
                'height' => array(
                    'name' => '弹窗高度',
                    'type' => 'text',
                    'desc' => '视频弹窗高度，可根据视频尺寸调整，要带单位，比如px',
                    'value'  => '550px'
                ),
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
        parent::__construct( 'video', '视频', $options, 'video-camera' );
    }

    function style( $atts ){
        $width = intval(isset($atts['width'])&&$atts['width']?$atts['width']:'900');
        $height = intval(isset($atts['height'])&&$atts['height']?$atts['height']:'550');
        $mod_height = intval(isset($atts['mod-height'])&&$atts['mod-height']?$atts['mod-height']:'200');
        ?>
        #modules-<?php echo $atts['modules-id'];?> .video-wrap{
            height: <?php echo $mod_height?>px;
        }
        #modules-<?php echo $atts['modules-id'];?> .modal-body{
            height: <?php echo $height?>px;
        }
        #modules-<?php echo $atts['modules-id'];?> .modal-lg{
            width: <?php echo $width?>px;
        }
        @media (max-width: 1199px){
            #modules-<?php echo $atts['modules-id'];?> .video-wrap{
                height: <?php echo $mod_height*0.83?>px;
            }
        }
        @media (max-width: 991px){
            #modules-<?php echo $atts['modules-id'];?> .video-wrap{
                height: <?php echo $mod_height*0.63?>px;
            }
            #modules-<?php echo $atts['modules-id'];?> .modal-body{
                height: <?php echo $height*0.63?>px;
            }
            #modules-<?php echo $atts['modules-id'];?> .modal-lg{
                width: <?php echo $width*0.63?>px;
            }
        }
        @media (max-width: 767px){
            #modules-<?php echo $atts['modules-id'];?> .video-wrap{
                height: <?php echo $mod_height*0.5?>px;
            }
            #modules-<?php echo $atts['modules-id'];?> .modal-body{
                height: <?php echo $height*0.6?>px;
            }
            #modules-<?php echo $atts['modules-id'];?> .modal-lg{
                width: auto;
            }
        }
        <?php
    }

    function template($atts, $depth) {
        $width = intval(isset($atts['width'])&&$atts['width']?$atts['width']:'900');
        $height = intval(isset($atts['height'])&&$atts['height']?$atts['height']:'550');
        ?>
        <div <?php echo (isset($atts['cover']) && $atts['cover'] ? wpcom_lazybg($atts['cover'],'video-wrap') : 'class="video-wrap"');?>>
            <div class="modal-player" data-toggle="modal" data-target="#vModal-<?php echo $atts['modules-id'];?>"></div>
            <script class="video-code" type="text/html">
                <?php
                $atts['video'];
                if( $atts['video']!='' && preg_match('/^(http:\/\/|https:\/\/|\/\/).*/i', $atts['video']) ){
                    $atts['video'] = '[video width="'.$width.'" height="'.$height.'" autoplay="true" src="'.$atts['video'].'"][/video]';
                }
                ?>
                <?php echo do_shortcode($atts['video']);?>
            </script>
            <!-- Modal -->
            <div class="modal fade modal-video" id="vModal-<?php echo $atts['modules-id'];?>" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                        </div>
                        <div class="modal-body"></div>
                    </div>
                </div>
            </div>
        </div>
    <?php }
}

register_module( 'WPCOM_Module_video' );