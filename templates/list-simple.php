<li class="list-item">
    <span class="date pull-right"><?php the_time(get_option('date_format'));?></span>
    <a href="<?php echo esc_url( get_permalink() );?>" title="<?php echo esc_attr(get_the_title());?>">
        <?php the_title();?>
    </a>
</li>