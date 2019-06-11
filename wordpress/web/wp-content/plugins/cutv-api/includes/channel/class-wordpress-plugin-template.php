<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class CUTV_Channel {

    /**
     * The single instance of CUTV_Channel.
     * @var 	object
     * @access  private
     * @since 	1.0.0
     */
    private static $_instance = null;

    /**
     * Settings class object
     * @var     object
     * @access  public
     * @since   1.0.0
     */
    public $settings = null;

    /**
     * The version number.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_version;

    /**
     * The token.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $_token;

    /**
     * The main plugin file.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $file;

    /**
     * The main plugin directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $dir;

    /**
     * The plugin assets directory.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_dir;

    /**
     * The plugin assets URL.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $assets_url;

    /**
     * Suffix for Javascripts.
     * @var     string
     * @access  public
     * @since   1.0.0
     */
    public $script_suffix;

    /**
     * A Unique Identifier
     */
    protected $plugin_slug;

    /**
     * A reference to an instance of this class.
     */
    private static $instance;

    /**
     * The array of templates that this plugin tracks.
     */
    protected $templates;


    /**
     * Returns an instance of this class.
     */
    public static function get_instance() {

        if ( null == self::$instance ) {
            self::$instance = new CUTV_Channel();
        }

        return self::$instance;

    }

    /**
     * Constructor function.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function __construct ( $file = '', $version = '1.0.0' ) {
        $this->_version = $version;
        $this->_token = 'CUTV_Channel';

        // Load plugin environment variables
        $this->file = $file;
        $this->dir = dirname( $this->file );
        $this->assets_dir = trailingslashit( $this->dir ) . '../assets';
        $this->vendor_dir = esc_url( trailingslashit( plugins_url('../bower_components', $this->file ) ) );
        $this->app_scripts_dir = esc_url( trailingslashit( plugins_url('../app/scripts', $this->file ) ) );
        $this->assets_url = esc_url( trailingslashit( plugins_url( '../assets/', $this->file ) ) );

        $this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '';

        $this->templates = array();

        register_activation_hook( $this->file, array( $this, 'install' ) );

        // Load frontend JS & CSS
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

        // Load admin JS & CSS
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );

//		add_action( 'register_post_type', array( $this, 'admin_enqueue_styles' ), 10, 1 );
//        CUTV_Channel()->register_post_type( 'listing', __( 'Listings', 'cutv-api' ), __( 'Listing', 'cutv-api' ) );
        // Add a filter to the attributes metabox to inject template into the cache.


        $this->templates = array();


        // Add a filter to the attributes metabox to inject template into the cache.
        add_filter(
            'page_attributes_dropdown_pages_args',
            array( $this, 'register_project_templates' )
        );


        // Add a filter to the save post to inject out template into the page cache
        add_filter(
            'wp_insert_post_data',
            array( $this, 'register_project_templates' )
        );


        // Add a filter to the template include to determine if the page has our
        // template assigned and return it's path
        add_filter(
            'template_include',
            array( $this, 'view_project_template')
        );


        // Add your templates to this array.
        $this->templates = array(
            'snaptube-template.php' => 'CUTV Snaptube',
            'catalog.php' => 'CUTV Catalog'
        );

        add_action( 'plugins_loaded', array( 'CUTV_Channel', 'get_instance' ) );


        // Load API for generic admin functions
        if ( is_admin() ) {
            $this->admin = new CUTV_Channel_Admin_API();

        }

        // Handle localisation
        $this->load_plugin_textdomain();
        add_action( 'init', array( $this, 'load_localisation' ), 0 );
    } // End __construct ()

    /**
     * Wrapper function to register a new post type
     * @param  string $post_type   Post type name
     * @param  string $plural      Post type item plural name
     * @param  string $single      Post type item single name
     * @param  string $description Description of post type
     * @return object              Post type class object
     */
    public function register_post_type ( $post_type = '', $plural = '', $single = '', $description = '', $options = array() ) {

        if ( ! $post_type || ! $plural || ! $single ) return;

        $post_type = new CUTV_Channel_Post_Type( $post_type, $plural, $single, $description, $options );

        return $post_type;
    }

    /**
     * Wrapper function to register a new taxonomy
     * @param  string $taxonomy   Taxonomy name
     * @param  string $plural     Taxonomy single name
     * @param  string $single     Taxonomy plural name
     * @param  array  $post_types Post types to which this taxonomy applies
     * @return object             Taxonomy class object
     */
    public function register_taxonomy ( $taxonomy = '', $plural = '', $single = '', $post_types = array(), $taxonomy_args = array() ) {

        if ( ! $taxonomy || ! $plural || ! $single ) return;

        $taxonomy = new CUTV_Channel_Taxonomy( $taxonomy, $plural, $single, $post_types, $taxonomy_args );

        return $taxonomy;
    }

    /**
     * Load frontend CSS.
     * @access  public
     * @since   1.0.0
     * @return void
     */
    public function enqueue_styles () {
        wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
        wp_enqueue_style( $this->_token . '-frontend' );
    } // End enqueue_styles ()

    /**
     * Load frontend Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function enqueue_scripts () {
        // wp_localize_script('cutv-api', 'wpApiSettings', array('root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest')));
        // wp_enqueue_script('cutv-api');


        wp_register_script( $this->_token . '-handlebars', esc_url($this->vendor_dir) . '/handlebars/handlebars.min' . $this->script_suffix . '.js', $this->_version );
        wp_enqueue_script( $this->_token . '-handlebars' );

        wp_register_script($this->_token . '-loadtpl', esc_url($this->assets_url) . 'load-template/loadTemplate' . $this->script_suffix . '.js', array($this->_token . '-handlebars'), $this->_version);
        wp_enqueue_script($this->_token . '-loadtpl');

        
        wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery'), $this->_version );
        wp_enqueue_script( $this->_token . '-admin' );

        wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend.js', array(  $this->_token . '-handlebars', $this->_token . '-admin'), $this->_version );
        wp_enqueue_script( $this->_token . '-frontend' );
//        exit;
//
    } // End enqueue_scripts ()

    /**
     * Load admin CSS.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_styles ( $hook = '' ) {
        wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
        wp_enqueue_style( $this->_token . '-admin' );
        wp_register_style( $this->_token . '-semantic', esc_url( $this->assets_url ) . 'styles/semantic/semantic.min.css', array(), $this->_version );
        wp_enqueue_style( $this->_token . '-semantic' );
    } // End admin_enqueue_styles ()

    /**
     * Load admin Javascript.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function admin_enqueue_scripts ( $hook = '' ) {

        // wp_localize_script('cutv-api', 'wpApiSettings', array('root' => esc_url_raw(rest_url()), 'nonce' => wp_create_nonce('wp_rest')));
        // wp_enqueue_script('cutv-api');

        // //  exit;

        if (!isset($_REQUEST['vc_action']) ||
            (isset($_REQUEST['page']) && $_REQUEST['page'] == 'wpvr_manage_videos')) {

            wp_register_script( $this->_token . '-moment', esc_url( $this->assets_url ) . 'js/moment' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
            wp_enqueue_script( $this->_token . '-moment' );
            if ($_REQUEST['page'] == 'cutv_manage_channels') {

                wp_register_script($this->_token . '-lodash', esc_url($this->assets_url) . 'js/lodash' . $this->script_suffix . '.js', array('jquery'), $this->_version);
                wp_enqueue_script($this->_token . '-lodash');;
                wp_register_script($this->_token . '-semantic', esc_url($this->assets_url) . 'styles/semantic/semantic.min' . $this->script_suffix . '.js', array('jquery'), $this->_version);
                wp_enqueue_script($this->_token . '-semantic');

                wp_register_script($this->_token . '-ng', esc_url($this->vendor_dir) . '/angular/angular' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-ng');
                wp_register_script($this->_token . '-ngroute', esc_url($this->vendor_dir) . '/angular-route/angular-route' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-ngroute');
                wp_register_script($this->_token . '-ngflow', esc_url($this->vendor_dir) . '/ng-flow/dist/ng-flow-standalone.min' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-ngflow');

                wp_register_script($this->_token . '-app', esc_url($this->app_scripts_dir) . '/app' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-app');
                wp_register_script($this->_token . '-svc-channel', esc_url($this->app_scripts_dir) . 'services/channel.svc' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-svc-channel');
                wp_register_script($this->_token . '-ctrl-main', esc_url($this->app_scripts_dir) . 'controllers/main.ctrl' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-ctrl-main');
                wp_register_script($this->_token . '-ctrl-channel', esc_url($this->app_scripts_dir) . 'controllers/channel.ctrl' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-ctrl-channel');
        
                wp_register_script($this->_token . '-dir-mng-channel', esc_url($this->app_scripts_dir) . 'directives/channel-item.directive' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-dir-mng-channel');
                wp_register_script($this->_token . '-dir-video-counts', esc_url($this->app_scripts_dir) . 'directives/channel--video-counts.directive' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-dir-video-counts');
                wp_register_script($this->_token . '-dir-mng-sources', esc_url($this->app_scripts_dir) . 'directives/manage-sources.directive' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-dir-mng-sources');
                wp_register_script($this->_token . '-dir-mng-videos', esc_url($this->app_scripts_dir) . 'directives/manage-videos.directive' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-dir-mng-videos');
                wp_register_script($this->_token . '-dir-upload-wrapper', esc_url($this->app_scripts_dir) . 'directives/channel-image-uploader.directive' . $this->script_suffix . '.js', $this->_version);
                wp_enqueue_script($this->_token . '-dir-upload-wrapper');


                wp_register_script($this->_token . '-admin', esc_url($this->assets_url) . 'js/admin' . $this->script_suffix . '.js', array('jquery', $this->_token . '-app'), $this->_version);
                wp_enqueue_script($this->_token . '-admin');



            }  else {

                wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery', 'underscore'), $this->_version );
                wp_enqueue_script( $this->_token . '-admin' );

            }

            if ($_REQUEST['page'] == 'wpvr_manage_videos') {
                wp_register_script($this->_token . '-lodash', esc_url($this->assets_url) . 'js/lodash' . $this->script_suffix . '.js', array('jquery'), $this->_version);
                wp_enqueue_script($this->_token . '-lodash');

            }


        } else {

            wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version );
            wp_enqueue_script( $this->_token . '-admin' );

        }

        /**/


    } // End admin_enqueue_scripts ()


    /**
     * Load plugin localisation
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_localisation () {
        load_plugin_textdomain( 'cutv-plugin', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_localisation ()

    /**
     * Load plugin textdomain
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function load_plugin_textdomain () {
        $domain = 'cutv-plugin';

        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    } // End load_plugin_textdomain ()

    /**
     * Main CUTV_Channel Instance
     *
     * Ensures only one instance of CUTV_Channel is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see CUTV_Channel()
     * @return Main CUTV_Channel instance
     */
    public static function instance ( $file = '', $version = '1.0.0' ) {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self( $file, $version );
        }
        return self::$_instance;
    } // End instance ()

    /**
     * Cloning is forbidden.
     *
     * @since 1.0.0
     */
    public function __clone () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __clone ()

    /**
     * Unserializing instances of this class is forbidden.
     *
     * @since 1.0.0
     */
    public function __wakeup () {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?' ), $this->_version );
    } // End __wakeup ()

    /**
     * Installation. Runs on activation.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    public function install () {
        $this->_log_version_number();
    } // End install ()

    /**
     * Log the plugin version number.
     * @access  public
     * @since   1.0.0
     * @return  void
     */
    private function _log_version_number () {
        update_option( $this->_token . '_version', $this->_version );
    } // End _log_version_number ()

    public function register_project_templates( $atts ) {

        // Create the key used for the themes cache
        $cache_key = 'page_templates-' . md5( get_theme_root() . '/' . get_stylesheet() );

        // Retrieve the cache list.
        // If it doesn't exist, or it's empty prepare an array
        $templates = wp_get_theme()->get_page_templates();
        if ( empty( $templates ) ) {
            $templates = array();
        }

        // New cache, therefore remove the old one
        wp_cache_delete( $cache_key , 'themes');

        // Now add our template to the list of templates by merging our templates
        // with the existing templates array from the cache.
        $templates = array_merge( $templates, $this->templates );

        // Add the modified cache to allow WordPress to pick it up for listing
        // available templates
        wp_cache_add( $cache_key, $templates, 'themes', 1800 );

        return $atts;

    }


    /**
     * Checks if the template is assigned to the page
     */
    public function view_project_template( $template ) {

        // Get global post
        global $post;

        // Return template if post is empty
        if ( ! $post ) {
            return $template;
        }

        // Return default template if we don't have a custom one defined
        if ( !isset( $this->templates[get_post_meta(
                $post->ID, '_wp_page_template', true
            )] ) ) {
            return $template;
        }

        $file = plugin_dir_path(__FILE__). get_post_meta(
                $post->ID, '_wp_page_template', true
            );

        // Just to be safe, we check if the file exist first
        if ( file_exists( $file ) ) {
            return $file;
        } else {
            echo 'template file not found: ';
            echo $file;
        }

        // Return template
        return $template;

    }

}
