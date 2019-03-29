<?php


    global $wpvr_colors, $wpvr_status, $wpvr_services, $wpvr_types_;
    global $wpvr_vs;
    //$max_wanted_videos = wpvr_max_fetched_videos_per_run();
    // wp_localize_script('cutv-api', 'wpApiSettings', array('root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest')));
    // wp_enqueue_script('cutv-api');
    $cutv_channels = cutv_get_channels();
    // $all_sources = cutv_get_sources_info(true);


?>
<script>
    var cutv = {
        channels: <?php echo json_encode($cutv_channels); ?>
    };
    // var sources = <?php echo json_encode($all_sources); ?>
</script>

<div>
    <div class="cutv-app-wrapper" ng-view=""></div>
</div>
