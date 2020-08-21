<?php
defined( 'ABSPATH' ) || exit;

function wpcom_sc_btn($atts, $content=''){
    $html = '';
    $atts['type'] = $atts['type']?$atts['type']:'primary';
    if($content){
        if($atts['url']) {
            $html = '<a class="btn btn-'.$atts['type'].'" href="'.esc_url($atts['url']).'" target="_blank">'.$content.'</a>';
        }else{
            $html = '<span class="btn btn-'.$atts['type'].'">'.$content.'</span>';
        }
    }
    return $html;
}

function wpcom_sc_gird($atts){
    if(isset($atts['id'])){
        global $post;
        $data = get_post_meta($post->ID, $atts['id'], true);
        if(!$data){
            $p = wpcom::get_post($atts['id'], 'sc_gird');
            $data = $p->post_content;
        }
        $data = maybe_unserialize($data);

        $html = '<div class="row">';
        if($data){
            foreach($data as $g){
                $html .= '<div class="col-md-'.$g['cols'].'">'.do_shortcode($g['body']).'</div>';
            }
        }
        $html .= '</div>';
        return $html;
    }
}

function wpcom_sc_icon($atts){
    if($atts['name']){
        $html = '<i class="fa fa-'.$atts['name'].'"></i>';
        return $html;
    }
}

function wpcom_sc_alert($atts, $content=''){
    $html = '';
    $atts['type'] = isset($atts['type'])?$atts['type']:'primary';
    if($content){
        $html .= '<div class="alert alert-'.$atts['type'].'" role="alert">';
        if(isset($atts['icon']) && $atts['icon']){
            if(isset($atts['size']) && $atts['size'] == '1'){
                $html .= '<i class="fa fa-lg fa-'.$atts['icon'].'"></i> <div class="alert-content">'.do_shortcode($content).'</div>';
            }else{
                $html .= '<i class="fa fa-'.$atts['icon'].'"></i> '.do_shortcode($content);
            }
        }else{
            $html .= do_shortcode($content);
        }
        $html .= '</div>';
    }
    return $html;
}

function wpcom_sc_panel($atts, $content){
    if(isset($atts['id'])){
        global $post;
        $data = get_post_meta($post->ID, $atts['id'], true);
        if(!$data){
            $p = wpcom::get_post($atts['id'], 'sc_panel');
            $data = $p->post_content;
        }
        $data = maybe_unserialize($data);
    }else if( isset($atts['type']) && isset($content) && $content){
        $data = $atts;
        $data['body'] = $content;
    }

    $html = '';
    if($data){
        $html = '<div class="panel panel-'.$data['type'].'">';
        if($data['title']){
            $html .= '<div class="panel-heading"><h3 class="panel-title">'.(isset($data['icon'])&&$data['icon']?'<i class="fa fa-'.$data['icon'].'"></i> ':'').$data['title'].'</h3></div>';
        }
        $html .= '<div class="panel-body">'.do_shortcode($data['body']).'</div></div>';
    }
    return $html;
}

function wpcom_sc_tabs($atts, $content){
    if(isset($atts['id']) && (!isset($content) || !$content)){
        global $post;
        $data = get_post_meta($post->ID, $atts['id'], true);
        if(!$data){
            $p = wpcom::get_post($atts['id'], 'sc_tabs');
            $data = $p->post_content;
        }
        $data = maybe_unserialize($data);
    }else if( isset($atts['type']) && isset($content) && $content){
        $data = $atts;
        $data['title'] = explode('||', $data['title']);
        $data['body'] = $content;
    }

    $html = '';
    if(isset($data['title']) && is_array($data['title'])) {
        $html = '<div class="tabs'.(isset($data['type'])&&$data['type']?' tabs-horizontal':'').'"><ul class="nav nav-tabs" role="tablist">';
        $i=0;
        foreach ($data['title'] as $title) {
            $html .= '<li role="presentation"' . ($i == 0 ? ' class="active"' : '') . '><a href="#tabs-' . $atts['id'] . '-' . $i . '" role="tab" data-toggle="tab">' . $title . '</a></li>';
            $i++;
        }
        $html .= '</ul><div class="tab-wrap"><div class="tab-content">';
        $i = 0;
        if(isset($data['body']) && is_array($data['body'])) {
            foreach ($data['body'] as $body) {
                $html .= '<div role="tabpanel" class="tab-pane fade' . ($i == 0 ? ' in active' : '') . '" id="tabs-' . $atts['id'] . '-' . $i . '">' . do_shortcode($body) . '</div>';
                $i++;
            }
        }else if(isset($data['body']) && $data['body']){
            $html .= do_shortcode($data['body']);
        }
        $html .= '</div></div></div>';
    }
    return $html;
}

function wpcom_sc_accordion($atts){
    if(isset($atts['id'])){
        global $post;
        $data = get_post_meta($post->ID, $atts['id'], true);
        if(!$data){
            $p = wpcom::get_post($atts['id'], 'sc_accordion');
            $data = $p->post_content;
        }
        $data = maybe_unserialize($data);

        $html = '';
        if(isset($data['title']) && is_array($data['title'])) {
            $html = '<div class="panel-group" id="accordion-'.$atts['id'].'" role="tablist" aria-multiselectable="true">';
            $i=0;
            foreach($data['title'] as $title){
                $html .= '<div class="panel panel-default"><div class="panel-heading" role="tab" id="heading-'.$atts['id'].'-'.$i.'"><h4 class="panel-title"><a role="button" data-toggle="collapse" data-parent="#accordion-'.$atts['id'].'" href="#accordion-'.$atts['id'].'-'.$i.'" aria-expanded="'.($i==0?'true':'false').'" aria-controls="accordion-'.$atts['id'].'-'.$i.'">'.$title.'</a></h4></div><div id="accordion-'.$atts['id'].'-'.$i.'" class="panel-collapse collapse'.($i==0?' in':'').'" role="tabpanel" aria-labelledby="heading-'.$atts['id'].'-'.$i.'"><div class="panel-body">'.( isset($data['body']) && isset($data['body'][$i]) ? do_shortcode($data['body'][$i]) : '').'</div></div></div>';
                $i++;
            }
            $html .= '</div>';
        }
        return $html;
    }
}

function wpcom_sc_map($atts, $content=''){
    if(isset($atts['pos'])){
        $str = '';
        $str .= isset($atts['title'])&&$atts['title'] ? '<h3 class="map-title">'.$atts['title'].'</h3>':'';
        $str .= $content ? '<p class="map-address">'.$content.'</p>':'';
        $height = intval(isset($atts['height'])&&$atts['height']?$atts['height']:'300px');

        $html = '<div class="map-wrap" style="height:'.$height.'px;">';
        $html .= baidu_map($str, $atts['pos'], isset($atts['scroll'])?$atts['scroll']:0, false);
        $html .= '</div>';
        return $html;
    }
}