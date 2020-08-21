<?php
defined( 'ABSPATH' ) || exit;

global $options;
$margin =' style="margin-left: 0; min-height: 125px;"';
?>
    <li class="item">
        <?php $has_thumb = get_the_post_thumbnail(); if($has_thumb){ ?>
            <div class="item-img">
                <a href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                    <?php the_post_thumbnail(); ?>
                </a>
                <?php
                $category = get_the_category();
                $cat = $category?$category[0]:'';
                if($cat){
                    ?>
                    <a class="item-category" href="<?php echo get_category_link($cat->cat_ID);?>" target="_blank"><?php echo $cat->name;?></a>
                <?php } ?>
            </div>
        <?php } ?>
        <div class="item-content"<?php echo ($has_thumb?'':$margin);?>>
            <h2 class="item-title">
                <a href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
                    <?php the_title();?>
                </a>
            </h2>
            <div class="item-excerpt">
                <?php the_excerpt(); ?>
            </div>
            <div class="item-meta">
                <?php
                if(!$has_thumb){
                    $category = get_the_category();
                    $cat = $category?$category[0]:'';
                    if($cat){ ?>
                        <a class="item-meta-li" href="<?php echo get_category_link($cat->cat_ID);?>" target="_blank"><?php echo $cat->name;?></a>
                    <?php } } ?>
                <span class="item-meta-li date"><?php echo format_date(get_post_time( 'U', false, $post ));?></span>
                <?php
                if(function_exists('the_views')) {
                    $views = intval(get_post_meta($post->ID, 'views', true));
                    ?>
                    <span class="item-meta-li views" title="阅读数"><i class="fa fa-eye"></i> <span class="data"><?php echo $views; ?></span></span>
                <?php }
                $likes = get_post_meta($post->ID, 'wpcom_likes', true);
                if($likes!=''){ ?>
                <span class="item-meta-li likes" title="点赞数"><i class="fa fa-thumbs-up"></i> <span class="data"><?php echo $likes?$likes:0;?></span></span><?php } ?>
                <?php if ( !isset($options['comments_open']) || $options['comments_open']=='1' ) { ?><a class="item-meta-li comments" href="<?php the_permalink();?>#comments" target="_blank" title="评论数"><i class="fa fa-comment"></i> <span class="data"><?php echo get_comments_number();?></span></a><?php }
                $favorites = get_post_meta($post->ID, 'wpcom_favorites', true);
                if($favorites!='') { ?>
                <span class="item-meta-li hearts" title="喜欢数"><i class="fa fa-heart"></i> <span class="data"><?php $favorites = get_post_meta($post->ID, 'wpcom_favorites', true); echo $favorites?$favorites:0;?></span></span><?php } ?>
            </div>
        </div>
    </li>