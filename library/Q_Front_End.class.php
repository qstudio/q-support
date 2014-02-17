<?php

/**
 * Front End scripts, views and styles
 * 
 * @since       0.9
 */

if ( ! class_exists( 'Q_Front_End' ) ) 
{
    
    class Q_Front_End
    {
        
        /*--------------------------------------------*
        * Attributes
        *--------------------------------------------*/

        /** Refers to a single instance of this class. */
        private static $instance = null;

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
            
            // front-end options ##
            if ( ! is_admin() ) {
                
                // add scripts ##
                add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ), 100000 );
                
            } else { // admin features ##
                
                
            }
            
        } // end constructor
        
        
        /*--------------------------------------------*
        * Functions
        *--------------------------------------------*/
        
        
        /**
         * Enqueue Plugin Scripts & Styles
         * 
         * @since       0.5
         * @return      void
         */
        public function wp_enqueue_scripts() 
        {
            
            wp_register_style( 'q-support-css', Q_Support::get_plugin_url( 'css/q-support.css' ) );
            wp_enqueue_style( 'q-support-css' );
            
        }
        
    }

}