<?php


function cutv_convert_snaptube() {
    

    if (isset($_REQUEST)) {
        global $wpdb;
        wp_suspend_cache_addition(true);

        $snaptube_ids = [];
        $method = $_REQUEST['method'];
        $wpvr_ids = explode(',', $_REQUEST['video_ids']);

        foreach ($wpvr_ids as $wpvr_id) {

            //* ATTEMPT TO PUBLISH WPVR VIDEO AS SNAPTUBE VIDEOS
            $wpvr_video_post = get_post($wpvr_id);
            $snaptube_post = $wpdb->get_row("SELECT * FROM " . $wpdb->posts . " WHERE post_type='videogallery' AND post_title ='" . $wpvr_video_post->post_title."'");
            $video_meta = cutv_wpvr_video_meta($wpvr_id);
            $channel_id = cutv_get_source_meta($video_meta['wpvr_video_sourceId'], CUTV_SOURCE_PID, true)->meta_value;
            $vids = $wpdb->get_results("SELECT vid, ordering FROM " . SNAPTUBE_VIDEOS ." ORDER BY vid DESC LIMIT 1");
            
            cutv_log(DEBUG_LEVEL, "[cutv_convert_snaptube] $method -> $wpvr_id");
            cutv_log(DEBUG_LEVEL, '- - - - - - - - - - - - - - - VIDEO META  - - - - - - - - - - - - - - -');
            cutv_log(DEBUG_LEVEL, $video_meta);
            cutv_log(DEBUG_LEVEL, $vids);
            
            
            // todo: make this a function to build snaptube video
            //? FIELDS TO CREATE THE SNAPTUBE VIDEO FROM WPVR VIDEO

            $ordering = $vids == null ? 1 : $vids[0]->ordering + 1;
            $vid = $vids == null ? 1 : $vids[0]->vid + 1;
            
            cutv_log(DEBUG_LEVEL, "vid => $vid, ordering: $ordering");

            $name = cutv_sanitize_title($wpvr_video_post->post_title);
            $description = $wpvr_video_post->post_content;
            $slug = $snaptube_post->ID || 0;
            $post_date = $video_meta['wpvr_video_service_date'];
            $member_id = $wpvr_video_post->post_author;
            $image = $video_meta['wpvr_video_service_thumb'];
            $opimage = $video_meta['wpvr_video_service_hqthumb'];
            $file = $video_meta['wpvr_video_service_url'];
            $link = $file;
            $featured = 1;
            $status = ($method === 'publish' ? 1 : 0);
            $file_type = 1;
            $duration = cutv_get_duration($wpvr_id);

            $converted_snaptube_video = array(
                $name, 
                $video_meta['wpvr_video_service_desc'], 
                $video_meta['wpvr_video_service_url'], 
                $slug,
                $file_type,
                $duration,
                $image,
                $opimage,
                $link,
                $featured,
                $post_date,
                $status,
                $member_id,
                $ordering
            );

            //* GET THE SNAPTUBE VIDEO
            $snaptube_video = $wpdb->get_row("SELECT * FROM " . SNAPTUBE_VIDEOS . " WHERE name ='" . $name ."' ORDER BY vid DESC LIMIT 1");

            if (!$snaptube_video) {

                // todo: extract to function
                //? SNAPTUBE VIDEO POST DATA
                $snaptube_video_post = array();
                $snaptube_video_post['post_title'] = $name;
                $snaptube_video_post['post_content']  = '[hdvideo id='.$vid.']';
                $snaptube_video_post['post_status']   = ($method === 'publish' ? $method : 'pending');
                $snaptube_video_post['post_author']   = $wpvr_video_post->post_author;
                $snaptube_video_post['post_type']   = 'videogallery';
                //* INSERT AS `video_gallery` WP POST
                $slug = wp_insert_post( $snaptube_video_post );
                $converted_snaptube_video[3] = $slug;
                
                // todo: make sure we need this
                // add_post_meta( $snaptube_id, '_vc_post_settings', 'a:1:{s:10:"vc_grid_id";a:0:{}}', true );
                // $snaptube_video_post['post_category'] = $categories;
                // add_post_meta( $wpvr_id, '_cutv_snaptube_video', $snaptube_id, true );
                // add_post_meta( $wpvr_id, '_cutv_snaptube_referential', $vid, true );
                
                //* ADD SNAPTUBE VIDEO
                $insert = $wpdb->prepare(
                    "INSERT INTO " . SNAPTUBE_VIDEOS .
                    " (name, description, file, slug, file_type, duration, image, opimage, link, featured, post_date, publish, member_id, ordering) VALUES ( %s, %s, %s, %d, %d, %s, %s, %s, %s, %d, %s, %d, %d, %d)",
                    $converted_snaptube_video
                );
                $wpdb->query($insert);

                cutv_log(DEBUG_LEVEL, "ADDED SNAPTUBE: $name");

            } else {
                //* UPDATE SNAPTUBE VIDEO
                $vid = $snaptube_video->vid;
                $wpdb->update( SNAPTUBE_VIDEOS, array( 'publish' => $status ), array( 'vid' => $vid ) );
                $slug = $snaptube_video->slug;

                cutv_log(DEBUG_LEVEL, "UPDATED SNAPTUBE: " . $snaptube_video->name ." (vid: $vid, slug: $slug)");
                
            }

            $snaptube_post = get_post($slug);
            cutv_log(DEBUG_LEVEL, "- - - - - - - - - - - - - - - SNAPTUBE POST - VID: $vid - - - - - - - - - - - - - - -");
            cutv_log(DEBUG_LEVEL, $snaptube_post);
            cutv_log(DEBUG_LEVEL, "SLUG: $snaptube_post->ID");


            //* UPDATE WPVR & SNAPTUBE VIDEO POST STATUS
            $wpdb->update( $wpdb->posts, array( 'post_status' => $method ), array( 'ID' => $wpvr_id ) );
            $wpdb->update( $wpdb->posts, array( 'post_status' => $method ), array( 'ID' => $snaptube_post->ID ) );
            array_push($snaptube_ids, $slug);

            //* UPDATE CHANNEL RELATION
            $channel_relation = $wpdb->get_row("SELECT * FROM " . SNAPTUBE_PLAYLIST_RELATIONS . " WHERE media_id=$vid");
            if (!$channel_relation) {
                cutv_log(DEBUG_LEVEL, '% ADDED CHANNEL RELATION %');
                $wpdb->insert( SNAPTUBE_PLAYLIST_RELATIONS, array( 'media_id' => $vid, 'playlist_id' => $channel_id ) );
            } else {
                $channel_relation = $wpdb->update( SNAPTUBE_PLAYLIST_RELATIONS, array( 'playlist_id' => $channel_id ), array( 'media_id' => $vid ) );
                cutv_log(DEBUG_LEVEL, '% UPDATED CHANNEL RELATION %');
            }

            $channel_relation = $wpdb->get_row("SELECT * FROM " . SNAPTUBE_PLAYLIST_RELATIONS . " WHERE media_id=$vid");


            cutv_log(DEBUG_LEVEL, "= = = = = = = = = = = = = CHANNEL RELATION = = = = = = = = = = = = = = = = = = = = = = =");
            
            cutv_log(DEBUG_LEVEL, $channel_relation);

            cutv_log(DEBUG_LEVEL, "= = = = = = = = = = = = = SNAPTUBE VIDEO = = = = = = = = = = = = = = = = = = = = = = =");
            //* GET THE SNAPTUBE VIDEO
            //todo: extract to helper
            $snaptube_video = $wpdb->get_row("SELECT * FROM " . SNAPTUBE_VIDEOS . " WHERE slug ='" . $slug."'");
            cutv_log(DEBUG_LEVEL, $snaptube_video);

            cutv_log(DEBUG_LEVEL, "= = = = = = = = = = = = = WPVR POST = = = = = = = = = = = = = = = = = = = = = = =");
            cutv_log(DEBUG_LEVEL, get_post($wpvr_id));


            // OTHER STUFF
            // todo: de we need this?
            // $categories = cutv_make_snaptube_cats($wpvr_id, $vid);
            // $tags = cutv_make_snaptube_tags($video['tags'], $wpvr_id);
        }
        
        cutv_log(DEBUG_LEVEL, "* * * * * * * * * * * * * * * UPDATED SNAPTUBES * * * * * * * * * * * * * * *", false);
        
        echo json_encode(cutv_get_snaptube_videos(implode(',', $snaptube_ids)));

    }

    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_convert_snaptube', 'cutv_convert_snaptube');
add_action('wp_ajax_nopriv_cutv_convert_snaptube', 'cutv_convert_snaptube');

//* not done yet
// todo: implement this so we can add video to unwanted list, and remove the snaptube video, the wpvr video and the video gallery video not implemented yet
function cutv_trash_snaptube_video() {


    if (isset($_REQUEST)) {
        global $wpdb;

        $video_ids = explode(',', $_REQUEST['videos']);

        foreach ($video_ids as $wpvr_post_id) {

            $snaptube_post_id = cutv_get_snaptube_post_id($wpvr_post_id);
            $vid = cutv_get_snaptube_vid($wpvr_post_id);

            // cutv_log(3, "[cutv_clear_snaptube_video][snaptube id] $snaptube_post_id");
            // cutv_log(3, "[cutv_clear_snaptube_video][vid] $vid");


            // CLEAN THE SNAPTUBE_VIDEOS
            $wpdb->delete( SNAPTUBE_VIDEOS, array( 'vid' => $vid ) );

            // CLEAN THE SNAPTUBE_PLAYLIST_RELATIONS (POSTS IN PLAYLISTS)
            $wpdb->delete( SNAPTUBE_PLAYLIST_RELATIONS, array( 'media_id' => $vid ) );

            // CLEAN THE SNAPTUBE_TAGS
            $wpdb->delete( SNAPTUBE_TAGS, array( 'media_id' => $vid ) );

            // CLEAN THE POSTS TABLE FROM THE SNAPTUBE VIDEOS
            $wpdb->delete( $wpdb->postmeta, array( 'post_id' => $wpvr_post_id ) );

            // CLEAN THE POSTS TABLE FROM THE SNAPTUBE VIDEOS
            $wpdb->delete( $wpdb->posts, array( 'ID' => $snaptube_post_id ) );
            $wpdb->delete( $wpdb->posts, array( 'ID' => $wpvr_post_id ) );


        }

        echo json_encode($video_ids);

    }

    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_trash_snaptube_video', 'cutv_trash_snaptube_video');
