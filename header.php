<?php global $options, $is_submit_page; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge,chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="initial-scale=1.0,user-scalable=no,maximum-scale=1,width=device-width">
    <title><?php wp_title( isset($options['title_sep']) && $options['title_sep'] ? $options['title_sep'] : ' | ', true, 'right' ); ?></title>
    <?php wp_head();?>
    <script> (function() {if (!/*@cc_on!@*/0) return;var e = "abbr, article, aside, audio, canvas, datalist, details, dialog, eventsource, figure, footer, header, hgroup, mark, menu, meter, nav, output, progress, section, time, video".split(', ');var i= e.length; while (i--){ document.createElement(e[i]) } })()</script>
    <!--[if lte IE 8]><script src="<?php echo get_template_directory_uri()?>/js/respond.min.js"></script><![endif]-->
</head>
<body <?php body_class()?>>
<header class="header">
    <div class="container clearfix">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target=".navbar-collapse">
                <span class="icon-bar icon-bar-1"></span>
                <span class="icon-bar icon-bar-2"></span>
                <span class="icon-bar icon-bar-3"></span>
            </button>
            <?php $h1_tag = 'div'; if(is_home()||is_front_page()) $h1_tag = 'h1'; ?>
            <<?php echo $h1_tag;?> class="logo">
                <a href="<?php bloginfo('url');?>" rel="home"><img src="<?php if($logo = isset($options['logo'])?$options['logo']:'') { echo $logo; } else { echo get_template_directory_uri().'/images/logo.png';} ?>" alt="<?php echo esc_attr(get_bloginfo( 'name' ));?>"></a>
            </<?php echo $h1_tag;?>>
        </div>
        <div class="collapse navbar-collapse">
            <?php
            wp_nav_menu( array(
                    'menu'              => 'primary',
                    'theme_location'    => 'primary',
                    'depth'             => 3,
                    'container'         => 'nav',
                    'container_class'   => 'navbar-left primary-menu',
                    'menu_class'        => 'nav navbar-nav',
                    'fallback_cb'       => 'WPCOM_Nav_Walker::fallback',
                    'walker'            => new WPCOM_Nav_Walker())
            );
            ?>
            <div class="navbar-action pull-right">

                <?php if( isset($options['member_enable']) && $options['member_enable']=='1' ) { ?>
                    <form class="navbar-search" action="<?php echo get_bloginfo('url');?>" method="get" role="search">
                        <input type="text" name="s" class="navbar-search-input" autocomplete="off" placeholder="<?php _e('Type your search here ...', 'wpcom');?>" value="<?php echo get_search_query(); ?>">
                        <a class="navbar-search-icon j-navbar-search" href="javascript:;"></a>
                    </form>

                    <?php do_action('wpcom_woo_cart_icon');?>

                    <div id="j-user-wrap">
                        <a class="login" href="<?php echo wp_login_url(); ?>"><?php _e('Sign in', 'wpcom');?></a>
                        <a class="login register" href="<?php echo wp_registration_url(); ?>"><?php _e('Sign up', 'wpcom');?></a>
                    </div>
                    <?php if( !isset($is_submit_page) && isset($options['tougao_on']) && $options['tougao_on']=='1' ){ ?><a class="publish" href="<?php echo esc_url(wpcom_addpost_url());?>">
                        <?php echo (isset($options['tougao_btn']) && $options['tougao_btn'] ? $options['tougao_btn'] : __('Submit Post', 'wpcom'));?></a>
                    <?php } ?>
                <?php }else{ ?>
                <form style="margin-right: -15px;" class="navbar-search" action="<?php echo get_bloginfo('url');?>" method="get" role="search">
                    <input type="text" name="s" class="navbar-search-input" autocomplete="off" placeholder="<?php _e('Type your search here ...', 'wpcom');?>" value="<?php echo get_search_query(); ?>">
                    <a class="navbar-search-icon j-navbar-search" href="javascript:;"></a>
                </form>
                <?php } ?>
            </div>
        </div><!-- /.navbar-collapse -->
    </div><!-- /.container -->
</header>
<div id="wrap">