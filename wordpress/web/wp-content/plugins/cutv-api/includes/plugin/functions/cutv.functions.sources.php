<?php

define( 'DEBUG_LEVEL', 2);

function cutv_get_sources_info($channel_id = null) {
    
    global $wpdb;
    
    $sources = cutv_get_sources($channel_id);
    
    foreach ($sources as $source) {
        
        $cutv_meta = cutv_get_source_meta($source->ID, CUTV_SOURCE_PID, true);
        $wpvr_meta = cutv_get_source_meta($source->ID, 'wpvr_source_name', true);
        $source_pid = $cutv_meta->meta_value;
        $source_name = $wpvr_meta->meta_value;
        
        cutv_log(DEBUG_LEVEL, "[cutv_get_sources_info] [$source->ID] $source_name (playlist: $source_pid)", false);
        
        if ($source_pid != null) {
            $playlist = $wpdb->get_row("SELECT * FROM wp_hdflvvideoshare_playlist WHERE pid = ". intval($source_pid) );
            if ($playlist != null) {
                cutv_log(DEBUG_LEVEL-1, '[cutv_get_sources_info] '. "$source_name is assigned to channel: $playlist->playlist_name");
            } else {
                cutv_log(DEBUG_LEVEL-1, '[cutv_get_sources_info] '. "$source_name is assigned to non-existant channel");
            }
        } else {
            
            cutv_log(DEBUG_LEVEL-1, '[cutv_get_sources_info] '. "$source_name not linked. ($source->ID)", false);
            
            $playlist = (object) [
                'playlist_name' => null,
                'pid' => null,
            ];
        }
        
        $all_sources[] = (object) [
            'name' => $source_name,
            'source_id' => $source->ID,
            'channel' => (object) [
                'name' => $playlist->playlist_name,
                'pid' => $playlist->pid,
                ]
            ];
    }
    echo json_encode($all_sources);
    
    
    die();
}
add_action('wp_ajax_cutv_get_sources_info', 'cutv_get_sources_info');

// updates sources against their channels
function cutv_update_channel_sources() {
    
    
    if (isset($_REQUEST)) {

        $channel_id = $_REQUEST['channel'];
        
        cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "CUTV_SOURCE_PID: ".CUTV_SOURCE_PID, false);
        
        $source_ids = cutv_get_source_meta_ids(CUTV_SOURCE_PID, $channel_id );
        $removing = ($source_ids ? $source_ids : []);
        $existing_source_ids = ($source_ids ? $source_ids : []);
        $received_source_ids = explode(',', $_REQUEST['sources']);
        $updating_source_ids;
        $channel_source_ids = [];
        
        cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "RECEIVED: ");
        cutv_log(DEBUG_LEVEL-1, $received_source_ids);
        
        if (count($existing_source_ids)) {
            cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "EXISTING: ");
            cutv_log(DEBUG_LEVEL-1, $existing_source_ids);
        }

        $link_source_ids = array_diff($received_source_ids, $existing_source_ids);
        $unlink_source_ids = array_diff($existing_source_ids, $received_source_ids);
        if (!count($link_source_ids) && !count($unlink_source_ids)) {
            cutv_log(DEBUG_LEVEL-1, "[cutv_update_channel_sources] all sources already linked to channel ($channel_id)");
        }
        
        if (count($existing_source_ids)) {
            $updating_source_ids = $link_source_ids;
        } else {
            $updating_source_ids = $received_source_ids;
        }

        if (count($existing_source_ids) && count($unlink_source_ids)) {
            $deleted = delete_source_meta( $unlink_source_ids, CUTV_SOURCE_PID, $channel_id, true);
            if ($deleted) {
                cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "REMOVED SOURCE META: ");
                cutv_log(DEBUG_LEVEL-1, $unlink_source_ids);
            }
        }


        if (count($updating_source_ids)) {
            foreach ($updating_source_ids as $source_id) {
                $inserted = add_source_meta( $source_id, CUTV_SOURCE_PID, $channel_id, true);
                if ($inserted) {
                    cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "ADDED SOURCE META: ");
                    cutv_log(DEBUG_LEVEL-1, $channel_source_ids);
                }
            }
        } else {
            cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] nothing to add here');
        }
        
    }

    $channel_source_ids = cutv_get_source_meta_ids(CUTV_SOURCE_PID, $channel_id );

    cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "CHANNEL SOURCE IDS: ");    
    echo json_encode($channel_source_ids);
    die();
}
add_action('wp_ajax_cutv_update_channel_sources', 'cutv_update_channel_sources');
       
         
function cutv_get_source_videos($source) {

    global $wpdb;

    $video_ids = get_video_meta_ids('wpvr_video_sourceId', $source);

    cutv_log(DEBUG_LEVEL, '[cutv_get_source_videos] '. "[$source] CHANNEL VIDEO IDS: "); 
    cutv_log(DEBUG_LEVEL+1, $video_ids); 

    $videos = array();
    if (count($video_ids)) {
        foreach($video_ids as $video_id) {

            $post = get_post($video_id);
            $videos[$video_id] = cutv_wpvr_video_meta($video_id);
            $videos[$video_id]['id'] = $video_id;
            $videos[$video_id]['name'] = $post->post_name;
            $videos[$video_id]['title'] = $post->post_title;
            $videos[$video_id]['status'] = $post->post_status;
            $videos[$video_id]['thumbnail'] = wp_get_attachment_url( $videos[$video_id]['_thumbnail_id'] );
            // cutv_log(DEBUG_LEVEL, '[cutv_get_source_videos] '. "video:"); 
            cutv_log(DEBUG_LEVEL, $videos[$video_id]); 
        }
    }
    return $videos;
    
    
}


function cutv_get_sources_by_channel($channel_id, $count = false, $json = true) {

    global $wpdb;
    
    $channel_id = $_REQUEST['channel_id'] !== null ? $_REQUEST['channel_id'] : $channel_id;
        
    $channel_source_ids = cutv_get_source_meta_ids( CUTV_SOURCE_PID, $channel_id );
    
    $sources = [];
    
    foreach ($channel_source_ids as $source_id) {
        
        $wpvr_posts = cutv_wpvr_video_by_ids($wpdb->get_results( "SELECT video_id FROM " . WPVR_VIDEO_META ." WHERE meta_key = 'wpvr_video_sourceId' AND meta_value = '$source_id'"));
        $sorted_videos = cutv_sort_source_videos_by_status($wpvr_posts, $count);
        $source_meta = cutv_wpvr_source_meta($source_id);
        
        $sources[] = array(
            'source' => $source_meta,
            'videos' => $sorted_videos
        );

    }
    
    if ($json) {
        echo json_encode($sources);
    } else {
        return $sources;
    }
    
    die();
    
}
add_action('wp_ajax_nopriv_cutv_get_sources_by_channel', 'cutv_get_sources_by_channel');
add_action('wp_ajax_cutv_get_sources_by_channel', 'cutv_get_sources_by_channel');
        

//** ----------------------------------------- DEPRECATED ----------------------------------------------------- *// 

// function cutv_move_source_videos() {
    
//     global $wpdb;
    
//     if (isset($_REQUEST)) {
        
//         $source_id = $_REQUEST['currentSrc'];
//         $new_source = $_REQUEST['newSrc'];
//         $source_videos = $wpdb->get_results("SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key='wpvr_video_sourceId' AND meta_value=". $source_id );
        
//         foreach ($source_videos as $video) {
//             update_post_meta($video->post_id, 'wpvr_video_sourceId', $new_source);
//             cutv_log(DEBUG_LEVEL, "video $video->post_id was moved to $new_source");
//         }
        
//         if ($_REQUEST['movePlaylists'] == true) {
//             $new_playlists =  '';
//             $currentSrc_YT_playlists = get_post_meta($source_id, 'wpvr_source_playlistIds_yt', true);
//             $newSource_YT_playlists = get_post_meta($new_source, 'wpvr_source_playlistIds_yt', true);
            
//             cutv_log(4, "current youtube playlists:  $currentSrc_YT_playlists");
//             cutv_log(4, "new source youtube playlists:  $newSource_YT_playlists");
            
            
//             $playlists = explode(',', $newSource_YT_playlists);
//             foreach ($playlists as $playlist) {
//                 $playlists_exist = strrpos($currentSrc_YT_playlists, $newSource_YT_playlists);
//                 $new_playlists = ($playlists_exist === false) ? $currentSrc_YT_playlists.','.$newSource_YT_playlists : $currentSrc_YT_playlists;
//             }
            
//             update_post_meta($new_source, 'wpvr_source_playlistIds_yt', $new_playlists);
            
//             cutv_log(DEBUG_LEVEL, "all source youtube playlists after update:  ". $new_playlists);
            
//         }
        
//         $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $source_id ) );
//         $wpdb->delete( $wpdb->posts, array( 'ID' => $source_id ) );
        
//         // echo json_encode();
        
//     }
//     die();
    
// }
// add_action('wp_ajax_cutv_move_source_videos', 'cutv_move_source_videos');
    
    
    