<?php

    function sanitize_title($string) {
        return preg_replace(
            array('#[^A-Za-z0-9\-.\' ]#'),
            array(''),
            urldecode($string)
        );
    }

    function hyphenize($string) {
        return strtolower(
            preg_replace(
                array('#[\\s-]+#', '#[^A-Za-z0-9\-]+#'),
                array('-', ''),
                urldecode($string)
            )
        );
    }

    function get_numerics ($str) {
        preg_match_all('/\d+/', $str, $matches);
        return $matches[0];
    }

    //! not used yet, but useful..

    function convert_youtube_duration($youtube_duration) {
        $re = '/(PT)(\d*)(M)(\d*)(S)/';
        $subst = '$2:$4';

        $result = preg_replace($re, $subst, $youtube_duration);

        return $result;
    }


	/* Get Durations in seconds */
	if( ! function_exists( 'cutv_duration_to_seconds' ) ) {
		function cutv_duration_to_seconds( $duration ) {
			if( $duration == '' ) return 0;

			if( $duration == '' || $duration == '0' ) return 0;
			elseif( $duration == 'PTS' ) return 0;
			else {
				$durationObj = new DateInterval( $duration );

				return ( 60 * 60 * $durationObj->h ) + ( 60 * $durationObj->i ) + $durationObj->s;
			}
		}
	}
	
    /* Get Video Formated Duration by post id */
    
    function cutv_get_duration( $post_id = '' , $return_seconds = FALSE ) {
        if( $post_id == '' ) {
            global $post;
            $post_id = $post->ID;
        }
        $duration = get_post_meta( $post_id , 'wpvr_video_duration' , TRUE );
        $r        = cutv_get_duration_string( $duration , $return_seconds );

        return $r;
    }
        

    /* Get Video Formated Duration by post id */
    if( ! function_exists( 'cutv_get_duration_string' ) ) {
        function cutv_get_duration_string( $duration = '' , $return_seconds = FALSE ) {
            if( $duration == '' ) {
                return '';
            }
            if( $duration == '' || $duration == '0' ) return 'xx:xx:xx';
            elseif( $duration == 'PTS' ) return 'xx:xx:xx';
            else {
                $durationObj = new DateInterval( $duration );
                $duration    = ( 60 * 60 * $durationObj->h ) + ( 60 * $durationObj->i ) + $durationObj->s;
            }

            //new dBug( $durationObj );

            if( $return_seconds === TRUE ) return $duration;
            //new dBug($duration);

            if( $duration < 3600 ) {
                $r = gmdate( "i:s" , $duration );
            } elseif( $duration < 86400 ) {
                $r = gmdate( "H:i:s" , $duration );
            } else {
                $duration -= 86400;
                $r = gmdate( "j\d H:i:s" , $duration );
            }

            return $r;
        }
    }


    /* Apply filters on videos Found */
    if( ! function_exists( 'cutv_filter_videos_found' ) ) {
        function cutv_filter_videos_found( $videosFound , $options ) {
            return $videosFound;
        }
    }
