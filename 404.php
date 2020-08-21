<?php get_header();?>
    <div class="main container" style="margin-bottom: 20px;">
            <article class="hentry">
                <div class="entry">
                    <div class="entry-head">
                        <h1 class="entry-title"><?php _e('404 Page not found!', 'wpcom');?></h1>
                    </div>
                    <div class="entry-content">
                        <p><?php _e("We're sorry, but the page you're looking for may have been moved or deleted.", 'wpcom');?> <a href="<?php bloginfo('url');?>"><?php _e('Go home', 'wpcom');?></a></p>
                    </div>
                </div>
            </article>

    </div>
<?php get_footer();?>