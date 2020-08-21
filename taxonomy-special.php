<?php
get_header();
$term = get_queried_object();
$banner = get_term_meta( $term->term_id, 'wpcom_banner', true );
if($banner){
    $banner_height = get_term_meta( $term->term_id, 'wpcom_banner_height', true );
    $text_color = get_term_meta( $term->term_id, 'wpcom_text_color', true );
    $bHeight = intval($banner_height ? $banner_height : 300);
    $bColor = ($text_color ? $text_color : 0) ? ' banner-white' : '';
    ?>
    <div <?php echo wpcom_lazybg($banner, 'banner'.$bColor, 'height:'.$bHeight.'px;');?>>
        <div class="banner-inner">
            <h1><?php single_cat_title(); ?></h1>
            <div class="page-description"><?php echo term_description();?></div>
        </div>
    </div>
<?php } ?>
    <div class="main container">
        <?php if($banner==''){ ?>
            <div class="special-head">
                <h1 class="special-title"><?php single_cat_title(); ?></h1>
                <div class="page-description"><?php echo term_description();?></div>
            </div>
        <?php } ?>
        <div class="content">
            <div class="sec-panel archive-list">
                <ul class="article-list">
                    <?php if(have_posts()) : while( have_posts() ) : the_post();?>
                        <?php get_template_part( 'templates/list' , 'default' ); ?>
                    <?php endwhile; else : ?>
                        <li class="item" style="border: 0;">
                            <p class="text-center" style="padding: 15px 0;margin: 0;color: #999;"><?php _e('No posts.', 'wpcom');?></p>
                        </li>
                    <?php endif;?>
                </ul>
                <?php wpcom_pagination(5);?>
            </div>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>