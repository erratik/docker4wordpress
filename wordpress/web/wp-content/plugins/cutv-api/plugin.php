<?php
/*
Plugin Name: CUTV Admin Suite v2.0-beta
Plugin URI: http://erratik.ca
Version: 2.0-beta
Author: Tayana Jacques
Author URI: http://erratik.ca
*/


global $wpdb;

define('CUTV_DEBUG_LEVEL', 3);
function cutv_log($log_level, $log, $message = false) {
    
    if ($log_level >= CUTV_DEBUG_LEVEL) {
        if (gettype($log) == 'object' || gettype($log) == 'array') {
            print_r($log);
        } else {
            echo $log;
        }
        echo "\n" . PHP_EOL;
    }

}

/* DB CONSTANTS */
define('CUTV_MAIN_FILE', __FILE__);
define('CUTV_PLUGIN_FOLDER', dirname(__FILE__));
define('SNAPTUBE_VIDEOS', $wpdb->prefix . 'hdflvvideoshare');
define('SNAPTUBE_PLAYLISTS', $wpdb->prefix . 'hdflvvideoshare_playlist');
define('SNAPTUBE_PLAYLIST_RELATIONS', $wpdb->prefix . 'hdflvvideoshare_med2play');
define('SNAPTUBE_TAGS', $wpdb->prefix . 'hdflvvideoshare_tags');




/* Including functions definitions */
require_once('includes/plugin/cutv.definitions.php');

require_once('includes/plugin/cutv.functions.php');


// REST Routes
require_once(dirname(__FILE__) . '/includes/rest-routes.php');

// CUTV Channel Plugin
require_once(dirname(__FILE__) . '/includes/channel-plugin.php');

// Custom Templates
include_once(dirname(__FILE__) . '/includes/templater.php');


require_once('includes/plugin/cutv.hooks.php');
