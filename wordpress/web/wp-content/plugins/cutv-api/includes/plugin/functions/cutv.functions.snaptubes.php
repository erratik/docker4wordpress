<?php

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


function cutv_convert_snaptube() {

    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        global $wpdb;
        wp_suspend_cache_addition(true);

        $video_ids = explode(',', $_REQUEST['videos']);
        // cutv_log(4, count($videos) .' videos to edit!');

        foreach ($video_ids as $wpvr_id) {


            // ATTEMPT TO PUBLISH WPVR VIDEO AS SNAPTUBE VIDEOS
            $wpvr_video = get_post($wpvr_id);
            // $wpvr_video_meta = get_post_meta($wpvr_id);
            // SET FILE (youtube link) FIELDS, USED TO ADD/REMOVE SNAPTUBE VIDEO
            $file = get_post_meta($wpvr_id, 'wpvr_video_service_url', true);

            // FIELDS TO CREATE THE SNAPTUBE VIDEO FROM WPVR VIDEO
            $description = $wpvr_video->post_content;
            $post_date = get_post_meta($wpvr_id, 'wpvr_video_service_date', true);
            $name = $wpvr_video->post_title;
            $vid = $wpdb->get_results("SELECT vid FROM " . SNAPTUBE_VIDEOS ." ORDER BY vid DESC LIMIT 1");
            $vid = $vid == null ? 1 : $vid[0]->vid + 1;

            // cutv_log(3, $vid);



            $already_exists = $wpdb->get_row("SELECT vid FROM " . SNAPTUBE_VIDEOS . " WHERE file ='" . $file."'");

            if ($already_exists == null) {


                // CREATE POST DATA, USE THAT ID AS THE SLUG FOR THE VIDEO ROW
                $snaptube_video_post = array();
                $snaptube_video_post['post_title']    = $name;
                $snaptube_video_post['post_content']  = '[hdvideo id='.$vid.']';
                $snaptube_video_post['post_status']   = 'publish';
                $snaptube_video_post['post_author']   = $member_id;
                $snaptube_video_post['post_type']   = 'videogallery';
                $snaptube_video_post['post_type']   = 'videogallery';
                $snaptube_video_post['post_category'] = $categories;


                // INSERT THE POST TO wp_posts AS A video_gallery, WITH UNIQUE VC POST META
                $post_id = wp_insert_post( $snaptube_video_post );

                add_post_meta( $post_id, '_vc_post_settings', 'a:1:{s:10:"vc_grid_id";a:0:{}}', true );
                add_post_meta( $wpvr_id, '_cutv_snaptube_video', $post_id, true );
                add_post_meta( $wpvr_id, '_cutv_snaptube_referential', $vid, true );


                // FIELDS ADDED TO CREATE SNAPTUBE VIDEOS
                $slug = $wpvr_video->post_name;
                $member_id = $wpvr_video->post_author;
                $duration = convert_youtube_duration(get_post_meta($wpvr_id, 'wpvr_video_duration', true));
                $image = get_post_meta($wpvr_id, 'wpvr_video_service_thumb', true);
                $opimage = get_post_meta($wpvr_id, 'wpvr_video_service_hqthumb', true);
                $link = $file;

                // STANDARD SNAPTUBE VIDEO SHITg
                $featured       = 1;
                $download       = 0;
                $publish        = 1;
                $file_type      = 1;
                $islive         = 0;
                $amazon_buckets = 0;

                // GET ALL SNAPTUBE VIDEOS TO GET COUNT & ORDERING
                $videos = $wpdb->get_results("SELECT * FROM " . SNAPTUBE_VIDEOS);
                $ordering = count($videos) + 2;

                // INSERT INTO SNAPTUBE VIDEO TABLE (SNAPTUBE_VIDEOS)
                $query_vids = $wpdb->prepare("INSERT INTO " . SNAPTUBE_VIDEOS ." (vid, name, description, file, slug, file_type, duration, image, opimage, download, link, featured, post_date, publish, islive, member_id, ordering, amazon_buckets) VALUES ( %d, %s, %s, %s, %d, %d, %s, %s, %s, %d, %s, %d, %s, %d, %d, %d, %d, %d )",
                    array($vid, sanitizeTitle($name), $description, $file, $post_id, $file_type, $duration, $image, $opimage, $download, $link, $featured, $post_date, $publish, $islive, $member_id, $ordering, $amazon_buckets)
                );

                $wpdb->query($query_vids);

            } else {
                $vid = $already_exists->vid;
                $wpdb->update( $wpdb->posts, array( 'post_status' => 'publish' ), array( 'ID' => $wpvr_id ) );
                // cutv_log(4, $wpvr_id);

            }

            // FIELDS TO CREATE THE SNAPTUBE VIDEO POST (THIS IS THE POST DISPLAYED ON THE SITE)
            $categories = cutv_make_snaptube_cats($wpvr_id, $vid);
            $tags = cutv_make_snaptube_tags($video['tags'], $wpvr_id);


        }

        $args = array(
            'numberposts' => -1,
            'post_status'    => 'any',
            'include'   => $_REQUEST['videos']
        );

        echo json_encode(cutv_get_snaptube_posts($args));

    }

    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_convert_snaptube', 'cutv_convert_snaptube');
// add_action('wp_ajax_nopriv_cutv_convert_snaptube', 'cutv_convert_snaptube');

function cutv_unpublish_snaptube_video() {

    // The $_REQUEST contains all the data sent via ajax
    if (isset($_REQUEST)) {
        global $wpdb;

        // print_r($_REQUEST['videos']);
        $video_ids = explode(',', $_REQUEST['videos']);

        foreach ($video_ids as $wpvr_id) {

            $snaptube_id = cutv_get_snaptube_vid($wpvr_id);

           // CLEAN THE SNAPTUBE_VIDEOS
            $wpdb->delete( SNAPTUBE_VIDEOS, array( 'vid' => $snaptube_id ) );

            $wpdb->update( $wpdb->posts, array( 'post_status' => 'pending' ), array( 'ID' => $wpvr_id ) );

            // CLEAN THE SNAPTUBE_PLAYLIST_RELATIONS (POSTS IN PLAYLISTS)
            $wpdb->delete( SNAPTUBE_PLAYLIST_RELATIONS, array( 'media_id' => $snaptube_id ) );

            $wpdb->delete( $wpdb->posts, array( 'ID' => cutv_get_snaptube_post_id($wpvr_id) ) );


        }

        $args = array(
            'numberposts' => -1,
            'post_status'    => 'any',
            'include'   => $_REQUEST['videos']
        );

        echo json_encode(cutv_get_snaptube_posts($args));
    }

    // Always die in functions echoing ajax content
    die();
}
add_action('wp_ajax_cutv_unpublish_snaptube_video', 'cutv_unpublish_snaptube_video');


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

// snaptube functions

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

// todo: check usage
function cutv_get_snaptube_video($id) {
    global $wpdb;
    // GET TEH SNAPTUBE VIDEO USING THE WPVR VIDEO ID
    return $wpdb->get_row( "SELECT * FROM " . SNAPTUBE_VIDEOS ." WHERE slug = $id");
}
