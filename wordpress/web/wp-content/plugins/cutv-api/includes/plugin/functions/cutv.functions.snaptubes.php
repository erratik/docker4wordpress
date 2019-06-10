<?php


function cutv_convert_snaptube() {
    
    // The $_REQUEST contains all the data sent via ajax
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
            $channel_id = get_source_meta($video_meta['wpvr_video_sourceId'], CUTV_SOURCE_PID, true)->meta_value;
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

            $name = sanitizeTitle($wpvr_video_post->post_title);
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
            
            // FORMAT DURATION
            $re = '/^PT(\d+H)?(\d+M)?(\d+S)?$/';
            $subst = '$1$2$3';
            $duration = substr(preg_replace('/\D/', ':', preg_replace($re, $subst, $video_meta['wpvr_video_duration'])), 0, -1);

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
// add_action('wp_ajax_nopriv_cutv_convert_snaptube', 'cutv_convert_snaptube');


function cutv_trash_snaptube_video() {

    // The $_REQUEST contains all the data sent via ajax
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

//* ------------------------------ snaptube helpers ------------------------------
// todo: move to a helper file
function cutv_get_snaptube_post_id($wpvr_id) {
    return get_post_meta($wpvr_id, '_cutv_snaptube_video', true);
}

function cutv_get_wpvr_post($id) {
    global $wpdb;
    $snaptube_video_post = $wpdb->get_row( "SELECT * FROM " . SNAPTUBE_VIDEOS ." WHERE slug = $id");
    // GET TEH WPVR POST USING THE WPVR VID
    return $wpdb->get_row( "SELECT * FROM $wpdb->posts WHERE slug = $id");
}

function cutv_get_snaptube_vid($id) {
    global $wpdb;

    $wpvr_video = get_post($id);
    // print_r($wpvr_video);
    $file = get_post_meta($id, 'wpvr_video_service_url', true);
    $snaptube_video = $wpdb->get_row( "SELECT vid FROM " . SNAPTUBE_VIDEOS ." WHERE file = '".$file."'");

    // GET TEH SNAPTUBE VIDEO USING THE WPVR VIDEO ID
    return $snaptube_video->vid;
}

function cutv_get_snaptube_posts($args) {

        $video_posts = get_posts( $args );

        // find the youtube thumb
        foreach($video_posts as $video_post) {
            $video_post = cutv_get_snaptube_post_data($video_post, $video_post->ID);
        }

        return $video_posts;
}


//!----------------------------------------- OLDER FUNCTIONS TO BE REFACTORED ----------------------------------------------------------------------


function cutv_make_snaptube_tags($tags, $wpvr_id) {
    global $wpdb;

    $vid = cutv_get_snaptube_vid($wpvr_id);

    // INSERT INTO SNAPTUBE_TAGS TABLE
    // @this is the category table sort of
    // echo '[tags ('.count($tags).')] ', "\n";
    if ($tags != null) {

        $t = 0;
        $tag_str = '';
        $safe_concat_str = '';
        foreach ($tags as $tag_id) {
            // get the tag content
            $post_tags = get_term( $tag_id );
            if ($post_tags != null) {
                $tag_str .= $post_tags->name;
                $safe_concat_str .= hyphenize($post_tags->name);
                if (count($tags)-1 != $t) {
                    $tag_str .= ',';
                    $safe_concat_str .= '-';
                }
                $t++;
            }
        }

        // update the tags if the video already has tags (find media_id)
        $tags_exist = $wpdb->get_results('SELECT * FROM ' . SNAPTUBE_TAGS . " WHERE media_id=". $vid );

        if (count($tags_exist)) {
            $updated = $wpdb->update(
                SNAPTUBE_TAGS,
                array(
                    'seo_name' => strtolower($safe_concat_str),   // integer (number)
                    'tags_name' => $tag_str    // integer (number)
                ),
                array('media_id' => $vid),
                array(
                    '%s',
                    '%s'
                ),
                array('%d')
            );

        } else {


            // get the tag rows, getting the last vtag_id as new row key
            $snaptube_tags = $wpdb->get_results("SELECT * FROM " . SNAPTUBE_TAGS ." ORDER BY vtag_id DESC LIMIT 1");
            $new_tag_id = $snaptube_tags[0]->vtag_id + 1;

            $query_tags = $wpdb->prepare("INSERT INTO " . SNAPTUBE_TAGS . " (vtag_id, tags_name, seo_name, media_id) VALUES ( %d, %s, %s, %d) ",
                array($new_tag_id, $tag_str, strtolower($safe_concat_str), $vid)
            );

            $wpdb->query($query_tags);


        }

    }
}

function cutv_make_snaptube_cats($video_id, $snaptube_video, $channel_id) {

    global $wpdb;
    // INSERT INTO SNAPTUBE_PLAYLIST_RELATIONS TABLE

    $snaptube_vid = $snaptube_video;

    $source_id = get_post_meta($video_id, 'wpvr_video_sourceId', true);
    // cutv_log(3, '[cutv_make_snaptube_cats::video source id] '. $video_id. " => ". $snaptube_video. ': '.get_post_meta($video_id,  'wpvr_video_sourceName', true));

    $wpvr_source_cat = get_post_meta($source_id, 'wpvr_source_postCats_', true);

    $med2play = $wpdb->get_row("SELECT rel_id FROM " . SNAPTUBE_PLAYLIST_RELATIONS . " ORDER BY rel_id DESC LIMIT 1");

    $rel_id = $med2play->rel_id + 1;

    $playlist_id = $wpdb->get_row("SELECT playlist_id FROM " . SNAPTUBE_PLAYLIST_RELATIONS . " WHERE media_id = " . $snaptube_vid);

    if (null != $playlist_id) {

        // cutv_log(4, "[cutv_make_snaptube_cats] removing all previous relations");

        $updated = $wpdb->delete(
            SNAPTUBE_PLAYLIST_RELATIONS,
            array('media_id' => $snaptube_vid )
        );

    }


    // INSERT INTO SNAPTUBE_PLAYLIST_RELATIONS TABLE
    // cutv_log(3, "[cutv_make_snaptube_cats] rel_id => " . $rel_id . ",  new playlist value => " . $wpvr_source_cat);


    $query_med2play = $wpdb->prepare("INSERT INTO " . SNAPTUBE_PLAYLIST_RELATIONS . " (rel_id, media_id, playlist_id, porder, sorder) VALUES ( %d, %d, %d, %d, %d ) ",
        array($rel_id, $snaptube_vid, $wpvr_source_cat, 0, 0)
    );

    $wpdb->query($query_med2play);

    return $wpvr_source_cat;
}

// function cutv_unpublish_snaptube_video() {

//     // The $_REQUEST contains all the data sent via ajax
//     if (isset($_REQUEST)) {
//         global $wpdb;

//         // print_r($_REQUEST['videos']);
//         $video_ids = explode(',', $_REQUEST['videos']);

//         foreach ($video_ids as $wpvr_id) {

//             $snaptube_id = cutv_get_snaptube_vid($wpvr_id);

//            // CLEAN THE SNAPTUBE_VIDEOS
//             $wpdb->delete( SNAPTUBE_VIDEOS, array( 'vid' => $snaptube_id ) );

//             $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $wpvr_id ) );

//             // CLEAN THE SNAPTUBE_PLAYLIST_RELATIONS (POSTS IN PLAYLISTS)
//             $wpdb->delete( SNAPTUBE_PLAYLIST_RELATIONS, array( 'media_id' => $snaptube_id ) );

//             $wpdb->delete( $wpdb->posts, array( 'ID' => cutv_get_snaptube_post_id($wpvr_id) ) );


//         }

//         $args = array(
//             'numberposts' => -1,
//             'post_status'    => 'any',
//             'include'   => $_REQUEST['videos']
//         );

//         echo json_encode(cutv_get_snaptube_posts($args));
//     }

//     // Always die in functions echoing ajax content
//     die();
// }
// add_action('wp_ajax_cutv_unpublish_snaptube_video', 'cutv_unpublish_snaptube_video');
