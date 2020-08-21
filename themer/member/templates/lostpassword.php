<?php
defined( 'ABSPATH' ) || exit;

$steps = array(
    'default' => __('STEP 1', 'wpcom'),
    'send_success' => __('STEP 2', 'wpcom'),
    'reset' => __('STEP 3', 'wpcom'),
    'finished' => __('STEP 4', 'wpcom')
);
?>

<div class="member-lostpassword">
    <div class="member-lp-head">
        <ul class="member-lp-process">
            <?php $i = 1; $active = 0; foreach ($steps as $key => $step ) {
                if( $key==$subpage ) {
                    $classes = 'active';
                    $active = 1;
                }else if( $key!=$subpage && $active == 1 ){
                    $classes = '';
                }else{
                    $classes = 'processed active';
                }
                if($key=='finished'){ ?>
                    <li class="last <?php echo $classes; ?>">
                        <i><?php echo $i; ?></i>
                        <p><?php echo $step;?></p>
                    </li>
                <?php } else{ ?>
                    <li class="<?php echo $classes; ?>">
                        <div class="process-index">
                            <i><?php echo $i; ?></i>
                            <p><?php echo $step;?></p>
                        </div>
                        <div class="process-line"></div>
                    </li>
                <?php } ?>
                <?php $i++; } ?>
        </ul>
    </div>

    <div class="member-lp-main">
        <?php do_action( 'wpcom_lostpassword_form_' . $subpage );?>
    </div>
</div>

