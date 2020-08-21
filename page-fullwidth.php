<?php
// TEMPLATE NAME: 全宽模板
get_header();?>
    <div class="main container" style="margin-bottom: 20px;">
        <?php while( have_posts() ) : the_post();?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <div class="entry">
                    <div class="entry-head">
                        <h1 class="entry-title"><?php the_title();?></h1>
                    </div>
                    <div class="entry-content clearfix">
                        <?php the_content();?>
                    </div>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
<?php get_footer();?>