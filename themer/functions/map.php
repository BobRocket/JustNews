<?php
defined( 'ABSPATH' ) || exit;

class Baidu_map{
    public function __construct( $html='', $pos='', $scrollWheelZoom=0 ) {
        $this->html = $html;
        $this->pos = $pos ? $pos : '116.403963,39.915119';
        $this->scrollWheelZoom = $scrollWheelZoom;
        $rand1 = rand(100,999);
        $rand2 = rand(1000,9999);
        $rand3 = rand(10000,99999);
        $this->id = 'map-'.$rand1.$rand2.$rand3; //随机数ID避免重复
    }

    public function init_map(){
        global $options;
        $ak = isset($options['baidu_map_ak']) && $options['baidu_map_ak'] ? $options['baidu_map_ak'] : '0RisxUuPqPSBMWjZQ24ROEch4TQFrQXE';
        return '<div id="'.$this->id.'" style="height: 100%;"></div>' . '<script>jQuery(window).load(function(){ baidu_map("'.$this->id.'", "'.addslashes($this->html).'", ['.strip_tags($this->pos).'], '.$this->scrollWheelZoom.', "'.$ak.'");})</script>';
    }
}

function baidu_map($html='', $pos='', $scrollWheelZoom=0, $echo=true){
    $map = new Baidu_map($html, $pos, $scrollWheelZoom);
    if($echo){
        echo $map->init_map();
    }else{
        return $map->init_map();
    }
}


