<?php
	
	/* Plugin Init Action Hook */
	add_action( 'init' , 'cutv_init' );
	function cutv_init() {
		/*starting a PHP session if not already started */
		if( ! session_id() ) @session_start();
//		cutv_mysql_install();
		add_image_size( 'cutv_hard_thumb' , 200 , 150 , TRUE ); // Hard Crop Mode
		add_image_size( 'cutv_soft_thumb' , 200 , 150 ); // Soft Crop Mode
//		cutv_capi_init();
	}

	add_action( 'plugins_loaded' , 'cutv_load_addons_activation_hooks' , 5 );
	function cutv_load_addons_activation_hooks() {
		$x           = explode( 'cutv_' , CUTV_MAIN_FILE );
		$plugins_dir = $x[ 0 ];
//		$addons_obj = cutv_get_addons( array() , FALSE );
//		if( isset( $addons_obj[ 'items' ] ) && count( $addons_obj[ 'items' ] ) != 0 ) {
//			foreach ( $addons_obj[ 'items' ] as $addon ) {
//				$addon_main_file = $plugins_dir . str_replace( '/' , "\\" , $addon->plugin_dir );
//				register_activation_hook(
//					$addon_main_file ,
//					function () use ( $addon ) {
//						cutv_start_plugin( $addon->id , $addon->version , FALSE );
//					}
//				);
//			}
//		}
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

	/* Loading the cutv_ Superwrap FOOTER */
	add_action( 'admin_footer' , 'cutv_add_slug_edit_screen_footer' , 999999999999 );
	function cutv_add_slug_edit_screen_footer() {
		if( CUTV_SMOOTH_SCREEN_ENABLED === TRUE ) {
			$screen = get_current_screen();
			if( $screen->id == 'edit-' . CUTV_SOURCE_TYPE || $screen->id == 'edit-' . CUTV_VIDEO_TYPE ) {
				?><!-- SUPER_WRAP --><?php
			}
		}
	}

	/*Fix For pagination Category 1/2 */
	add_filter( 'request' , 'cutv_remove_page_from_query_string' );
	function cutv_remove_page_from_query_string( $query_string ) {
		if( isset( $query_string[ 'name' ] ) && $query_string[ 'name' ] == 'page' && isset( $query_string[ 'page' ] ) ) {
			unset( $query_string[ 'name' ] );
			// 'page' in the query_string looks like '/2', so i'm spliting it out
			list( $delim , $page_index ) = split( '/' , $query_string[ 'page' ] );
			$query_string[ 'paged' ] = $page_index;
		}
		
		return $query_string;
	}
	
	/*Fix For pagination Category 2/2 */
	add_filter( 'request' , 'cutv_fix_category_pagination' );
	function cutv_fix_category_pagination( $qs ) {
		if( isset( $qs[ 'category_name' ] ) && isset( $qs[ 'paged' ] ) ) {
			$qs[ 'post_type' ] = get_post_types( $args = array(
				'public'   => TRUE ,
				'_builtin' => FALSE ,
			) );
			array_push( $qs[ 'post_type' ] , 'post' );
		}
		
		return $qs;
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
	
	/* Set Autoupdate Hook */
	//add_action( 'init' , 'cutv_activate_autoupdate' , 100 );
//	function cutv_activate_autoupdate() {
//		global $cutv_addons;
//
//		//Check cutv_ updates
//		if( cutv_CHECK_PLUGIN_UPDATES ) {
//			new cutv_autoupdate_product (
//				cutv_VERSION , // Current Version of the product (ex 1.7.0)
//				cutv_SLUG , // Product Plugin Slug (ex cutv_/cutv_.php')
//				FALSE // Update zip url ? (ex TRUE or FALSE ),
//			);
//		}
//
//		//Check for active addons updates
//		if( cutv_CHECK_ADDONS_UPDATES ) {
//			$addons_obj = cutv_get_addons( array() , FALSE );
//			//d( $cutv_addons );
//			if( !is_multisite() ){
//				if( isset( $addons_obj[ 'items' ] ) && count( $addons_obj[ 'items' ] ) != 0 ) {
//					foreach ( $addons_obj[ 'items' ] as $addon ) {
//						//continue;
//						if( ! isset( $cutv_addons[ $addon->id ] ) ) {
//							continue;
//						}
//						if( ! is_plugin_active( $addon->plugin_dir ) ) {
//							continue;
//						}
//						$local_version = $cutv_addons[ $addon->id ][ 'infos' ][ 'version' ];
//						//d( $local_version );
//						//d( $addon->id );
//						new cutv_autoupdate_product (
//							$local_version , // Current Version of the product (ex 1.7.0)
//							$addon->plugin_dir , // Product Plugin Slug (ex cutv_/cutv_.php')
//							FALSE // Update zip url ? (ex TRUE or FALSE ),
//						);
//
//					}
//				}
//			}else{
//				if( isset( $addons_obj[ 'items' ] ) && count( $addons_obj[ 'items' ] ) != 0 ) {
//					foreach ( $addons_obj[ 'items' ] as $addon ) {
//						if( ! isset( $cutv_addons[ $addon->id ] ) ) {
//							continue;
//						}
//
//						//d( $addon->id );
//						//d( is_plugin_active_for_network( $addon->plugin_dir ));
//
//						if( ! is_plugin_active_for_network( $addon->plugin_dir ) ) {
//							continue;
//						}
//
//
//						$local_version = $cutv_addons[ $addon->id ][ 'infos' ][ 'version' ];
//						//d( $local_version );
//						//d( $addon->id );
//						new cutv_autoupdate_product (
//							$local_version , // Current Version of the product (ex 1.7.0)
//							$addon->plugin_dir , // Product Plugin Slug (ex cutv_/cutv_.php')
//							FALSE // Update zip url ? (ex TRUE or FALSE ),
//						);
//
//						//d( $addon );
//						//$plugin = explode('/' , $addon->plugin_dir );
//						//$plugin_data = get_plugin_data( $plugin[1] , $markup = true, $translate = true );
//						//d( $plugin_data );
//
//
//
//
//
//					}
//				}
//			}
//		}
//
//	}
//
	/* Activation */
	//add_action( 'admin_footer' , 'cutv_check_customer' );
	//add_action( 'admin_footer' , 'cutv_check_customer' );

	/* Add query video custom post types on pre get posts action */
	add_filter( 'pre_get_posts' , 'cutv_include_custom_post_type_queries' , 1000 , 1 );
	function cutv_include_custom_post_type_queries( $query ) {
		global $cutv_options , $cutv_private_cpt;
		$getOut = FALSE;
		
		//d( DOING_AJAX );
		if( $query->is_page() ) {
			return $query;
		}
		
		if( ! defined( 'DOING_AJAX' ) || DOING_AJAX === FALSE ) {
			if( is_admin() ) {
				return $query;
			}
		}
		
		$cutv_private_query_vars = array(
			'product_cat' ,
			'download_artist' ,
			'download_tag' ,
			'download_category' ,
		);
		$cutv_private_query_vars = apply_filters( 'cutv_extend_private_query_vars' , $cutv_private_query_vars );
		
		foreach ( $query->query_vars as $key => $val ) {
			if( in_array( $key , $cutv_private_query_vars ) ) {
				return $query;
			}
		}
		
		if( $cutv_options[ 'privateCPT' ] == null ) {
			$cutv_private_cpt = array();
		} else {
			$cutv_private_cpt = $cutv_options[ 'privateCPT' ];
		}
		$cutv_private_cpt = apply_filters( 'cutv_extend_private_cpt' , $cutv_private_cpt );
		
		
		//_d( $query->get( 'post_type' ) );
		
		//This line is Bugging with TrueMag Theme
		//if( isset($query->query_vars['suppress_filters']) && $query->query_vars['suppress_filters'] ) return $query;
		
		
		//echo "#IAM OUT";
		//_d( $cutv_private_cpt );
		if( $cutv_options[ 'addVideoType' ] === TRUE ) {
			
			$supported = $query->get( 'post_type' );
			//_d( $supported );
			//new dBug( $cutv_options['privateCPT'] );
			//new dBug( $cutv_private_cpt );
			if( is_array( $supported ) ) {
				foreach ( $supported as $s ) {
					if( in_array( $s , $cutv_private_cpt ) ) {
						$getOut = TRUE;
					}
				}
			} else {
				$getOut = in_array( $supported , $cutv_private_cpt );
			}
			
			if( $getOut === TRUE ) {
				return $query;
			} elseif( $supported == 'post' || $supported == '' ) {
				$supported = array( 'post' , CUTV_VIDEO_TYPE );
			} elseif( is_array( $supported ) ) {
				array_push( $supported , CUTV_VIDEO_TYPE );
			} elseif( is_string( $supported ) ) {
				$supported = array( $supported , CUTV_VIDEO_TYPE );
			}
			//echo "newSuported = ";new dBug( $supported );
			
			$query->set( 'post_type' , $supported );
			
			return $query;
		}
	}
