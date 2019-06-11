<?php

define('DEBUG_LEVEL', 2);

function cutv_get_snaptube_post_data($video_post, $wpvr_id) {
  $video_post->snaptube_vid = intval(cutv_get_snaptube_vid($wpvr_id));
  $video_post->snaptube_id = intval(cutv_get_snaptube_post_id($wpvr_id));
  $video_post->source_id = intval(get_post_meta( $wpvr_id, 'wpvr_video_sourceId', true ));
  $video_post->featured_on_channel = get_post_meta($wpvr_id, '_cutv_featured_channel', true);
  $video_post->youtube_thumbnail = get_post_meta($wpvr_id, 'wpvr_video_service_thumb', true );
  $video_post->video_duration = convert_youtube_duration(get_post_meta($wpvr_id, 'wpvr_video_duration', true));

  if ($video_post->snaptube_vid == ! null) {
      $video_post->post_status = 'publish';
  }
  return $video_post;
}

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
