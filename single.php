<?php
global $options,$current_user;
$dashang_display = isset($options['dashang_display']) ? $options['dashang_display'] : 0;
$show_author = isset($options['show_author']) && $options['show_author']=='0' ? 0 : 1;
$format = get_post_format($post->ID);
$video = get_post_meta( $post->ID, 'wpcom_video', true );
$video = $video ? $video : '';
if( $video!='' && preg_match('/^(http:\/\/|https:\/\/|\/\/).*/i', $video) ){
    $vthumb = get_the_post_thumbnail_url( $post->ID,'large' );
    $video = '[video width="860" preload="auto" src="'.$video.'" poster="'.$vthumb.'"][/video]';
}
get_header();?>
    <div class="main container">
        <div class="content">
            <?php if( $video=='' && isset($options['breadcrumb']) && $options['breadcrumb']=='1' ) wpcom_breadcrumb('breadcrumb entry-breadcrumb'); ?>
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry">
                        <?php
                        if( $video!='' ){ ?>
                            <div class="entry-video">
                                <?php echo do_shortcode($video); ?>
                            </div>
                        <?php } ?>
                        <div class="entry-head">
                            <h1 class="entry-title"><?php the_title();?></h1>
                            <div class="entry-info">
                                <?php
                                $author = get_the_author_meta( 'ID' );
                                $author_url = get_author_posts_url( $author );
                                $author_name = get_the_author();
                                ?>
                                <a class="nickname" href="<?php echo $author_url; ?>"><?php echo $author_name;?></a>
                                <span class="dot">•</span>
                                <span><?php echo format_date(get_post_time( 'U', false, $post ));?></span>
                                <span class="dot">•</span>
                                <?php the_category( ', ', '', false ); ?>
                                <?php if(function_exists('the_views')) {
                                    $views = intval(get_post_meta($post->ID, 'views', true));
                                    ?>
                                    <span class="dot">•</span>
                                    <span>阅读 <?php echo $views; ?></span>
                                <?php } ?>
                            </div>
                        </div>
                        <?php do_action('wpcom_echo_ad', 'ad_single_1');?>
                        <?php if($post->post_excerpt){ ?>
                        <div class="entry-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                        <?php } ?>
                        <div class="entry-content clearfix">
                            <?php the_content();?>
                            <?php wpcom_pagination();?>
                            <?php wpcom_post_copyright();?>
                        </div>
                        <div class="entry-footer">
                            <div class="entry-tag"><?php the_tags('', '');?></div>
                            <div class="entry-action">
                                <div class="btn-zan" data-id="<?php the_ID(); ?>"><i class="fa fa-thumbs-up"></i> <?php _e( 'Like', 'wpcom' );?> <span class="entry-action-num">(<?php $likes = get_post_meta($post->ID, 'wpcom_likes', true); echo $likes?$likes:0;?>)</span></div>

                                <?php if($dashang_display==1 && isset($options['dashang_1_img']) && ($options['dashang_1_img'] || $options['dashang_2_img'])){ ?>
                                    <div class="btn-dashang">
                                        <i class="fa fa-usd"></i> 打赏
                                                <span class="dashang-img<?php if($options['dashang_1_img']&&$options['dashang_2_img']){echo ' dashang-img2';}?>">
                                                    <?php if($options['dashang_1_img']){ ?>
                                                        <span>
                                                        <img src="<?php echo esc_url($options['dashang_1_img'])?>" alt="<?php echo esc_attr($options['dashang_1_title'])?>"/>
                                                            <?php echo $options['dashang_1_title'];?>
                                                    </span>
                                                    <?php } ?>
                                                    <?php if($options['dashang_2_img']){ ?>
                                                        <span>
                                                        <img src="<?php echo esc_url($options['dashang_2_img'])?>" alt="<?php echo esc_attr($options['dashang_2_title'])?>"/>
                                                            <?php echo $options['dashang_2_title'];?>
                                                    </span>
                                                    <?php } ?>
                                                </span>
                                    </div>
                                <?php } ?>
                            </div>

                            <?php if( isset($options['xzh-appid']) && $options['xzh-appid'] && isset($options['xzh-render-body']) && $options['xzh-render-body'] ){ ?><div id="xhz-render-body"></div><?php } ?>

                            <div class="entry-bar">
                                <div class="entry-bar-inner clearfix">
                                    <?php if($show_author) { ?>
                                        <div class="author pull-left">
                                            <a data-user="<?php echo $author;?>" target="_blank" href="<?php echo $author_url; ?>" class="avatar">
                                                <?php echo get_avatar( $author, 60, '',  $author_name);?>
                                                <?php echo $author_name; ?>
                                            </a>
                                            <?php $group = wpcom_get_user_group( $author );
                                            if( $group ){ ?><span class="author-title"><?php echo $group->name;?></span>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                    <div class="info <?php echo $show_author?'pull-right':'text-center';?>">
                                        <div class="info-item meta">
                                            <a class="meta-item j-heart" href="javascript:;" data-id="<?php the_ID(); ?>"><i class="fa fa-heart"></i> <span class="data"><?php $favorites = get_post_meta($post->ID, 'wpcom_favorites', true); echo $favorites?$favorites:0;?></span></a>
                                            <?php if ( isset($options['comments_open']) && $options['comments_open']=='1' ) { ?><a class="meta-item" href="#comments"><i class="fa fa-comments"></i> <span class="data"><?php echo get_comments_number();?></span></a><?php } ?>
                                            <?php if($dashang_display==0 && isset($options['dashang_1_img']) && ($options['dashang_1_img'] || $options['dashang_2_img'])){ ?>
                                            <a class="meta-item dashang" href="javascript:;">
                                                <i class="icon-dashang"></i>
                                                <span class="dashang-img<?php if($options['dashang_1_img']&&$options['dashang_2_img']){echo ' dashang-img2';}?>">
                                                    <?php if($options['dashang_1_img']){ ?>
                                                        <span>
                                                        <img src="<?php echo esc_url($options['dashang_1_img'])?>" alt="<?php echo esc_attr($options['dashang_1_title'])?>"/>
                                                            <?php echo $options['dashang_1_title'];?>
                                                    </span>
                                                    <?php } ?>
                                                    <?php if($options['dashang_2_img']){ ?>
                                                        <span>
                                                        <img src="<?php echo esc_url($options['dashang_2_img'])?>" alt="<?php echo esc_attr($options['dashang_2_title'])?>"/>
                                                            <?php echo $options['dashang_2_title'];?>
                                                    </span>
                                                    <?php } ?>
                                                </span>
                                            </a>
                                            <?php } ?>
                                        </div>
                                        <div class="info-item share">
                                            <a class="meta-item mobile j-mobile-share" href="javascript:;" data-id="<?php the_ID();?>"><i class="fa fa-share-alt"></i> 生成分享图片</a>
                                            <a class="meta-item wechat" href="javascript:;">
                                                <i class="fa fa-wechat"></i>
                                                <span class="wx-wrap">
                                                    <span class="j-qrcode" data-text="<?php the_permalink();?>"></span>
                                                    <span>扫码分享到微信</span>
                                                </span>
                                            </a>
                                            <?php
                                            $share_img = isset($GLOBALS['post-thumb']) ? $GLOBALS['post-thumb'] : wpcom::thumbnail_url($post->ID);
                                            ?>
                                            <a class="meta-item weibo" href="http://service.weibo.com/share/share.php?url=<?php echo urlencode(get_permalink());?>&title=<?php echo urlencode(get_the_title());?>&pic=<?php echo urlencode($share_img);?>&searchPic=true" target="_blank" rel="nofollow"><i class="fa fa-weibo"></i></a>
                                            <a class="meta-item qq" href="https://connect.qq.com/widget/shareqq/index.html?url=<?php echo urlencode(get_permalink());?>&title=<?php echo urlencode(get_the_title());?>&pics=<?php echo urlencode($share_img);?>" target="_blank" rel="nofollow"><i class="fa fa-qq"></i></a>
                                        </div>
                                        <div class="info-item act">
                                            <a href="javascript:;" id="j-reading"><i class="fa fa-file-text"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="entry-page">
                                <?php $pre = get_previous_post(); $next = get_next_post();
                                if($pre){ $pbg = get_the_post_thumbnail_url($pre); ?>
                                    <div <?php echo wpcom_lazybg($pbg, 'entry-page-prev'); ?>>
                                        <a href="<?php echo get_the_permalink($pre);?>" title="<?php echo esc_attr(get_the_title($pre));?>">
                                            <span><?php echo get_the_title($pre);?></span>
                                        </a>
                                        <div class="entry-page-info">
                                            <span class="pull-left"><?php echo _x( '&laquo; Previous', 'single', 'wpcom' );?></span>
                                            <span class="pull-right"><?php echo format_date(get_post_time( 'U', false, $pre ));?></span>
                                        </div>
                                    </div>
                                <?php } ?>
                                <?php if($next){ $nbg = get_the_post_thumbnail_url($next); ?>
                                    <div <?php echo wpcom_lazybg($nbg, 'entry-page-next'); ?>>
                                        <a href="<?php echo get_the_permalink($next);?>" title="<?php echo esc_attr(get_the_title($next));?>">
                                            <span><?php echo get_the_title($next);?></span>
                                        </a>
                                        <div class="entry-page-info">
                                            <span class="pull-right"><?php echo _x( 'Next  &raquo;', 'single', 'wpcom' );?></span>
                                            <span class="pull-left"><?php echo format_date(get_post_time( 'U', false, $next ));?></span>
                                        </div>
                                    </div>
                                <?php } ?>
                            </div>
                            <?php do_action('wpcom_echo_ad', 'ad_single_2');?>
                            <?php wpcom_related_post( (isset($options['related_num'])?$options['related_num']:6), ($related_news=$options['related_news'])?$related_news:__('Related posts', 'wpcom'), ($options['related_show_type']?$options['related_show_type']:0), 'templates/list-image'); ?>
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
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
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