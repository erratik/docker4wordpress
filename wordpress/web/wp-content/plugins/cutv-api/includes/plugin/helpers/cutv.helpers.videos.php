<?php

define('DEBUG_LEVEL', 2);

function get_video_meta_ids( $meta_key, $meta_value) {
    global $wpdb;
    $query_string = "SELECT * FROM " . WPVR_VIDEO_META . " WHERE meta_key = '$meta_key' AND meta_value = '$meta_value'";
    $rows = $wpdb->get_results($query_string);
    
    cutv_log(DEBUG_LEVEL, '[get_video_meta_ids] '. $query_string);
    
    foreach ($rows as $meta_row) {
        $video_ids[] = $meta_row->video_id;
    }
    
    return $video_ids;
}

function cutv_wpvr_video_meta($video_id) {
    global $wpdb;
    $query_string = "SELECT meta_key,meta_value FROM " . WPVR_VIDEO_META . " WHERE video_id = $video_id AND (" 
    . "meta_key = '_thumbnail_id' " 
    . "OR meta_key = 'wpvr_video_id' " 
    . "OR meta_key = 'wpvr_video_duration' " 
    . "OR meta_key = 'wpvr_video_service_date' " 
    . "OR meta_key = 'wpvr_video_service_thumb' " 
    . "OR meta_key = 'wpvr_video_service_hqthumb' " 
    . "OR meta_key = 'wpvr_video_service_url' " 
    . "OR meta_key = 'wpvr_video_sourceId' " 
    . "OR meta_key = 'wpvr_video_service_desc')";
    
    $rows = $wpdb->get_results($query_string);

    $args = array(
        'post_type' => 'attachment',
        'post_parent' => intval($video_id)
    );
    
    $video_meta = array();
    foreach ($rows as $i => $meta_row) {
        $keys = [];
        foreach ($meta_row as $k => $meta_column) {
            array_push($keys, $meta_column);
        }
        $video_meta[$keys[0]] = $keys[1];
        
    }
    
    return $video_meta;
}

function cutv_get_snaptube_videos($ids, $single = false) {
    global $wpdb;
    if ($single) {
        return $wpdb->get_row( "SELECT * FROM " . SNAPTUBE_VIDEOS ." WHERE slug = $ids");
    } else {
        return $wpdb->get_results( "SELECT * FROM " . SNAPTUBE_VIDEOS ." WHERE slug IN ($ids)");
    }
}
function cutv_video_meta_by_source($source_id) {

    global $wpdb;
    
    $videos = $wpdb->get_results( "SELECT video_id FROM " . WPVR_VIDEO_META ." WHERE meta_key = 'wpvr_video_sourceId' AND meta_value = '$source_id'");
    
    $source_videos_meta = [];
    foreach ($videos as $video) {
        array_push($source_videos_meta, cutv_wpvr_video_meta($video->video_id));
    }
    
    return $source_videos_meta;
}

function cutv_wpvr_video_by_ids($videos) {
    global $wpdb;

    $wpvr_posts = [];
    foreach ($videos as $video) {
        
        $wpvr_post = get_post($video->video_id);
        
        if ($wpvr_post !== null) {
            array_push( $wpvr_posts, $wpvr_post);
        } else {
            $wpdb->delete( WPVR_VIDEO_META, array( 'video_id' => $video->video_id ) );
        }

    }
    
    
    return $wpvr_posts;

}

function cutv_sort_source_videos_by_status($wpvr_posts, $count = true) {

    
    $videos = array(
        'draft'   => [],
        'publish' => [],
        'pending' => []
    );
    
    foreach ($wpvr_posts as $wpvr_post) {
        array_push($videos[$wpvr_post->post_status], $wpvr_post);
    }

    if ($count ) {
        foreach ($videos as $status => $video) {
            $videos[$status] = count($videos[$status]);
        }
    }

    return $videos;

}
