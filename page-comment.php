<?php
// TEMPLATE NAME: 评论模板
wp_enqueue_script( 'comment-reply' );
get_header();
?>
    <div class="main container">
        <div class="content">
            <?php while( have_posts() ) : the_post();?>
                <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                    <div class="entry">
                        <div class="entry-head">
                            <h1 class="entry-title"><?php the_title();?></h1>
                        </div>
                        <div class="entry-content clearfix">
                            <?php the_content();?>
                        </div>
                        <?php comments_template();?>
                    </div>
                </article>
            <?php endwhile; ?>
        </div>
        <aside class="sidebar">
            <?php get_sidebar();?>
        </aside>
    </div>
<?php get_footer();?>