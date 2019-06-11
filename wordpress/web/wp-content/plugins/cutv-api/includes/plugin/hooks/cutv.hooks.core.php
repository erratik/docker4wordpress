<?php
	
	/* Plugin Init Action Hook */
	add_action( 'init' , 'cutv_init' );
	function cutv_init() {
		/*starting a PHP session if not already started */
		if( ! session_id() ) @session_start();
		
		add_image_size( 'cutv_hard_thumb' , 200 , 150 , TRUE ); // Hard Crop Mode
		add_image_size( 'cutv_soft_thumb' , 200 , 150 ); // Soft Crop Mode
		
	}

	add_action( 'plugins_loaded' , 'cutv_load_addons_activation_hooks' , 5 );
	function cutv_load_addons_activation_hooks() {
		$x           = explode( 'cutv_' , CUTV_MAIN_FILE );
		$plugins_dir = $x[ 0 ];
	}

	/* Loading cutv_ translation files */
	add_action( 'plugins_loaded' , 'cutv_load_textdomain' );
	function cutv_load_textdomain() {
		load_plugin_textdomain( CUTV_LANG , FALSE , dirname( plugin_basename( __FILE__ ) ) . '/../languages/' );
	}

	/* Loading the cutv_ Superwrap HEADER*/
	add_action( 'load-edit.php' , 'cutv_add_slug_edit_screen_header' , - 1 );
	function cutv_add_slug_edit_screen_header() {
		if( CUTV_SMOOTH_SCREEN_ENABLED === TRUE ) {
			$screen = get_current_screen();
			if( $screen->id == 'edit-' . CUTV_SOURCE_TYPE || $screen->id == 'edit-' . CUTV_VIDEO_TYPE ) {
				?><div class = "cutv_super_wrap" style = " transition:visibility 1s ease-in-out;visibility:hidden;"><!-- SUPER_WRAP --><?php
			}
		}
	}
	
	/* Actions to be done on the activation of cutv_ */
	register_activation_hook( CUTV_MAIN_FILE , 'cutv_activation' );
	function cutv_activation() {

		cutv_reset_on_activation();

		cutv_start_plugin( 'cutv_' , CUTV_VERSION , FALSE );
		
		if ( ! get_option( 'cutv_flush_rewrite_rules_flag' ) ) {
			add_option( 'cutv_flush_rewrite_rules_flag', TRUE );
		}
		
		wp_schedule_event( time() , 'hourly' , 'cutv_hourly_event' );
		cutv_save_errors( ob_get_contents() );
		//cutv_set_debug( ob_get_contents() , TRUE );
		flush_rewrite_rules();
		
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
	}
	
	/* Actions to be done on the DEactivation of cutv_ */
	register_deactivation_hook( CUTV_MAIN_FILE , 'cutv_deactivation' );
	function cutv_deactivation() {
		wp_clear_scheduled_hook( 'cutv_hourly_event' );
		//flush_rewrite_rules();
		cutv_save_errors( ob_get_contents() );
		//cutv_set_debug( ob_get_contents() , TRUE );
	}
	
	register_deactivation_hook( CUTV_MAIN_FILE , 'flush_rewrite_rules' );
	
