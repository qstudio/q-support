<?php

/**
 * Plugin Name:     WordPress Support
 * Plugin URI:      http://wp-support.co/
 * Description:     NOTE: This plugin is currently in BETA development and will be released soon!
 * Version:         1.5.0
 * Author:          Q Studio
 * Author URI:      http://qstudio.us
 * License:         GPL2
 * Class:           Q_Support
 * Text Domain:     q-support
 * GitHub Plugin URI: qstudio/q-support
 */

/*  

Copyright 2014 Q Studio ( url : http://qstudio.us )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

// quick check :) ##
defined( 'ABSPATH' ) OR exit;

/* Plugin Class */
if ( ! class_exists( 'Q_Support' ) ) 
{
    
    // plugin version
    define( 'Q_SUPPORT_VERSION', '1.5.0' ); // version ##

    // cache ##
    define( 'Q_SUPPORT_CACHE', true ); // cache and transients ##
    define( 'Q_SUPPORT_CACHE_EXPIRE', 60*60*24*7 ); // cache timeout ##

    // error logs ##
    define( 'Q_SUPPORT_LOG_FILE',  plugin_dir_path( __FILE__ )."logs/debug.log" ); // path to file ##
    define( 'Q_SUPPORT_LOG_PATH',  plugin_dir_path( __FILE__ )."logs/" ); // path to log folder ##
    define( 'Q_SUPPORT_LOG_MAX_KB', 1024 ); // max log file size - 1 MB for now ##
    
    // general usage abso path to plugin directory ##
    define( 'Q_SUPPORT_PATH',  plugin_dir_path( __FILE__ ) ) ; // path to plugin ##
    
    // date ##
    define( 'Q_SUPPORT_DATE_FORMAT', 'Y-m-d H:i:s' );

    // data whitelisting ##
    define( 'Q_SUPPORT_WHITELIST_DOMAIN', 'domain.com' ); 

    // for links out ##
    define( 'Q_SUPPORT_DOMAIN', 'http://www.wp-support.co/' );

    // required capability to fully use this plugin ##
    define( 'Q_SUPPORT_CAPABILITY', 'edit_posts' ); // to view settings and make support requests ##
    define( 'Q_SUPPORT_CAPABILITY_ADMIN', 'manage_options' ); // to debug and view error.log ##
    
    // Q_Plugin library ##
    require_once plugin_dir_path( __FILE__ ) . 'library/Q_Plugin.class.php';
    
    // Q_Errors library ##
    require_once plugin_dir_path( __FILE__ ) . 'library/Q_Errors.class.php';
    
    // Q_Admin library ##
    require_once plugin_dir_path( __FILE__ ) . 'library/Q_Admin.class.php';
    
    // Q_Data library ##
    require_once plugin_dir_path( __FILE__ ) . 'library/Q_Data.class.php';
    
    // Q_Settings library ##
    require_once plugin_dir_path( __FILE__ ) . 'library/Q_Settings.class.php';
    
    // Q_Front_End library ##
    require_once plugin_dir_path( __FILE__ ) . 'library/Q_Front_End.class.php';
    
    // on plugin activation ##
    register_activation_hook( __FILE__, array( 'Q_Support', 'activation_hook' ) );

    // on plugin deactivation ##
    register_deactivation_hook( __FILE__, array( 'Q_Support', 'deactivation_hook' ) );
    
    // on plugin uninstall ##
    register_uninstall_hook( __FILE__, array( 'Q_Support',  'uninstall_hook' ) );
    
    // instatiate plugin via WP hook - not too early, not too late ##
    add_action( 'init', array ( 'Q_Support', 'get_instance' ), 0 );
    
    // declare the class and base Class ##
    class Q_Support 
    {
        
        // Refers to a single instance of this class. ##
        private static $instance = null;
        
        // public static properties ##
        public static $plugin_url = 'edit.php?post_type=q_support';
        public static $text_domain = 'q-support'; // for translation ##
        public static $q_support_api_url; // API URL ##
        public static $q_support_api_timeout = 60; // allows us to extend the timeout on API calls ##
        
        
        /**
         * Creates or returns an instance of this class.
         *
         * @return  Foo     A single instance of this class.
         */
        public static function get_instance() {

            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;

        }
        
        
        /**
         * Instatiate Class
         * 
         * @since       0.2
         * @return      void
         */
        private function __construct() 
        {
            
            // plugin and user settings ##
            Q_Settings::get_instance();
            
             // error logging ##
            Q_Errors::get_instance();
            
            // admin modifications
            Q_Admin::get_instance();
            
            // data collection and display ##
            Q_Data::get_instance();
            
            // front-end display and views ##
            Q_Front_End::get_instance();
            
        }
        
        
        /**
         * Plugin Activation
         * 
         * @since       0.3
         * @return      void
         */
        public static function activation_hook()
        {
            
            // get plugin options ##
            $q_support_options = Q_Settings::get_q_support_options();
            
            // if the user who actiated the plugin is not connected to the API - give them a notice to Connect ##
            if ( ! Q_Settings::get_q_support_user_connected() ) {
            
                // get plugin name ##
                $name = get_file_data( __FILE__, array ( 'Plugin Name' ), 'plugin' );

                // save notices to option ##
                $notices = array();
                $notices[] = sprintf ( 
                    __(
                        '<strong>%s</strong> - To submit support requests, you need to <a href="#" class="button q_support_connect q_support_space_both">%s</a> your account - <a href="%s" target="_blank">%s</a>',
                        self::$text_domain
                    )
                    ,esc_html( __("$name[0] Activated", self::$text_domain ) ) 
                    ,esc_html( __("Connect", self::$text_domain ) ) 
                    ,esc_url( Q_SUPPORT_DOMAIN.'meta/terms-of-use/' ) // terms ##
                    ,esc_html( __("Terms of Use", self::$text_domain ) )
                );

                $q_support_options["deferred_admin_notices"] = $notices;

            }
            
            // save plugin version ##
            $q_support_options["version"] = Q_SUPPORT_VERSION; 
            
            // add flag that plugin is configured ##
            $q_support_options["configured"] = true;
            
            // save it all ##
            update_option( 'q_support_options', $q_support_options );
            
            // get and save default settings ##
            Q_Settings::get_q_support_settings();
            
            // Redirect if not activating multiple plugins at once
            if( ! isset( $_GET['activate-multi'] ) ) {
                wp_redirect( admin_url( self::$plugin_url ) );
            }
            
        }
        
                
        /**
         * Plugin Deactivation
         * 
         * @since       0.3
         * @return      void
         */
        public static function deactivation_hook()
        {
            
            // clear all transient data ##
            Q_Data::delete_cache();
            
            // delete the error log file ##
            Q_Errors::delete_error_log();
            
            // array of plugin options ##
            $options = array (
                    'q_support_options' // plugin settings - ok to delete on deactivate ##
                ,   'q_store'           // debugging tracker ##
                ,   'q_support_before_delete_post' // dump delete tracker ##
            );
            
            // remove plugin options ##
            Q_Data::delete_options( $options );
            
            // array of plugin user_meta to delete for all users ##
            $user_meta_keys = array (
                    'q_support_settings'
            );
            
            // batch delete user_meta ##
            Q_Admin::delete_user_meta( $user_meta_keys );
            
            // remove cron task ##
            q_support_clear_schedule();
            
        }
        
        
        /**
         * Plugin Uninstalled
         * 
         * @since       0.3
         * @return      void
         */
        public static function uninstall_hook()
        {
            
            // array of plugin options - should already be empty  ##
            $options = array (
                    'q_support_options'     // plugin settings ##
                ,   'q_store'               // debugging tracker ##
                ,   'q_support_before_delete_post' // dump delete tracker ##
                     
            );
            
            // remove plugin options - should already be empty  ##
            Q_Data::delete_options( $options );
            
            // array of plugin user_meta to delete for all users ##
            $user_meta_keys = array (
                    'q_support_settings'
            );
            
            // batch delete user_meta ##
            Q_Admin::delete_user_meta( $user_meta_keys );
            
            // clear all transient data - should already be empty ##
            Q_Data::delete_cache();
            
            // delete the error log file - should already be deleted ##
            Q_Errors::delete_error_log();
            
            // delete the dump directory and all it's contents ##
            Q_Data::delete_directory( plugin_dir_path( __FILE__ )."dump/" );
            
            // delete CPT posts ##
            global $wpdb;

            $query = "
                DELETE FROM wp_posts 
                WHERE post_type = 'q_support' 
                LIMIT 0, 3000
            ";

            $wpdb->query($query);
            
        }
        
        
        /**
         * Get Plugin URL
         * 
         * @since       0.3
         * @return      string  Absoulte URL to plugin directory
         */
        public static function get_plugin_url( $path = '' ) 
        {
            
            return plugins_url( ltrim( $path, '/' ), __FILE__ );
            
        }
        
        
    }
    
}