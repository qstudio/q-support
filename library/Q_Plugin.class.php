<?php

/*
 * Name:        Q Plugin Helper Class
 * Description: Slimmed down version of Q 
 * Version:     1.4.0
 * Author:      Q Studio
 * Author URI:  http://www.qstudio.us
 * Class:       Q_Plugin
 */

// quick check :) ##
defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'Q_Plugin' ) ) {
    
    // define constants ##
    define( 'Q_PLUGIN_VERSION', '1.4.0' );
    define( 'Q_PLUGIN_DEBUG', false );
    
    class Q_Plugin {
        
        public static $store;
        
        /**
         * Pretty print_r / var_dump
         * 
         * @since       0.1
         * @param       Mixed       $var        PHP variable name to dump
         * @param       string      $title      Optional title for the dump
         * @return      String      HTML output
         */
        public static function pr( $var, $title = null ) 
        { 
            
            if ( $title ) $title = '<h2>'.$title.'</h2>';
            print '<pre class="var_dump">'; echo $title; var_dump($var); print '</pre>'; 
            
        }
        
        
        /**
         * Request data safely using $_GET, $_POST & $_REQUEST
         * 
         * @since   0.1
         * @param   string      $key:           Key to search for
         * @param   string      $sanitize       Sanatize method to apply to the data
         * @param   Boolean     $debug          Allows for individual debugging of keys
         * @return  mixed       boolean | string         
         */
        public static function request_safe( $key = null, $sanitize = null, $debug = false, $methods = array( 'post' => true ) )
        {
            
            // quick check ##
            if ( ! $key ) { return false; }
            
            // debugging on - so allow broader range of request methods ##
            if ( defined( 'Q_DEBUG' ) && Q_DEBUG === true ) {
                
                $methods['get'] = true; // allow $_GET method ##
                #$methods['request'] = true; // allow $_REQUEST method ##
                
            }
            
            // check for key in allowed superglobals ##
            foreach( $methods as $method => $value ) {
                
                if ( $value === true ) { // method allowed ##
                    
                    switch ( $method ) {
                        
                        case 'get':
                            
                            if ( isset( $_GET[ $key ] ) ) {  
                                
                                if ( $debug === true ) { pr($_GET[ $key ]); } // debug ##
                                
                                return Q::sanitize( $_GET[ $key ], $sanitize );
                                
                            }
                                
                            break;
                        
                        case 'post':
                        default:
                            
                            if ( isset( $_POST[ $key ] ) ) {  
                                
                                if ( $debug === true ) { wp_die($_POST[ $key ]); } // debug ##
                                
                                return Q::sanitize( $_POST[ $key ], $sanitize );
                                
                            }
                            
                            break;
                        
                    }
                    
                }
                
            }
            
            // nothing happening ##
            return false;
            
        }
        
        
        /**
         * Sanitize user input data using WordPress functions
         * 
         * @since       0.1
         * @param       string      $value      Value to sanitize
         * @param       string      $type       Type of value ( email, user, int, key, text[default] )
         * @link        http://codex.wordpress.org/Validating_Sanitizing_and_Escaping_User_Data
         * @link        http://wp.tutsplus.com/tutorials/creative-coding/data-sanitization-and-validation-with-wordpress/
         * @return      string      HTML output
         */
        public static function sanitize( $value = null, $type = 'text' )
        {
            
            // check submitted data ##
            if ( is_null( $value ) ) {
                
                return false;
                
            }
            
            switch ($type) {
                
                case( 'email' ):
                
                    return sanitize_email( $value );
                    break;
                
                case( 'user' ):
                
                    return sanitize_user( $value );
                    break;
                
                case( 'integer' ):
                    
                    #pr( "sanitize value: {$value}" );
                    return intval( $value );
                    break;
                
                case( 'filename' ):
                
                    return sanitize_file_name( $value );
                    break;
                
                case( 'key' ):
                
                    return self::sanitize_key( $value ); // extended version of wp sanatize_key
                    break;
                
                case( 'sql' ):
                    
                    return esc_sql( $value );
                    break;
                
                case( 'stripslashes' ):
                    
                    return preg_replace("~\\\\+([\"\'\\x00\\\\])~", "$1", $value);
                    #stripslashes( $value );
                    break;
                
                case( 'none' ):
                    
                    return $value;
                    break;
                
                case( 'text' ):
                default;
                     
                    // text validation
                    return sanitize_text_field( $value );
                    break;
                    
            }
            
        }
        
        
        /**
        * Sanitizes a string key.
        *
        * @since 1.3.0
        * @param string $key String key
        * @return string Sanitized key
        */
        public static function sanitize_key( $key = null ) 
        {
            
            // sanity check ##
            if ( ! $key ) { return false; }
            
            // scan the key for allowed characters ##
            $key = preg_replace( '/[^a-zA-Z0-9_\-~!$^+]/', '', $key );

            // return the key ##
            return $key;
            
       }
       
       
       
        
        /**
         * Generate Key
         * 
         * @since       0.7
         * @param       Int         $length     length of key to produce ( optional )
         * @return      string      Key
         */
        public static function generate_key( $length = null ) 
        {
            
            // harder, but we'll need our own sanitize_key function ##
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            
            // declare our return variable ##
            $key = '';
            
            // loop over the character set a randomly defined number of times ##
            for( $i = 0; $i < ( isset( $length ) ? $length : mt_rand( 15, 50 ) ); $i++ ) {
            
                $key .= $chars[ mt_rand( 0, strlen( $chars )-1) ];
                
            }
            
            // kick it back ##
            return sanitize_file_name( $key );
            
        }
       
        
       
    }

// class_exists check ##
}