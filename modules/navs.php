<?php
class WPCOM_Module_navs extends WPCOM_Module {
    function __construct(){
        $options = array(
            array(
                'tab-name' => '常规设置',
                'title' => array(
                    'name' => '标题',
                    'type' => 'text',
                    'desc' => '',
                    'value'  => ''
                ),
                'sub-title' => array(
                    'name' => '副标题',
                    'type' => 'text',
                    'desc' => '',
                    'value'  => ''
                ),
                'cols' => array(
                    'name' => '每行显示',
                    'type' => 'select',
                    'desc' => '',
                    'value'  => '4',
                    'options' => array(
                        '3' => '3个',
                        '4' => '4个',
                        '5' => '5个',
                        '6' => '6个'
                    )
                ),
                'target' => array(
                    'name' => '链接目标',
                    'type' => 'select',
                    'desc' => '',
                    'value'  => '1',
                    'options' => array(
                        '0' => '当前页面',
                        '1' => '新标签页'
                    )
                ),
                'links' => array(
                    'type' => 'repeat',
                    'items' => array(
                        'url' => array(
                            'name' => '链接地址',
                            'type' => 'text',
                            'desc' => '',
                            'value'  => ''
                        ),
                        'title' => array(
                            'name' => '链接标题',
                            'type' => 'text',
                            'desc' => '',
                            'value'  => ''
                        ),
                        'desc' => array(
                            'name' => '链接简介',
                            'type' => 'text',
                            'desc' => '',
                            'value'  => ''
                        ),
                        'img' => array(
                            'name' => '链接Logo',
                            'type' => 'upload',
                            'desc' => 'LOGO图片比例为1:1',
                            'value'  => ''
                        )
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
        parent::__construct('navs', '导航链接', $options, 'link');
    }

    function template( $atts, $depth ){
        $target = isset($atts['target']) && $atts['target']=='0' ? '' : ' target="_blank"';
        $cols = isset($atts['cols']) && $atts['cols'] ? $atts['cols'] : 4;
        ?>
        <div class="sec-panel">
            <?php if( isset($atts['title']) && $atts['title'] ){ ?>
            <div class="sec-panel-head">
                <h2><?php echo $atts['title'];?> <small><?php echo $atts['sub-title'];?></small></h2>
            </div>
            <?php } ?>
            <div class="sec-panel-body">
                <div class="list list-navs list-navs-cols-<?php echo $cols;?>">
                <?php if( isset($atts['links']) && $atts['links'] ){ foreach($atts['links'] as $links){ ?>
                    <a class="navs-link" href="<?php echo esc_url($links['url']);?>"<?php echo $target;?>>
                        <?php if($links['img']){ ?>
                        <div class="navs-link-logo">
                            <img src="<?php echo esc_url($links['img']);?>" alt="<?php echo esc_attr($links['title']);?>">
                        </div><?php } ?>
                        <div class="navs-link-info">
                            <h3><?php echo $links['title'];?></h3>
                            <p><?php echo $links['desc'];?></p>
                        </div>
                    </a>
                <?php } } ?>
                </div>
            </div>
        </div>
    <?php }
}
register_module( 'WPCOM_Module_navs' );