<?php

/**
 * Error Logging and display
 * 
 * @since       0.9
 */

if ( ! class_exists( 'Q_Errors' ) ) 
{
    
    class Q_Errors
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
            
            // error logging ##
            add_action( 'admin_init', array( $this, 'set_error_logging' ), 1 );
            
            if ( is_admin() ) {
            
                add_action( 'admin_menu', array( $this, 'add_admin_menu_errors' ), 2 ); // error log viewer ##
                
            }
            
        } // end constructor
        
        
        /*--------------------------------------------*
        * Functions
        *--------------------------------------------*/
        
        
        /*
         * Add Error Log View to admin menu
         * 
         * @since       0.3
         * @return      void
         **/
        public function add_admin_menu_errors() 
        {
            
            // If WP_DEBUG is set to true, let's guess the user knows what they are doing and turn this feature off ##
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
                return false;
            }
            
            // add a sub menu to the CPT ##
            $add_admin_menu_errors = add_submenu_page (
                Q_Support::$plugin_url
                ,__('Error Log', Q_Support::$text_domain ) ## page title*/
                ,__('Errors', Q_Support::$text_domain ) ## menu title*/
                ,Q_SUPPORT_CAPABILITY ## capability needed
                ,'errors'
                ,array( $this, 'view_error_log' )
            );
            
	}
        
        
        
        
        /**
         * Set Error Handlers
         * 
         * @since       0.3
         * @link        http://us1.php.net/set_error_handler
         * @ return     void
         */
        public function set_error_logging()
        {
            
            // create the error log file ##
            $this->make_error_log();
            
            // We need to make sure we can capture errors ##
            error_reporting( E_ALL );
            ini_set( 'error_reporting', E_ALL );
            ini_set( 'display_errors', FALSE );
            ini_set( 'display_startup_errors', FALSE );
            ini_set( 'html_errors', FALSE );
            
            // Error handler ##
            set_error_handler( array( $this, 'log_error' ) );
            
            // Uncaught exception handler ##
            set_exception_handler( array( $this, 'log_exception' ) );
            
            // Fatal ones !! ##
            register_shutdown_function( array( $this, 'log_shutdown' ) );
            
        }
        
        
        /**
         * Check Error Log
         * 
         * @since       0.3
         * @return      Array     data about error log ##
         */
        public function check_error_log() 
        {
            
            $check_error_log = array();
            $check_error_log["filesize"] = 0; // default to zero ##
            $check_error_log["file_exists"] = false; // default to no ##
            
            if ( file_exists( Q_SUPPORT_LOG_FILE ) ) {
                
                // ok ! ##
                $check_error_log["file_exists"] = true;

                // grab size ##
                $check_error_log["filesize"] = round( filesize( Q_SUPPORT_LOG_FILE ) / 1024 );
                
            }
            
            if ( is_readable( Q_SUPPORT_LOG_FILE ) ) {
                $check_error_log["is_readable"] = true;
            }
            
            if ( is_writable( Q_SUPPORT_LOG_FILE ) ) {
                $check_error_log["is_writable"] = true;
            }
            
            
            
            // archive file, if it's too big ##
            if ( $check_error_log["filesize"] > Q_SUPPORT_LOG_MAX_KB ) {
                
                $this->rename_error_log();
                
            }
            
            return $check_error_log;
            
        }
        
        
        /**
         * Make Error Log
         * 
         * @since       0.3
         * @return      void
         */
        public function make_error_log() 
        {
            
            // get error log details ##
            $check_error_log = $this->check_error_log();
                    
            if ( $check_error_log["file_exists"] === false ) {
                
                $make_error_log = @fopen( Q_SUPPORT_LOG_FILE, 'w' );
                
                if ( $make_error_log === false ) {
                    
                    // get plugin options ##           
                    $q_support_options = Q_Settings::get_q_support_options();
            
                    // save error ##
                    $q_support_options["make_error_log"] = false; 

                    // save it all ##
                    update_option( 'q_support_options', $q_support_options );
                    
                }
                
            }

        }
        
        
        /**
         * Rename Error Log
         * 
         * @since       0.3
         * @return      boolean     True or False ##
         */
        public function rename_error_log() 
        {
            
            // get todays date ##
            $now = date( "Y-m-d-H-i-s", strtotime( "now" ));
            
            // get plugin options ##           
            $q_support_options = Q_Settings::get_q_support_options();

            // save error ##
            $q_support_options["rename_error_log"] = $now; 

            // save it all ##
            update_option( 'q_support_options', $q_support_options );
            
            // do some renaming ##
            return @rename( Q_SUPPORT_LOG_FILE, Q_SUPPORT_LOG_PATH.$now.'_debug.log' );
            
        }        
        
        
        /**
         * Delete Error Log
         * 
         * @since       0.3
         * @return      void
         */
        public static function delete_error_log() 
        {
            
            #@unlink( Q_SUPPORT_LOG_FILE );
            
            $files = glob( Q_SUPPORT_LOG_PATH.'*' ); // get all log files ##
            foreach ( $files as $file ){ // iterate files ##
                if ( is_file( $file ) ) {
                    @unlink( $file ); // delete file ##
                }
            }
            
        }        
        
        
        /*
         * Error handler, passes flow over to the exception logger with new ErrorException.
         * 
         * @since       0.3
         * @return      void
         */
        public function log_error( $errno, $errstr, $errfile, $errline, $errcontext = null ) 
        {
            
            $l = error_reporting();
            
            if ( $l & $errno ) {
                
                $exit = false;
                switch ( $errno ) {
                    
                    case E_USER_ERROR:
                    case E_ERROR:
                        $type = 'Fatal Error';
                        $exit = true;
                    break;
                    case E_USER_WARNING:
                    case E_WARNING:
                        $type = 'Warning';
                    break;
                    case E_USER_NOTICE:
                    case E_NOTICE:
                    case @E_STRICT:
                        $type = 'Notice';
                    break;
                    case @E_RECOVERABLE_ERROR:
                        $type = 'Catchable';
                    break;
                    default:
                        $type = 'Unknown Error';
                        $exit = true;
                    break;
                
                }
                
                $this->log_exception ( new \ErrorException( $type.': '.$errstr, 0, $errno, $errfile, $errline ) );
                
            }
            
        }
        
        
        /**
         * Save sanitized uncaught exception handlers to log ##
         * 
         * @since       0.3
         * 
         * @return      void
         */
        public function log_exception( Exception $e ) 
        {
            
            $message = "[".date(Q_SUPPORT_DATE_FORMAT)."] PHP {$e->getMessage()} in {$e->getFile()} @ line {$e->getLine()};";
            file_put_contents( Q_SUPPORT_LOG_FILE, $message . PHP_EOL, FILE_APPEND );

            // Don't execute PHP internal error handler ##
            return false;
           
        }
        
        
        /**
         * Save fatal errors to log / database
         * 
         * @since       0.3
         * @src         http://stackoverflow.com/questions/4410632/handle-fatal-errors-in-php-using-register-shutdown-function
         * 
         * @return      void
         */
        public function log_shutdown() 
        {
            
            $error = error_get_last();
            
            if ( $error["type"] == E_ERROR ||  $error["type"] == E_USER_ERROR ) {
            
                $this->log_error( $error["type"], $error["message"], $error["file"], $error["line"] );
                
                // Don't execute PHP internal error handler ##
                exit;
                
            }
            
        }
        
        
        /**
         * Get Error Log contents
         * 
         * @since       0.3
         * @return      array   Containing sanitized content from error log ##
         */
        public function view_error_log( $lines = 21, $length = 60, $file = null ) 
        {
            
            // quick check on who's viewing ##
            if ( ! current_user_can( Q_SUPPORT_CAPABILITY ) ) { 
                
                wp_die( _e( "You do not have sufficient permissions to access this page.", "q-support" ) );
                
            }
            
            if ( !$file ) { $file = Q_SUPPORT_LOG_FILE; } // default to plugin error log file ##
            if ( !$lines ) { $lines = 21; } // lines backup - WHY?? ##
            
            // we double the offset factor on each iteration ##
            $multiplier = 1;
            
            // add slashes -- required for winNT? ##
            #$file = addslashes( $file );
            
            // get the size of the file ##
            $bytes = file_exists( $file ) ? filesize( $file ) : 0; 
            
            #wp_die( $bytes. ' / ' . $file );
            
            // not yet complete ##
            $complete = FALSE;
            
            // start a new array ##
            $log = array();
            
            // open it up ##
            $fp = fopen( $file, "r" ) or wp_die( _e( "Can't open $file", "q-support" ) );
            
            while ( $complete === FALSE ) {
                
                //seek to a position close to end of file
                $offset = ( (int)$lines * (int)$length * (int)$multiplier );
                #echo "offset: {$offset} / lines: {$lines} / length {$length} / multiplier {$multiplier} <br />";
                fseek( $fp, -$offset, SEEK_END );

                // we might seek mid-line, so read partial line
                // if our offset means we're reading the whole file, 
                // we don't skip...
                if ( $offset < $bytes ) {
                    fgets( $fp );
                }
                
                // read all following lines, store last x ##
                while ( !feof( $fp ) ) {
                    
                    $line = fgets( $fp );
                    #echo($line);
                    array_push( $log, $line );
                    #$log[] = $line;
                    if ( count( $log ) > $lines ) {
                        array_shift( $log );
                        $complete = TRUE;
                    }
                    
                }
                
                #echo count($log);
                
                // if we read the whole file, we're done, even if we don't have enough lines ##
                if ( $offset >= $bytes ) {
                    $complete = TRUE;
                } else {
                    $multiplier *= 2; //otherwise let's seek even further back
                }
                
            }
            
            // tidy up ##
            fclose( $fp );
            
            // flip the error log - perhaps a bit controversial !! ##
            $log = array_reverse($log);
            
            // format to print ##
            $log_format = '';
            
            // counter ##
            $count = 0;
            
            // download log option ##
            $log_downloadable = true;
            
            // empty log - good work !! ##
            if ( !array_filter( $log ) ) {
                
                $log_format = "<p class='q_support_log_format'><span class='q_support_log_count'>$count</span>".__( 'Error Log is empty - good work :)', Q_Support::$text_domain )."</p>";
                
                // no point in downloading nothing ##
                $log_downloadable = false;
                
            } else {
            
                // clean up the array ##
                foreach( $log as $key => $value ) {

                    // remove empty rows ##
                    if( is_null($value) || $value == '' || $value === false ) {

                        unset( $log[$key] );

                    // format nicely ##
                    } else {

                        $log_format .= "<p class='q_support_log_format'><span class='q_support_log_count'>$count</span>{$value}</p>";

                    }

                    // iterate counter ##
                    $count++;

                }
            
            }
            
            // open wrap ##
            echo '<div class="wrap q_support_wrap">';
            
            // icon and h2 ##
            screen_icon("q_support");
            echo '<h2>'; _e("PHP Error Log"); echo '</h2>';
            
            // intro blurb ##
            printf( 
                '<p>Here are the last 20 entries ( in reverse order ) from the PHP Error Log, you can view the entire file using the link at the bottom - <a href="%1$s" class="_blank">%2$s</a></p>'
                ,esc_url( Q_SUPPORT_DOMAIN.'plugin/error-log/' )
                ,esc_html( __("Documentation", "q-support" ) ) 
            );
            
            // dump it ##
            echo ( $log_format );
            
            // link to error log ##
            if ( $log_downloadable ) {
            
                printf( 
                    '<a href="%s" class="q_support_log_button button _blank">%s ( %s )</a>.'
                    ,esc_url( Q_Support::get_plugin_url( '/logs/debug.log' ) )
                    ,esc_html( __("View Full Error Log", "q-support" ) )
                    ,esc_html( round( $bytes / 1024 )." kb" )
                );
            
            }
            
            // close wrap ##
            echo '</div>';
            
        }
        
    }

}