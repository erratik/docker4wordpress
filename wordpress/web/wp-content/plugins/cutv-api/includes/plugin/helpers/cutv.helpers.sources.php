<?php

define('DEBUG_LEVEL', 2);

// get the source_id field from sources that match key/value
function get_source_meta_ids( $meta_key, $meta_value) {
    global $wpdb;
    
    $query_string = "SELECT source_id FROM " . WPVR_SOURCE_META . " WHERE meta_key = '$meta_key' AND meta_value = '$meta_value'";
    $rows = $wpdb->get_results($query_string);
    
    cutv_log(DEBUG_LEVEL, '[get_source_meta_ids] '. $query_string);
    
    foreach ($rows as $meta_row) {
        $source_ids[] = $meta_row->source_id;
    }
    
    return $source_ids;
}

function get_source_meta($source_id, $meta_key, $meta_value = null, $return_meta = true)  {
    global $wpdb;
    
    $query_string = "SELECT * FROM " . WPVR_SOURCE_META . " WHERE source_id = $source_id AND meta_key = '$meta_key'";
    cutv_log(DEBUG_LEVEL, '[get_source_meta_ids] '. $query_string);

    return $wpdb->get_row($query_string);
}

function add_source_meta($source_id, $meta_key, $meta_value = null, $return_meta = true) {
    global $wpdb;
    
    $insert = array( 
        'source_id' => intval($source_id),	   
        'meta_key' => $meta_key,	   
        'meta_value' => $meta_value,	   
        'key_id' => 0,
    );
    
    if ($meta_value) {
        $insert['meta_value'] = $meta_value;
    }
    
    $result = $wpdb->insert( WPVR_SOURCE_META, $insert);

    return meta_action_result($source_id, $meta_key, $meta_value, $return_meta, $result, 'add_source_meta');
    
}

function upsert_source_meta($source_id, $meta_key, $meta_value = false, $return_meta = true) {
    global $wpdb;
    
    $result;
    $update = update_source_meta($source_id, $meta_key, $meta_value, false);
    if ($update === false) {
        cutv_log(DEBUG_LEVEL+1, '[upsert_source_meta] failure updating, trying insert');
        
        cutv_log(DEBUG_LEVEL-1, '[upsert_source_meta] check insert query');
        cutv_log(DEBUG_LEVEL-1, $insert);
        
        $result = update_source_meta($source_id, $meta_key, $meta_value, false);
        return meta_action_result($source_id, $meta_key, $meta_value, false, $result, 'upsert_source_meta');

    } 
    
    return meta_action_result($source_id, $meta_key, $meta_value, $return_meta, $result, 'upsert_source_meta');
}

function update_source_meta($source_id, $meta_key, $meta_value, $return_meta = true) {
    global $wpdb;
    
    $data = array( 
        'meta_key' => $meta_key,	
        'meta_value' => $meta_value,
    );
    
    $data_format = array( 
		'%s',	// string
		'%s',	// string
    );

    $where = array( 
        'source_id' => intval($source_id),
        'meta_key' => $meta_key,
    );

    $where_format = array( 
		'%d',   // number
		'%s'	// string
    );

    cutv_log(DEBUG_LEVEL-1, '[update_source_meta] update -> '. json_encode($data, JSON_UNESCAPED_UNICODE));
    cutv_log(DEBUG_LEVEL-1, '[update_source_meta] where -> '. json_encode($where, JSON_UNESCAPED_UNICODE));
    cutv_log(DEBUG_LEVEL-1, '[update_source_meta] query -> '.json_encode(array(WPVR_SOURCE_META, $data, $where, $data_format, $where_format), JSON_UNESCAPED_UNICODE));
    
    $result = $wpdb->update(WPVR_SOURCE_META, $data, $where, $data_format, $where_format);
    
    return meta_action_result($source_id, $meta_key, $meta_value, $return_meta, $result, 'update_source_meta');
    
}

function delete_source_meta($source_ids, $report = false) {
    global $wpdb;
    $results = [];
    foreach ($source_ids as $source_id) {    

        $delete = array( 'source_id' => intval($source_id), 'meta_key' => CUTV_SOURCE_PID);

        cutv_log(DEBUG_LEVEL-2, '[delete_source_meta] $delete -> '. json_encode( $delete ));
        $result = $wpdb->delete( WPVR_SOURCE_META, $delete );

        $results[] = $result;
    }
    cutv_log(DEBUG_LEVEL-1, '[delete_source_meta] ' . min($results) );
    return min($results);
}

function meta_action_result($source_id, $meta_key, $meta_value, $return_meta, $result, $from = null) {
    if (false === $result) {
        return false;
    } elseif($return_meta) {
        cutv_log(DEBUG_LEVEL-1, "[$from] returning meta");
        return get_source_meta($source_id, $meta_key, $meta_value);
    } else {
        return $result;
    }
}

function cutv_get_sources($channel_id = null) {

    $args = array(
        'post_type' => 'wpvr_source',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    );

    if ($channel_id) {
        $insert['ID'] = $channel_id;
    }

    return get_posts( $args );
    
}
