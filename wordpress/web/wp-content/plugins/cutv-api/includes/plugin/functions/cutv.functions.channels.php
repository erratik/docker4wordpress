<?php


function cutv_add_channel()
{

    
    if (isset($_REQUEST)) {
        global $wpdb;

        $channel_name = $_REQUEST['channelName'];
        $slug = sanitize_title_with_dashes($channel_name);

        $term_parent = $wpdb->get_results("SELECT term_id FROM " . $wpdb->postmeta . " WHERE slug='channels'" )->term_id;

        $channel_id = wp_insert_category(
            array(
                'cat_name' => $channel_name,
                // 'category_description' => '',
                'category_nicename' => $slug,
                'category_parent' => $term_parent
            )
        );

        // Set Channel Status & Visibility
        if (isset($_REQUEST['featured'])) {
            update_term_meta($channel_id, 'cutv_channel_featured', $_REQUEST['featured']);
        }

        if (isset($_REQUEST['enabled'])) {
            update_term_meta($channel_id, 'cutv_channel_enabled', $_REQUEST['enabled']);
        }


        $playlists = $wpdb->get_results( 'SELECT * FROM ' . SNAPTUBE_PLAYLISTS );
        $query = $wpdb->prepare("INSERT INTO " . SNAPTUBE_PLAYLISTS . " (pid, playlist_name, playlist_slugname, playlist_desc, is_publish, playlist_order) VALUES ( %d, %s, %s, %s, %d, %d )",
            array($channel_id, $channel_name, $slug, '', 1, count($playlists))
        );
        $wpdb->query($query);

        echo json_encode(cutv_get_channel($channel_id));

    }

    die();
}
add_action('wp_ajax_cutv_add_channel', 'cutv_add_channel');


function cutv_update_channel() {

    global $wpdb;
    
    if (isset($_REQUEST)) {

        $channel_id = $_REQUEST['channel'];

        // channel featured = shows up in .cutv-channels-top
        if (isset($_REQUEST['featured'])) {
            $enabled = $_REQUEST['featured'];
            update_term_meta($channel_id, 'cutv_channel_featured', $enabled);
        }

        // channel exists, can be worked on, but not visible on website
        if (isset($_REQUEST['enabled'])) {
            $enabled = $_REQUEST['enabled'];
            update_term_meta($channel_id, 'cutv_channel_enabled', $enabled);
        }

        // update channel image
        if (isset($_REQUEST['image']) && $_REQUEST['image'] !== '') {
            update_term_meta($channel_id, 'cutv_channel_img', $_REQUEST['image']);
        }

        // update channel term & meta
        if (isset($_REQUEST['name'])) {
            $channel_name = $_REQUEST['name'];
            $channel_slug = sanitize_title_with_dashes($channel_name);

            // update snaptube playlist name & slugname
            $wpdb->update(
                SNAPTUBE_PLAYLISTS,
                array(
                    'playlist_name' => $channel_name,	// string
                    'playlist_slugname' => $channel_slug	// integer (number)
                ),
                array( 'pid' => $channel_id ),
                array(
                    '%s',	// value1
                    '%s',	// value1
                ),
                array( '%d' )
            );
            // update term name & slug
            $wpdb->update(
                $wpdb->terms,
                array(
                    'name' => $channel_name,	// string
                    'slug' => $channel_slug	// integer (number)
                ),
                array( 'term_id' => $channel_id ),
                array(
                    '%s',	// value1
                    '%s',	// value1
                ),
                array( '%d' )
            );
        }

        $channel = cutv_get_channel($channel_id);

        header('Content-Type: application/json');
        echo json_encode($channel);
    }

    
    die();

}
add_action('wp_ajax_cutv_update_channel', 'cutv_update_channel');


function cutv_get_channel($channel_id) {
    global $wpdb;
    $channel = $wpdb->get_row("SELECT pid, playlist_name, playlist_slugname FROM " . SNAPTUBE_PLAYLISTS ." WHERE pid = $channel_id", ARRAY_A );
    $channel['cutv_channel_img'] = get_term_meta( $channel_id, 'cutv_channel_img', true );
    $channel['enabled'] = filter_var(get_term_meta( $channel_id, 'cutv_channel_enabled', true ), FILTER_VALIDATE_BOOLEAN);
    $channel['featured'] = filter_var(get_term_meta( $channel_id, 'cutv_channel_featured', true ), FILTER_VALIDATE_BOOLEAN);
    return $channel;
}
add_action('wp_ajax_nopriv_cutv_get_channel', 'cutv_get_channel');
add_action('wp_ajax_cutv_get_channel', 'cutv_get_channel');

function cutv_get_channels() {
    global $wpdb;

    $channel_id = isset($_REQUEST) && $_REQUEST['channel_id'] ? $_REQUEST['channel_id'] : 0;
    $exclude_sources =  $_REQUEST['exclude_sources'] ? $_REQUEST['exclude_sources'] : 0;

    if ($channel_id) {
        $countVideos = $_REQUEST['count'] ? true : false;
        
        echo $exclude_sources ? json_encode(cutv_get_channel($channel_id)) : json_encode(array(
            'channel' => cutv_get_channel($channel_id),
            'sources' => cutv_get_sources_by_channel($channel_id, $countVideos, false),
        ));

    } else {

        $channels = [];
        $channels_rows = $wpdb->get_results("SELECT * FROM " . SNAPTUBE_PLAYLISTS ." WHERE pid > 1" );
        foreach ($channels_rows as $channel) {
            $channels[] = $exclude_sources ? cutv_get_channel($channel->pid) : array(
                'channel' => cutv_get_channel($channel->pid),
                'sources' => cutv_get_sources_by_channel($channel->pid, $countVideos, false),
            );
        };

        if (isset($_REQUEST) && isset($_REQUEST['json'])) {
            echo json_encode($channels);
        } else {
            return $channels;
        }
    }

    die();

}
add_action('wp_ajax_nopriv_cutv_get_channels', 'cutv_get_channels');
add_action('wp_ajax_cutv_get_channels', 'cutv_get_channels');


function cutv_feature_channel_videos($channel_id) {

    global $wpdb;

    if (isset($_REQUEST['channel_id']) && $_REQUEST['video'] !== '') {
        update_term_meta($_REQUEST['channel_id'], 'cutv_featured_video', $_REQUEST['video']);
    }

    die();
}
add_action('wp_ajax_cutv_feature_channel_videos', 'cutv_feature_channel_videos');


function cutv_delete_channel()
{
    if (isset($_REQUEST)) {
        global $wpdb;

        $channel_id =  $_REQUEST['channel'];

        wp_delete_category($channel_id);
        $wpdb->delete( $wpdb->termmeta, array( 'term_id' => $channel_id ) );
        $wpdb->delete( $wpdb->postmeta, array( 'wpvr_source_postCats_' => $channel_id ) );
        $wpdb->delete( SNAPTUBE_PLAYLISTS, array( 'pid' => $channel_id ) );
        $wpdb->delete( SNAPTUBE_PLAYLIST_RELATIONS, array( 'playlist_id' => $channel_id ) );

        header("HTTP/1.1 200 Ok");

    }

    die();
}
add_action('wp_ajax_cutv_delete_channel', 'cutv_delete_channel');

//! MOVE THIS!
function get_the_catalog_cat( $id = false ) {
    $categories = get_the_terms( $id, 'catablog-terms' );
    if ( ! $categories || is_wp_error( $categories ) )
        $categories = array();

    $categories = array_values( $categories );

    foreach ( array_keys( $categories ) as $key ) {
        _make_cat_compat( $categories[$key] );
    }

    /**
     * Filters the array of categories to return for a post.
     *
     * @since 3.1.0
     * @since 4.4.0 Added `$id` parameter.
     *
     * @param array $categories An array of categories to return for the post.
     * @param int   $id         ID of the post.
     */
    return apply_filters( 'get_the_categories', $categories, $id );
}
