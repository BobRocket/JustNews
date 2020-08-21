<?php
global $options;
get_header();?>
    <div class="main container">
        <?php while( have_posts() ) : the_post();?>
            <?php if( isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) { ?>
                <ol class="breadcrumb entry-breadcrumb">
                    <li class="home"><i class="fa fa-map-marker"></i> <a href="<?php echo get_bloginfo('url')?>"><?php _e('Home', 'wpcom');?></a>
                        <?php if( isset($options['kx_page']) && $options['kx_page'] && $kx = get_post($options['kx_page']) ){ ?>
                    <li><a href="<?php echo get_permalink($kx->ID);?>"><?php echo $kx->post_title;?></a></li>
                    <?php } ?>
                    <li class="active"><?php the_title();?></li>
                </ol>
            <?php } ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry">
                    <div class="entry-head">
                        <h1 class="entry-title"><?php the_title();?></h1>
                    </div>
                    <div class="entry-content clearfix">
                        <?php the_excerpt(); ?>
                        <?php if(get_the_post_thumbnail()){ ?>
                            <a class="kx-img" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                                <?php the_post_thumbnail('full'); ?>
                            </a>
                        <?php } ?>
                        <?php wpcom_pagination();?>
                    </div>
                    <div class="entry-footer kx-item" data-id="<?php the_ID();?>">
                        <div class="kx-meta hidden-sm hidden-md hidden-lg clearfix">
                            <span><?php echo format_date(get_post_time( 'U', false, $post ));?></span>
                            <span class="j-mobile-share" data-id="<?php the_ID();?>">
                                    <i class="fa fa-share-alt"></i> 生成分享图片
                                </span>
                        </div>
                        <div class="kx-meta hidden-xs clearfix">
                            <span><?php echo format_date(get_post_time( 'U', false, $post ));?></span>
                            <span>分享到</span>
                            <span class="share-icon wechat">
                                            <i class="fa fa-wechat"></i>
                                            <span class="wechat-img">
                                                <span class="j-qrcode" data-text="<?php the_permalink();?>"></span>
                                            </span>
                                        </span>
                            <span class="share-icon weibo" href="javascript:;"><i class="fa fa-weibo"></i></span>
                            <span class="share-icon qq" href="javascript:;"><i class="fa fa-qq"></i></span>
                            <span class="share-icon copy"><i class="fa fa-file-text"></i></span>
                        </div>
                    </div>
                    <div class="entry-page">
                        <p><?php previous_post_link(_x( 'Previous: %link', 'kx', 'wpcom' ), '%title'); ?></p>
                        <p><?php next_post_link(_x( 'Next: %link', 'kx', 'wpcom' ), '%title'); ?></p>
                    </div>
                    <?php
                    if ( isset($options['comments_open']) && $options['comments_open']=='1' ) {
                        comments_template();
                    }
                    ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
<?php
if(!$current_user->ID){
    $login_url = wp_login_url();
    $reg_url = wp_registration_url();
    ?>
    <div class="modal" id="login-modal">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                    <h4 class="modal-title">请登录</h4>
                </div>
                <div class="modal-body login-modal-body">
                    <p>您还未登录，请登录后再进行相关操作！</p>
                    <div class="login-btn">
                        <a class="btn btn-login" href="<?php echo $login_url;?>">登 录</a>
                        <a class="btn btn-register" href="<?php echo $reg_url;?>">注 册</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } get_footer();?>