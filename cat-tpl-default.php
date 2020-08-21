<?php
get_header();
$banner = get_term_meta( $cat, 'wpcom_banner', true );
if($banner){
    $banner_height = get_term_meta( $cat, 'wpcom_banner_height', true );
    $text_color = get_term_meta( $cat, 'wpcom_text_color', true );
    $bHeight = intval($banner_height ? $banner_height : 300);
    $bColor = ($text_color ? $text_color : 0) ? ' banner-white' : '';
    ?>
    <div <?php echo wpcom_lazybg($banner, 'banner'.$bColor, 'height:'.$bHeight.'px;');?>>
        <div class="banner-inner">
            <h1><?php single_cat_title(); ?></h1>
            <div class="page-description"><?php echo category_description();?></div>
        </div>
    </div>
<?php } ?>
    <div class="main container">
        <div class="content">
            <div class="sec-panel archive-list">
                <?php if($banner==''){ ?>
                    <div class="sec-panel-head">
                        <h1><?php single_cat_title(); ?></h1>
                    </div>
                <?php } ?>
                <ul class="article-list">
                    <?php while( have_posts() ) : the_post();?>
                        <?php get_template_part( 'templates/list' , 'default' ); ?>
                    <?php endwhile; ?>
                </ul>
                <?php wpcom_pagination(5);?>
            </div>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>