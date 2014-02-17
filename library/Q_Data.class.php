<?php

/**
 * Data Building Functions
 * 
 * @since       0.9
 */

if ( ! class_exists( 'Q_Data' ) ) 
{
    
    // add kint for pretty var_dump ##
    require_once plugin_dir_path( __FILE__ ) . '/kint/Kint.class.php';
    
    class Q_Data
    {
        
        /*--------------------------------------------*
        * Attributes
        *--------------------------------------------*/

        /** Refers to a single instance of this class. */
        private static $instance = null;
        
        // public properties ##
        public $plugin_url_data = 'edit.php?post_type=q_support&page=data';

        // to clean up links ##
        public static $allowed_tags_href = array(
            'a' => array(
                'href' => array()
                ,'title' => array()
            )
            ,'abbr' => array(
                'title' => array()
            )
            ,'acronym' => array(
                'title' => array()
            )
            ,'code' => array()
            ,'em' => array()
            ,'strong' => array()
        );
            
        // to clean up the desc ##
        public static $allowed_tags_desc = array(
            'abbr' => array(
                'title' => array()
            )
            ,'acronym' => array(
                'title' => array()
            )
            ,'code' => array()
            ,'em' => array()
            ,'strong' => array()
        );
        
        /*--------------------------------------------*
         * Constructor
         *--------------------------------------------*/

        
        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo A single instance of this class.
         */
        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        } // end get_instance;

        
        /**
         * Initializes the plugin by setting localization, filters, and administration functions.
         */
        private function __construct() {
            
            if ( is_admin() ) {
                
                 // plugin data viewer ##
                add_action( 'admin_menu', array( $this, 'add_admin_menu_data' ), 3 );
                
                // AJAX clear saved support data ##
                add_action('wp_ajax_q_support_data_clear', array( $this, 'ajax_q_support_data_clear' ));
                
            }
            
        } // end constructor
        
        
        /*--------------------------------------------*
        * Functions
        *--------------------------------------------*/
        
        
        /*
         * Add plugin configuration debugger to admin menus
         * 
         * @since       0.3
         * @return      void
         **/
        public function add_admin_menu_data() 
        {
            
            #if ( ! Q_Settings::get_q_support_user_connected() ) { return false; } // not until the plugin is connectd ##
            
            // add a sub menu to the CPT ##
            $add_admin_menu_data = add_submenu_page (
                Q_Support::$plugin_url
                ,__( 'Plugin Data', Q_Support::$text_domain ) ## page title*/
                ,__( 'Data', Q_Support::$text_domain ) ## menu title*/
                ,Q_SUPPORT_CAPABILITY ## capability needed
                ,'data'
                ,array( $this, 'view_q_support_data' )
            );
            
	}
        
        
        /**
         * Debug all Getter functions
         * 
         * @since       0.3
         * @return      string  HTML content of debug info ##
         */
        public function view_q_support_data()
        {
            
            // quick check on who's viewing ##
            if ( ! current_user_can( Q_SUPPORT_CAPABILITY ) ) { 
                
                wp_die( _e( "You do not have sufficient permissions to access this page.", Q_Support::$text_domain ) );
                
            }
            
            // open wrap ##
            echo '<div class="wrap q_support_wrap">';
            
            // icon and h2 ##
            screen_icon("q_support");
            echo '<h2>'; _e("Plugin Data"); echo '</h2>';
            
            // notice space ##
            #echo '<div class="q_support_response updated"><p></p></div>';
            
            // intro blurb ##
            printf( 
                '<p>This screen allows you to view all the data stored in the database which can be submitted allong with support requests - <a href="%1$s" class="_blank">%2$s</a></p>'
                ,esc_url( Q_SUPPORT_DOMAIN.'plugin/question-data/' )
                ,esc_html( __("Documentation", Q_Support::$text_domain ) ) 
            );
            
            #d( Q_Settings::get_q_support_options() );
            #d( Q_Settings::get_q_support_settings() ); // this is now a personal setting - per user ##
            d( $this->get_server() );
            d( $this->get_client() );
            d( $this->get_htaccess() );
            d( $this->get_wp_config() );
            d( $this->get_wordpress() );
            d( $this->get_post_types() );
            d( $this->get_theme() );
            d( $this->get_plugins() );
            d( $this->get_sidebars() );
            
            // link to error log ##
            printf( 
                '<p><a href="%s" class="q_support_data_clear button">%s</a></p>'
                ,esc_url( '#' )
                ,esc_html( __("Reload Stored Data", Q_Support::$text_domain ) )
            );
            
            // link to error log ##
            printf( 
                '<p>%s</p>'
                ,esc_html( __("Note: User and plugin activation data is only cleared when the plugin is deactivated or uninstalled.", Q_Support::$text_domain ) )
            );
            
            // close wrap ##
            echo '</div>';
            
        }
        
        
        /*
        * AJAX Clear Support Data ##
        * 
        * @since        0.3
        *
        * @return       string  HTML notification message ##  
        **/
        public function ajax_q_support_data_clear() 
        {
            
            // quick check on who's viewing ##
            if ( ! current_user_can( Q_SUPPORT_CAPABILITY_ADMIN ) ) { 
                wp_die( _e( "You do not have sufficient permissions to access this page.", Q_Support::$text_domain ) );
            }
            
            // check nonce ##
            check_ajax_referer( 'q_support_nonce', 'nonce' );
            
            // array of plugin options ##
            #$options = array (
                #'q_support_options' // plugin options ##
            #    'q_support_settings' // settings ##
            #);
            
            // remove plugin options ##
            #$this->delete_options( $options );
            
            // clear all transient data ##
            $this->delete_cache();
            
            // delete the error log file ##
            #Q_Errors::delete_error_log();
            
            // delete CPT posts ##
            #global $wpdb;

            #$query = "
            #    DELETE FROM wp_posts 
            #    WHERE post_type = 'q_support' 
            #";

            #LIMIT 0, 3000
            
            #$wpdb->query($query);
            
            // now reload the data ##
            #$this->get_q_support_options();
            #$this->get_q_support_user();
            
            // reload all other data ##
            #Q_Settings::get_q_support_settings(); // this is now a personal setting - per user ##
            $this->get_server();
            $this->get_client();
            $this->get_htaccess();
            $this->get_wp_config();
            $this->get_wordpress();
            $this->get_post_types();
            $this->get_theme();
            $this->get_plugins();
            $this->get_sidebars();
            
            $return = sprintf( 
                __(
                    'Plugin Data Reloaded - <a href="%s">%s</a> to see the new data below.'
                    ,Q_Support::$text_domain
                )
                , esc_url( $this->plugin_url_data )
                , esc_html( __("Refresh", Q_Support::$text_domain ) ) 
            );

            // return it ##
            echo json_encode($return);

            // all AJAX calls must die!! ##
            die();
            
	}
        
        
        
        
        
        /**
         * Delete multiple wp_options keys
         * 
         * @param       array   $options    Array of option names to delete
         * @param       string  $funtion    Function to call to delete options
         * @since       0.3
         * @return      void
         */
        public static function delete_options( $options = null, $function = 'delete_option' ) 
        {
    
            if ( ! $options || ! is_array ( $options ) ) { return; }

            foreach( $options as $option ) {

                call_user_func( $function, $option );

            }

        }
        
        
        /**
         * Delete Cached Options
         * 
         * @since       0.3
         * @src         https://github.com/wp-plugins/delete-expired-transients/blob/master/delete-expired-transients.php
         * @return      void
         */
        public static function delete_cache() 
        {

            global $wpdb;

            // delete all wps transients
            $sql = "
                delete from {$wpdb->options}
                where option_name like '\_transient\_q\_support\_%'
                or option_name like '\_transient\_timeout\_q\_support\_%'
            ";
                
            $wpdb->query($sql);
            
        }
        
        
        /**
         * 
         * @param       string      $dirPath
         * @throws      InvalidArgumentException
         * @link        http://stackoverflow.com/questions/3349753/delete-directory-with-files-in-it
         */
        public static function delete_directory( $dirPath ) 
        {
            
            if (! is_dir($dirPath)) {
                throw new InvalidArgumentException("$dirPath must be a directory");
            }
            
            if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
                $dirPath .= '/';
            }
            
            $files = glob($dirPath . '*', GLOB_MARK);
            
            foreach ($files as $file) {
                if (is_dir($file)) {
                    self::delete_directory( $file );
                } else {
                    unlink($file);
                }
            }
            
            rmdir($dirPath);
            
        }
        
        
        
        
        
        /**
         * Create a compressed zip file of DUMP data 
         * 
         * @since       0.5.5
         * @return      Mixed
         */
        public static function create_dump( $file_name ) 
        {
            
            // do we have a file name ? ##
            if ( ! $file_name ) { 
                
                // log error ##
                if ( Q_PLUGIN_DEBUG ) update_option( 'q_support_create_dump', 'Missing File Name' );
                
                // kick back ##
                return false; 
                
            }
            
            // grab the user settings ##
            $q_support_settings = Q_Settings::get_q_support_settings();
            
            // nothing cooking ##
            if ( ! $q_support_settings ) {
                
                // log error ##
                if ( Q_PLUGIN_DEBUG ) update_option( 'q_support_create_dump', 'Missing Settings' );
                
                // kick back ##
                return false;
                
            }
            
            // clean up the debug option ##
            if ( Q_PLUGIN_DEBUG ) { 
                delete_option( 'q_support_create_dump' );
                $q_support_create_dump = array();
            }
            
            // list the settings that we need to check ##
            $settings_to_check = array(
                    'wordpress'
                ,   'server'
                ,   'client'          
            );
            
            // overwritting is cool ##
            $overwrite = true;
            
            // where are we going to save this file ##
            $path = Q_SUPPORT_PATH . 'dump/';
            
            if ( Q_PLUGIN_DEBUG ) $q_support_create_dump["path"] = $path;
            
            // put it all together to get the full file path ##
            $zip_file = $path.$file_name.'.zip';
            
            if ( Q_PLUGIN_DEBUG ) $q_support_create_dump["zip_file"] = $zip_file;
            
            // if the zip file already exists and overwrite is false, return false ##
            if( file_exists( $zip_file ) && ! $overwrite ) { return false; }
            
            // create a file of the WP install data - based on the user settings ##
            $dump = array();

            // loop over each settings area, and add some data ##
            foreach( $settings_to_check as $setting ) {
                
                // should we include this ? ##
                if ( isset( $q_support_settings[$setting] ) && $q_support_settings[$setting] == 'yes' ) {
                    
                    switch( $setting ){
                        
                        case('wordpress');
                           
                            $dump['wordpress'] = array(
                                    'htaccess'      => self::get_htaccess()
                                ,   'wp_config'     => self::get_wp_config()
                                ,   'wordpress'     => self::get_wordpress()
                                ,   'post_types'    => self::get_post_types()
                                ,   'theme'         => self::get_theme()
                                ,   'plugins'       => self::get_plugins()
                                ,   'sidebars'      => self::get_sidebars()
                            );
                            
                            break;
                        
                        case('server');
                           
                            $dump['server'] = self::get_server();
                            
                            break;
                        
                        case('client');
                           
                            $dump['client'] = self::get_client();
                            
                            break;
                        
                    }
                    
                }
                
            }
            
            // create the archive ##
            $zip = new ZipArchive();
            if( $zip->open( $zip_file, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true ) {
                
                // log error ##
                if ( Q_PLUGIN_DEBUG ) {
                    $q_support_create_dump["error"] = 'Error Opening Zip';
                    update_option( 'q_support_create_dump', $q_support_create_dump );
                }
                
                // kick back ##
                return false;

            }

            // create a file inside the zip with the contents of dump ##
            $zip_addFromString = $zip->addFromString( 'dump.txt', var_export( $dump, true ) );

            // debug ##
            if ( Q_PLUGIN_DEBUG ) {

                if ( $zip_addFromString ) {
                    
                    $q_support_create_dump["status"] = "Zip Good: status {$zip->status}";

                } else {

                    $q_support_create_dump["status"] = "Zip Error: status {$zip->status}";

                }

                update_option( 'q_support_create_dump', $q_support_create_dump );

            }

            // close the zip -- done! ##
            $zip->close();

            // check to make sure the file exists ##
            if ( file_exists( $zip_file ) ) {

                return $file_name.'.zip'; // kick back the filename ##

            } else {

                return false;

            }
            
        }
        
        
        
        /**
         * Get WordPress Settings
         * 
         * @since       0.3
         * @return      array   Containing info about wp settings ( domain, privacy, permalinks, admin email )
         */
        public static function get_wordpress() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_wordpress'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_wordpress = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
            
                // set a new array ##
                $get_wordpress = array();
                
                // current version ##
                $get_wordpress["version"] = get_bloginfo("version");

                // latest version ##
                $cur = get_preferred_from_update_core();
                if ( ! is_object( $cur ) ) {
                    $cur = new stdClass;
                }
                
                if ( isset( $cur->current ) ) {
                    $get_wordpress["version_latest"] = $cur->current;
                }

                // version compare ##
                $compare = version_compare( $get_wordpress["version"], $get_wordpress["version_latest"], '<=' );
                $get_wordpress["is_latest"] = $compare ? true : false;
                
                $get_wordpress["admin_email"] = get_option("admin_email");
                $get_wordpress["blogname"] = get_option("blogname");
                $get_wordpress["blog_charset"] = get_option("blog_charset");
                $get_wordpress["date_format"] = get_option("date_format");
                $get_wordpress["time_format"] = get_option("time_format");
                $get_wordpress["timezone_string"] = get_option("timezone_string");
                $get_wordpress["gmt_offset"] = get_option("gmt_offset");
                $get_wordpress["home"] = get_option("home");
                $get_wordpress["siteurl"] = get_option("siteurl");
                $get_wordpress["template"] = get_option("template");
                $get_wordpress["upload_path"] = get_option("upload_path");
                $get_wordpress["posts_per_page"] = get_option("posts_per_page");
                $get_wordpress["posts_per_rss"] = get_option("posts_per_rss");
                $get_wordpress["posts_per_rss"] = get_option("posts_per_rss");
                $get_wordpress["posts_per_rss"] = get_option("posts_per_rss");
                $get_wordpress["permalink_structure"] = get_option("permalink_structure");
                $get_wordpress["blog_public"] = get_option("blog_public");
                $get_wordpress["users_can_register"] = get_option("users_can_register");
                $get_wordpress["default_role"] = get_option("default_role");
                $get_wordpress["uploads_use_yearmonth_folders"] = get_option("uploads_use_yearmonth_folders");
                $get_wordpress["upload_path"] = get_option("upload_path");
                
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_wordpress, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##

            // send back some data ##
            return $get_wordpress;
            
        }
                
                
        /**
         * Get Installed Plugins
         * 
         * @since       0.3
         * @return      array   Containing info about all active plugins ( name, is_active, version, version_latest, is_latest, author_name, author_url, support_url )
         */
        public static function get_plugins() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_plugins'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_plugins = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
            
                if ( ! function_exists('get_plugins') ) {
                    require_once ( ABSPATH . 'wp-admin/includes/plugin.php' );
                }

                if ( ! function_exists( 'plugins_api' ) ) {
                    require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
                }

                // grab all plugins ##
                $wp_get_plugins = get_plugins();
                #echo '<pre>'; var_dump($get_plugins); echo '</pre>';

                // loop over all plugins to build array ##
                foreach( $wp_get_plugins as $key => $value ) {

                    // unique key ##
                    $count = wp_kses( $value["Title"], self::$allowed_tags_href );

                    // Plugin Title ##
                    $get_plugins[$count]['title'] = wp_kses( $value["Title"], self::$allowed_tags_href );

                    // build plugin slug ##
                    $get_plugins[$count]['slug'] = str_replace( " ", "-", strtolower($value["Title"]) );

                    // check plugin status ##
                    if ( is_plugin_active( $key ) ) {

                        $get_plugins[$count]['status'] = 'active';

                    } else {

                        $get_plugins[$count]['status'] = 'inactive';

                    }

                    // get plugin details from comments ##
                    $get_plugins[$count]['file'] = $key;
                    $get_plugins[$count]['url'] = wp_kses( $value['PluginURI'], self::$allowed_tags_href );
                    $get_plugins[$count]['version'] = wp_kses( $value['Version'], self::$allowed_tags_href );
                    $get_plugins[$count]['version_latest'] = false;
                    $get_plugins[$count]['is_latest'] = false;
                    $get_plugins[$count]['description'] = wp_kses( $value['Description'], self::$allowed_tags_desc );
                    $get_plugins[$count]['author'] = wp_kses( $value['Author'], self::$allowed_tags_href );
                    $get_plugins[$count]['author_url'] = wp_kses( $value['AuthorURI'], self::$allowed_tags_href );

                    // set the arguments to get latest info from repository via API ##
                    $args = array(
                        'slug' => $get_plugins[$count]['slug'],
                        'fields' => array(
                            'version' => true,
                            'requires' => true,
                            'tested' => true,
                            'compatibility' => true,
                            'downloaded' => true,
                            'rating' => true,
                            'num_ratings' => true,
                            'last_updated' => true
                        )
                    );

                    // Prepare our query ##
                    $call_api = plugins_api( 'plugin_information', $args );

                    // Check for Errors & Display the results ##
                    if ( is_wp_error( $call_api ) ) {

                        $get_plugins[$count]['api_error'] = $call_api->get_error_message();
                        
                    } else {

                        #$get_plugins[$count]['api_call'] = $call_api;

                        if ( ! empty( $call_api->downloaded ) ) {

                            $get_plugins[$count]['version_latest'] = $call_api->version;
                            $get_plugins[$count]['last_updated'] = $call_api->last_updated;
                            $get_plugins[$count]['downloaded'] = $call_api->downloaded;
                            $get_plugins[$count]['requires'] = $call_api->requires;
                            $get_plugins[$count]['tested'] = $call_api->tested;
                            $get_plugins[$count]['compatibility'] = $call_api->compatibility;
                            $get_plugins[$count]['rating'] = $call_api->rating;
                            $get_plugins[$count]['num_ratings'] = $call_api->num_ratings;
                            
                            // is this the latest version ##
                            $get_plugins[$count]['is_latest'] = version_compare( $get_plugins[$count]['version'], $get_plugins[$count]['version_latest'], '<=' );
                            
                        }

                    }

                }

                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_plugins, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
            
            return $get_plugins;
            
        }
        
        
        /**
         * Get Active Widgets
         * 
         * @since       0.3
         * @return      array   Containing info about active widgets
         */
        public static function get_sidebars() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_sidebars'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_sidebars = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
                
                // get all sidebars ##
                $get_registered_sidebars = self::get_registered_sidebars();
                
                if ( $get_registered_sidebars && is_array( $get_registered_sidebars ) ) {
                    
                    foreach( $get_registered_sidebars as $sidebar ) {
                        
                        $count = wp_kses( $sidebar['id'], self::$allowed_tags_href );
                        $get_sidebars[$count]["id"] = $sidebar['id'];
                        $get_sidebars[$count]["title"] = $sidebar['name'];
                        $get_sidebars[$count]["widgets"] = self::get_widget_data_for( $sidebar["id"] );
                        
                    }
                    
                }
                
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_sidebars, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
            
            return $get_sidebars;
            
        }
        
        
        /*
         * Get Widget data for a specified sidebar ##
         * 
         * @since       0.3
         * @return      array   Containing info about active widgets
         * @src         https://gist.github.com/kingkool68/3418186
         */
        public static function get_widget_data_for( $sidebar_id ) 
        {
            
            global $wp_registered_sidebars, $wp_registered_widgets;

            // Holds the final data to return
            $output = array();

            // Loop over all of the registered sidebars looking for the one with the same ID as $sidebar_id
            //$sibebar_id = false;
            foreach( $wp_registered_sidebars as $sidebar ) {
                
                if( $sidebar['id'] == $sidebar_id ) {
                    
                    // We now have the Sidebar ID, we can stop our loop and continue.
                    $sidebar_id = $sidebar['id'];
                    break;
                    
                }
                
            }

            if( !$sidebar_id ) {
                // There is no sidebar registered with the name provided.
                return $output;
            } 

            // A nested array in the format $sidebar_id => array( 'widget_id-1', 'widget_id-2' ... );
            $sidebars_widgets = wp_get_sidebars_widgets();
            $widget_ids = $sidebars_widgets[$sidebar_id];

            if( !$widget_ids ) {
                // Without proper widget_ids we can't continue. 
                return array();
            }

            // Loop over each widget_id so we can fetch the data out of the wp_options table.
            foreach( $widget_ids as $id ) {
                
                // The name of the option in the database is the name of the widget class.  
                $option_name = $wp_registered_widgets[$id]['callback'][0]->option_name;

                // Widget data is stored as an associative array. To get the right data we need to get the right key which is stored in $wp_registered_widgets
                $key = $wp_registered_widgets[$id]['params'][0]['number'];
                
                $widget_data = get_option($option_name);
                
                // Add the widget data on to the end of the output array.
                $output[$option_name] = $widget_data[$key];
                
            }

            return $output;
            
        }
        
        
        /**
         * Get Registered Sidebars
         * 
         * @since       0.3
         * @return      array   Containing id and name of each registered sidebar - or false if the global array value is empty ##
         */
        public static function get_registered_sidebars() 
        {
            
            global $wp_registered_sidebars;
            
            // nothing cooking ##
            if ( empty( $wp_registered_sidebars ) ) {
                
                return false;
                
            }
            
            // loop over each found sidebar ##
            foreach ( $wp_registered_sidebars as $sidebar ) {
                
                // unique key ##
                $count = wp_kses( $sidebar['id'], self::$allowed_tags_href );
                
                $get_registered_sidebars[$count]["id"] = $sidebar['id'];
                $get_registered_sidebars[$count]["name"] = $sidebar['name'];

            }
            
            // return it ##
            return $get_registered_sidebars;
            
        }
        
        
        /**
         * Get Active Theme info
         * 
         * @since       0.3
         * @return      array   Containing info about active theme ( name, is_active, version, version_latest, is_latest, author_name, author_url, support_url )
         */
        public static function get_theme() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_theme'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_theme = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
            
                // include required functionality if missing ##
                if ( ! function_exists( 'themes_api' ) ) {
                    require_once( ABSPATH . 'wp-admin/includes/theme.php' );
                }

                // get the theme info ##
                $wp_get_theme = wp_get_theme();

                // test it ##
                #self::pr($wp_get_theme);

                // build a new array ##
                $get_theme = array();

                // slug look-up ##
                $get_theme['slug'] = $wp_get_theme->get( 'TextDomain' ) ? $wp_get_theme->get( 'TextDomain' ) : $wp_get_theme->get( 'Name' ) ;

                // build theme slug ##
                $get_theme['slug'] = str_replace( " ", "-", strtolower( $get_theme['slug'] ) );

                // grab and format the required data ##
                $get_theme["name"] = wp_kses( $wp_get_theme->get( 'Name' ), self::$allowed_tags_href );
                $get_theme["version"] = $wp_get_theme->get( 'Version' );
                $get_theme['version_latest'] = false;
                $get_theme["is_latest"] = false; // by default ##
                $get_theme["url"] = $wp_get_theme->get( 'ThemeURI' );
                #$get_theme["description"] = wp_kses( $wp_get_theme->get( 'Description' ), $this->allowed_tags_desc );
                $get_theme["author"] = wp_kses( $wp_get_theme->get( 'Author' ), self::$allowed_tags_href );
                $get_theme["author_url"] = wp_kses( $wp_get_theme->get( 'AuthorURI' ), self::$allowed_tags_href );

                // child theme ? ##
                $get_theme["template"] = wp_kses( $wp_get_theme->get( 'Template' ), self::$allowed_tags_href );
                $get_theme["is_child"] = is_child_theme();

                // translations ##
                $get_theme["text_domain"] = wp_kses( $wp_get_theme->get( 'TextDomain' ), self::$allowed_tags_href );
                $get_theme["domain_path"] = wp_kses( $wp_get_theme->get( 'DomainPath' ), self::$allowed_tags_href );

                // set the arguments to get latest info from repository via API ##
                $args = array(
                    'slug' => $get_theme['slug'],
                    'fields' => array(
                        'version' => true,
                        'requires' => true,
                        'tested' => true,
                        'compatibility' => true,
                        'downloaded' => true,
                        'rating' => true,
                        'num_ratings' => true,
                        'last_updated' => true
                    )
                );

                // Prepare our query ##
                $call_api = themes_api( 'theme_information', $args );

                // Check for Errors & Display the results ##
                if ( is_wp_error( $call_api ) ) {

                    $get_theme['api_error'] = $call_api->get_error_message();

                } else {

                    #$get_theme['api_call'] = $call_api;

                    if ( ! empty( $call_api->downloaded ) ) {

                        $get_theme['version_latest'] = isset( $call_api->version ) ? $call_api->version : '';
                        $get_theme['last_updated'] = isset( $call_api->last_updated ) ? $call_api->last_updated : '';
                        $get_theme['downloaded'] = isset( $call_api->downloaded ) ? $call_api->downloaded : '';
                        $get_theme['requires'] = isset( $call_api->requires ) ? $call_api->requires : '';
                        $get_theme['tested'] = isset( $call_api->tested ) ? $call_api->tested : '';
                        $get_theme['compatibility'] = isset( $call_api->compatibility ) ? $call_api->compatibility : '';
                        $get_theme['rating'] = isset( $call_api->rating ) ? $call_api->rating: '';
                        $get_theme['num_ratings'] = isset( $call_api->num_ratings ) ? $call_api->num_ratings : '';

                        // is this the latest version ? ##
                        $get_theme["is_latest"] = version_compare( $get_theme["version"], $get_theme['version_latest'], '<=' );

                    }

                }
                
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_theme, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
            
            // return the array ##
            return $get_theme;
            
        }
        
        
        /**
         * Get Custom Post Types
         * 
         * @since       0.3
         * @return      array   Containing info about CPT's ##
         */
        public static function get_post_types() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_post_types'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_post_types = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
                
                // get all sidebars ##
                $get_post_types = array();
                
                $post_types = get_post_types( '', 'names' ); 

                foreach ( $post_types as $post_type ) {

                    // post type name ##
                    $get_post_types[$post_type] = array();
                    $get_post_types[$post_type]["name"] = $post_type;
                    
                    // get taxonomies ##
                    $get_post_types[$post_type]["taxonomies"] = get_object_taxonomies( $post_type );
                   
                }
                
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_post_types, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
            
            return $get_post_types;
            
        }
        
        
        /**
         * Get WP_Config.php Settings
         * 
         * @since       0.3
         * @return      array   Containing info about wp_config.php ( wp_debug, wp_cache )
         * @link        http://davejesch.com/wordpress/wordpress-tech/wordpress-constants/
         * @link        http://codex.wordpress.org/Editing_wp-config.php
         */
        public static function get_wp_config() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_wp_config'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_wp_config = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
            
                // set a new array ##
                $get_wp_config = array();

                $get_wp_config['wp_home'] = defined('WP_HOME') ? WP_HOME : false;
                $get_wp_config['wp_siteurl'] = defined('WP_SITEURL') ? WP_SITEURL : false;
                $get_wp_config['wplang'] = defined('WPLANG') ? WPLANG : false;
                $get_wp_config['disable_wp_cron'] = defined('DISABLE_WP_CRON') ? DISABLE_WP_CRON : false;
                $get_wp_config['wp_cache'] = defined('WP_CACHE') ? WP_CACHE : false;
                $get_wp_config['wp_debug'] = defined('WP_DEBUG') ? WP_DEBUG : false;
                $get_wp_config['wp_debug_log'] = defined('WP_DEBUG_LOG') ? WP_DEBUG_LOG : false;
                $get_wp_config['script_debug'] = defined('SCRIPT_DEBUG') ? SCRIPT_DEBUG : false;
                $get_wp_config['wp_debug_display'] = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false;
                $get_wp_config['autosave_interval'] = defined('AUTOSAVE_INTERVAL') ? AUTOSAVE_INTERVAL : false;
                $get_wp_config['wp_post_revisions'] = defined('WP_POST_REVISIONS') ? WP_POST_REVISIONS : false;

                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_wp_config, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
                
            // send back some data ##
            return $get_wp_config;
            
        }
        
        
        /**
         * Get .htaccess settings
         * 
         * @since       0.3
         * @return      array   Containing info about wp_config.php ( file_exists, is_writeable, get_file_contents )
         */
        public static function get_htaccess() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_htaccess'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_htaccess = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
            
                // set a new array ##
                $get_htaccess = array();

                // https://github.com/Yoast/wordpress-seo/blob/master/admin/pages/files.php ##
                if ( file_exists( get_home_path() . ".htaccess" ) ) {
                    $get_htaccess["exists"] = true;
                    $get_htaccess_file = get_home_path() . ".htaccess";
                    $get_htaccess["content"] = file_get_contents($get_htaccess_file);
                    if ( is_writeable( $get_htaccess_file ) ) {
                        $get_htaccess["is_writeable"] = true;
                    }
                }
            
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_htaccess, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
                
            // send back some data ##
            return $get_htaccess;
            
        }
                
        
        /**
         * Get Server Info ##
         * 
         * @since       0.3
         * @return      array   Containing sanitized server details ##
         */
        public static function get_server() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_server'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_server = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
                
                // grab globals ##
                global $q_support_options;
                
                // set a new array ##
                $get_server = array();
            
                $get_server["phpversion"] = phpversion();
                $get_server["php_uname"] = php_uname('s');
                $get_server["php_os"] = PHP_OS;
                $get_server["server_software"] = $_SERVER['SERVER_SOFTWARE'];
                
                if ( date_default_timezone_get() ) {
                    $get_server["date_default_timezone_get"] = date_default_timezone_get();
                }

                if ( ini_get('date.timezone') ) {
                    $get_server["ini_get__date.timezone"] = ini_get('date.timezone');
                }
                
                $get_server["mysql_get_server_info"] = mysql_get_server_info();
                
                // php_info is overkill - set constant / option to true to include ##
                if ( isset( $q_support_options["get_server__phpinfo"] ) && $q_support_options["get_server__phpinfo"] === true ) $get_server["phpinfo"] = phpinfo();
                
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_server, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
                
            // send back some data ##
            return $get_server;
            
        }
        
        
        /**
         * Get Client Info ##
         * 
         * @since       0.3
         * @return      array   Containing sanitized client details ##
         */
        public static function get_client() 
        {
            
            // Generate a cache key that will hold the response for this request: 
            $transient_key = 'q_support_get_client'; 

            // Check transient. If it's there - use that, if not re fetch the theme  
            if ( false === ( $get_client = get_transient( $transient_key ) ) || Q_SUPPORT_CACHE === false ) {  
            
                // set a new array ##
                $get_client = array();
                
                // all known options ##
                // @src     http://stackoverflow.com/questions/1895727/how-can-i-detect-the-browser-with-php-or-javascript
                $browsers = array(
                    'firefox', 'msie', 'opera', 'chrome', 'safari', 'mozilla', 'seamonkey', 'konqueror', 'netscape',
                    'gecko', 'navigator', 'mosaic', 'lynx', 'amaya', 'omniweb', 'avant', 'camino', 'flock', 'aol'
                );

                if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
                    
                    // browser user-agent ##
                    $get_client["http_user_agent"] = $_SERVER['HTTP_USER_AGENT'];
                    
                    foreach( $browsers as $_browser ) {
                        
                        if ( preg_match( "/($_browser)[\/ ]?([0-9.]*)/", strtolower($get_client["http_user_agent"]), $match ) ) {

                            $get_client["name"] = ucfirst($match[1]);
                            $get_client["version"] = $match[2];
                            @list($get_client['majorver'], $get_client['minorver'], $get_client['build']) = explode('.', $get_client['version']);
                            break;

                        }
                          
                    }
                    
                }
                
                // Set transient for next time... keep it for 24 hours should be good  
                if ( Q_SUPPORT_CACHE === true ) set_transient( $transient_key, $get_client, Q_SUPPORT_CACHE_EXPIRE );  
            
            } // cache ##
                
            // send back some data ##
            return $get_client;
            
        }
        
    }

}