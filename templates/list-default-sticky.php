<?php
preg_match_all('/<img[^>]*src=[\'"]([^\'"]+)[\'"].*>/iU', get_the_content(), $matches);
if(isset($matches[1]) && isset($matches[1][3]) && is_multimage()) {
    get_template_part('templates/list', 'multimage-sticky');
    return;
}
global $options;
$show_author = isset($options['show_author']) && $options['show_author']=='0' ? 0 : 1;
$img_right = isset($options['list_img_right']) && $options['list_img_right']=='1' ? 1 : 0;
$margin = $img_right ? 'style="margin-right: 0;"': 'style="margin-left: 0;"' ;
?>
<li class="item<?php echo $img_right ? ' item2':'';?><?php echo is_sticky()?' item-sticky':'';?>">
    <?php $has_thumb = get_the_post_thumbnail(); if($has_thumb){
        $video = get_post_meta( $post->ID, 'wpcom_video', true );?>
        <div class="item-img<?php echo $video?' item-video':'';?>">
        <a class="item-img-inner" href="<?php the_permalink();?>" title="<?php echo esc_attr(get_the_title());?>" target="_blank">
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
                <?php if(is_sticky()){ ?><span class="sticky-post">置顶</span><?php } ?> <?php the_title();?>
            </a>
        </h2>
        <div class="item-excerpt">
            <?php the_excerpt(); ?>
        </div>
        <div class="item-meta">
            <?php if( $show_author && isset($options['member_enable']) && $options['member_enable']=='1' ){ ?>
            <div class="item-meta-li author">
                <?php
                $author = get_the_author_meta( 'ID' );
                $author_url = get_author_posts_url( $author );
                ?>
                <a data-user="<?php echo $author;?>" target="_blank" href="<?php echo $author_url; ?>" class="avatar">
                    <?php echo get_avatar( $author, 60, '',  get_the_author());?>
                </a>
                <a class="nickname" href="<?php echo $author_url; ?>" target="_blank"><?php echo get_the_author(); ?></a>
            </div>
            <?php } ?>
            <?php
            if(!$has_thumb){
                $category = get_the_category();
                $cat = $category?$category[0]:'';
                if($cat){
                    ?>
                    <a class="item-meta-li" href="<?php echo get_category_link($cat->cat_ID);?>" target="_blank"><?php echo $cat->name;?></a>
                <?php } } ?>
            <span class="item-meta-li date"><?php echo format_date(get_post_time( 'U', false, $post ));?></span>

            <?php
            $post_metas = isset($options['post_metas']) && is_array($options['post_metas']) ? $options['post_metas'] : array();
            foreach ( $post_metas as $meta ) echo wpcom_post_metas($meta);
            ?>
        </div>
    </div>
</li>
<?php do_action('wpcom_echo_ad', 'ad_flow');?>