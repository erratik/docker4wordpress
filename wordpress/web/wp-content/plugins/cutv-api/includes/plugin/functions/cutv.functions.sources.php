<?php

define( 'DEBUG_LEVEL', 2);
include_once(CUTV_PLUGIN_FOLDER . '/includes/sources/cutv.sources.helpers.php');

function cutv_get_sources_info($channel_id = null) {
    
    global $wpdb;
    
    $sources = cutv_get_sources($channel_id);
    
    foreach ($sources as $source) {
        
        $cutv_meta = get_source_meta($source->ID, CUTV_SOURCE_PID, true);
        $wpvr_meta = get_source_meta($source->ID, 'wpvr_source_name', true);
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
    
    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_get_sources_info', 'cutv_get_sources_info');

function cutv_update_channel_sources() {
    
    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {

        $channel_id = $_REQUEST['channel'];
        
        cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "CUTV_SOURCE_PID: ".CUTV_SOURCE_PID, false);
        
        $source_ids = get_source_meta_ids(CUTV_SOURCE_PID, $channel_id );
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

    $channel_source_ids = get_source_meta_ids(CUTV_SOURCE_PID, $channel_id );

    cutv_log(DEBUG_LEVEL-1, '[cutv_update_channel_sources] '. "CHANNEL SOURCE IDS: ");    
    echo json_encode($channel_source_ids);
    die();
}
add_action('wp_ajax_cutv_update_channel_sources', 'cutv_update_channel_sources');
       
         
//!----------------------------------------- OLDER FUNCTIONS TO BE REFACTORED ----------------------------------------------------------------------


function get_postmeta_ids( $meta_key, $meta_value) {
    
    global $wpdb;
    
    $r = $wpdb->get_results("SELECT post_id FROM wp_postmeta WHERE meta_key = '$meta_key' AND meta_value = '$meta_value'") ;
    
    // cutv_log(1, '[get_postmeta_ids] '. "SELECT post_id FROM wp_postmeta WHERE meta_key = '$key' AND meta_value = $meta_value", true);
    foreach ($r as $meta_row) {
        $post_ids[] = $meta_row->post_id;
    }
    return $post_ids;
}
function cutv_get_sources_by_channel($channel_id) {
    $abridged = true;
    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        $channel_id = $_REQUEST['channel_id'];
        $abridged = false;
    }
    
    $source_ids = get_postmeta_ids( CUTV_SOURCE_PID, $channel_id );
    
    if ($source_ids == null) {
        echo json_encode([]);
        die();
    }
    
    if ($abridged == false) {
        
        $sources = [];
        foreach ($source_ids as $source_id) {
            
            $args = array(
                'numberposts' => -1,
                'post_type'   => 'wpvr_video',
                'post_status' => 'any',
                'meta_query' => array(
                    array(
                        'key'   => 'wpvr_video_sourceId',
                        'value' => $source_id
                        )
                        )
                    );
                    
            $posts =  cutv_get_snaptube_posts($args);
            
            $source_videos = new stdClass;
            $source_videos->unpublished = [];
            $source_videos->published = [];
            $source_videos->pending = [];
            
            // cutv_log(DEBUG_LEVEL, $posts, true);
            
            
            foreach ($posts as $post) {
                cutv_log(DEBUG_LEVEL, 'vid: '. $post->snaptube_vid . ' => '. get_post_meta($post->ID, '_cutv_snaptube_video', true) . ' (' . $post->post_status.') ' . $post->post_title, true);
                
                if ($post->post_status == 'pending') { // pending
                    
                    $source_videos->unpublished[] = $post->ID;
                } elseif ($post->post_status == 'draft') { // draft
                    $source_videos->pending[] = $post->ID;
                } else { // publish
                    $source_videos->published[] = $post->ID;
                }
            }
            
            
            
            $sources[] = (object) [
                'source_id' => $source_id,
                'source_video_counts' => $source_videos,
                'source_name' => get_post_meta( $source_id, 'wpvr_source_name', true )
            ];
        }
        
        echo json_encode($sources);
        
    } else {
        
        return $source_ids;
        
    }
    
    die();
    
}
add_action('wp_ajax_cutv_get_sources_by_channel', 'cutv_get_sources_by_channel');
        



function cutv_get_source_video_posts($source_id) {

global $wpdb;

if (isset($_REQUEST)) {
    $source_id = $_REQUEST['source_id'];
    $args = array(
        'numberposts' => -1,
        'post_type'   => 'wpvr_video',
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key'   => 'wpvr_video_sourceId',
                'value' => $source_id
                )
                )
            );
            
            $video_posts = get_posts( $args );
            
            // find the youtube thumb
            foreach($video_posts as $video_post) {
                $video_post = cutv_get_snaptube_post_data($video_post, $video_post->ID);
            }
            
            echo json_encode($video_posts);
        }
        die();
    }
    add_action('wp_ajax_cutv_get_source_video_posts', 'cutv_get_source_video_posts');
    
    function cutv_get_source_videos($source_id, $meta = true) {
        
        global $wpdb;
        
        // find out how many videos are in that source?
        $source_videos = $wpdb->get_results("SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key='wpvr_video_sourceId' AND meta_value=". $source_id );
        $response = null;
        $source_videos_extended = [];
        if (count($source_videos)) {
            
            foreach ($source_videos as $video) {
                $snaptube_video = get_post_meta( $video->post_id, '_cutv_snaptube_video', true );
                
                if (!$snaptube_video) {
                    $response['unpublished_videos'][]= $video->post_id;
                } else {
                    $response['published_videos'][] = $video->post_id;
                    // if i want meta, i probably don't don't want the entire video info
                    if (!$meta) {
                        echo '[cutv_get_source_videos] snaptube post id for ' . $video->post_id, ': ', $snaptube_video, "\n";
                        $response['videos'][] = cutv_get_snaptube_video( get_post_meta( $video->post_id, '_cutv_snaptube_video', true ));
                    }
                }
                
                
            }
        }
        
        return $meta ? $response : count($source_videos);
    }
    
    

function cutv_move_source_videos() {
    
    global $wpdb;
    
    if (isset($_REQUEST)) {
        
        $source_id = $_REQUEST['currentSrc'];
        $new_source = $_REQUEST['newSrc'];
        $source_videos = $wpdb->get_results("SELECT * FROM " . $wpdb->postmeta . " WHERE meta_key='wpvr_video_sourceId' AND meta_value=". $source_id );
        
        foreach ($source_videos as $video) {
            update_post_meta($video->post_id, 'wpvr_video_sourceId', $new_source);
            cutv_log(DEBUG_LEVEL, "video $video->post_id was moved to $new_source");
        }
        
        if ($_REQUEST['movePlaylists'] == true) {
            $new_playlists =  '';
            $currentSrc_YT_playlists = get_post_meta($source_id, 'wpvr_source_playlistIds_yt', true);
            $newSource_YT_playlists = get_post_meta($new_source, 'wpvr_source_playlistIds_yt', true);
            
            cutv_log(4, "current youtube playlists:  $currentSrc_YT_playlists");
            cutv_log(4, "new source youtube playlists:  $newSource_YT_playlists");
            
            
            $playlists = explode(',', $newSource_YT_playlists);
            foreach ($playlists as $playlist) {
                $playlists_exist = strrpos($currentSrc_YT_playlists, $newSource_YT_playlists);
                $new_playlists = ($playlists_exist === false) ? $currentSrc_YT_playlists.','.$newSource_YT_playlists : $currentSrc_YT_playlists;
            }
            
            update_post_meta($new_source, 'wpvr_source_playlistIds_yt', $new_playlists);
            
            cutv_log(DEBUG_LEVEL, "all source youtube playlists after update:  ". $new_playlists);
            
        }
        
        $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $source_id ) );
        $wpdb->delete( $wpdb->posts, array( 'ID' => $source_id ) );
        
        // echo json_encode();
        
    }
    die();
    
}
add_action('wp_ajax_cutv_move_source_videos', 'cutv_move_source_videos');
    
    
    