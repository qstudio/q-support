<?php

/**
 * User & Plugin Settings
 * 
 * @since       0.9
 */

if ( ! class_exists( 'Q_Settings' ) ) 
{
    
    class Q_Settings
    {
        
        /*--------------------------------------------*
        * Attributes
        *--------------------------------------------*/

        /** Refers to a single instance of this class. */
        private static $instance = null;
        
        // default q_support_settings ##
        public static $q_support_settings_default = array(
            'target'        => "community"
            ,'agent'        => "0"
            ,'wordpress'    => "no"
            ,'server'       => "no"
            ,'client'       => "no"
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
            
            // @todo - if user edits their profile email - we need to alert API of the new email address ##
            
            if ( is_admin() ) {
                
                // add admin menus ##
                add_action( 'admin_menu', array( $this, 'add_admin_menu_settings' ), 1 );
                
                // check for q_support_options ##
                add_action( 'admin_init', array( $this, 'get_q_support_options' ), 3 );
                
                // AJAX connect user account to API ##
                add_action('wp_ajax_q_support_connect', array( $this, 'ajax_q_support_connect' ));
                
                // AJAX clear saved support data ##
                add_action('wp_ajax_q_support_settings', array( $this, 'ajax_q_support_settings' ));
                
            }
            
        } // end constructor
        
        
        /*--------------------------------------------*
        * Functions
        *--------------------------------------------*/
        
        
        
        /*
         * Add admin menu
         * 
         * @since       0.3
         * @return      void
         **/
        public function add_admin_menu_settings() 
        {
            
            if ( ! self::get_q_support_user_connected() ) { return false; } // not shown until this user is connectd ##
            
            // add a sub menu to the CPT ##
            $add_admin_menu_settings = add_submenu_page (
                Q_Support::$plugin_url
                ,__( 'Settings', Q_Support::$text_domain ) ## page title
                ,__( 'Settings', Q_Support::$text_domain ) ## menu title
                ,Q_SUPPORT_CAPABILITY ## capability needed
                ,'settings'
                ,array( $this, 'view_settings' )
            );
            
	}
        
        
        
        /**
         * Returns all the settings fields
         *
         * @since       0.3
         * @return array settings fields
         */
        function get_q_support_settings_fields() 
        {
            
            return array (
                
                'target'            => array(
                    'name'          => 'target'
                    ,'label'        => __( 'Support Type', Q_Support::$text_domain )
                    ,'desc'         => __( 'You can send open support requests to the community, or select one specific agent.', Q_Support::$text_domain )
                    ,'type'         => 'select'
                    ,'default'      => 'community'
                    ,'options'      => array(
                        'community' => 'Community'
                        ,'agent'    => 'Dedicated Agent'
                    )
                )

                ,'agent'           => array(
                    'name'          => 'agent'
                    ,'label'        => __( 'Select Agent', Q_Support::$text_domain )
                    ,'desc'         => sprintf( 
                                        __( 'Select an agent from the list. <a href="%s" class="_blank">%s</a>.', Q_Support::$text_domain )
                                        , esc_url( Q_SUPPORT_DOMAIN.'meta/dedicated-agents/' )
                                        , esc_html( __("Read More", Q_Support::$text_domain ) ) 
                                    )
                    ,'type'         => 'select'
                    ,'default'      => '0'
                    ,'options'      => $this->get_agents()
                )

                ,'wordperss'        => array(
                    'name'          => 'wordpress'
                    ,'label'        => __( 'WordPress', Q_Support::$text_domain )
                    ,'desc'         => sprintf( 
                                        __( 'Include information about your <a href="%s" class="_blank">%s</a>.', Q_Support::$text_domain )
                                        , esc_url( Q_SUPPORT_DOMAIN.'plugin/wordpress-data/' )
                                        , esc_html( __("WordPress Installation", Q_Support::$text_domain ) ) 
                                    )
                    ,'type'         => 'select'
                    ,'default'      => 'yes'
                    ,'options'      => array(
                        'yes'       => 'Yes'
                        ,'no'       => 'No'
                    )
                )

                ,'server'           => array(
                    'name'          => 'server'
                    ,'label'        => __( 'Server', Q_Support::$text_domain )
                    ,'desc'         => sprintf( 
                                        __( 'Include information about your <a href="%s" class="_blank">%s</a>.', Q_Support::$text_domain )
                                        , esc_url( Q_SUPPORT_DOMAIN.'plugin/web-server-data/' )
                                        , esc_html( __("Web Server", Q_Support::$text_domain ) ) 
                                    )
                    ,'type'         => 'select'
                    ,'default'      => 'yes'
                    ,'options'      => array(
                        'yes'       => 'Yes'
                        ,'no'       => 'No'
                    )
                )

                ,'client'           => array(
                    'name'          => 'client'
                    ,'label'        => __( 'Client', Q_Support::$text_domain )
                    ,'desc'         => sprintf( 
                                        __( 'Include information about your <a href="%s" class="_blank">%s</a>.', Q_Support::$text_domain )
                                        , esc_url( Q_SUPPORT_DOMAIN.'plugin/client-data/' )
                                        , esc_html( __("Browser", Q_Support::$text_domain ) ) )
                    ,'type'         => 'select'
                    ,'default'      => 'yes'
                    ,'options'      => array(
                        'yes'       => 'Yes'
                        ,'no'       => 'No'
                    )
                )
                    
            );

        }

        
        /**
         * Plugin Settings Page 
         * 
         * @since       0.3
         * @return      String      HTML Date for settings page
         * @link        http://ednailor.com/2012/04/16/allowing-non-admin-users-to-update-theme-options/
         */
        public function view_settings() 
        {
            
            // quick check on who's viewing ##
            if ( ! current_user_can( Q_SUPPORT_CAPABILITY ) ) { 
                
                wp_die( _e( 'You do not have sufficient permissions to access this page.', Q_Support::$text_domain ) );
                
            }
            
            // open wrap ##
            echo '<div class="wrap q_support_wrap">';
            
            // todo - remove testing calls here ##
            #q_support_poll();
            #pr(Q_Plugin::$store['q_support_poll']);
            
            // icon and h2 ##
            screen_icon("q_support");
            echo '<h2>'; _e( 'WordPress Support Settings', Q_Support::$text_domain ); echo '</h2>';

            // intro blurb ##
            printf( 
                __('
                    <p>You can make support requests to the <a href="%1$s" class="_blank">%2$s</a> or a <a href="%3$s" class="_blank">%4$s</a> or find out more about how the <a href="%5$s" class="_blank">%6$s</a> works.</p>'
                    , Q_Support::$text_domain 
                )
                ,esc_url( Q_SUPPORT_DOMAIN.'meta/community-support/' )
                ,esc_html( __("Community", Q_Support::$text_domain ) ) 
                ,esc_url( Q_SUPPORT_DOMAIN.'meta/dedicated-agent/' )
                ,esc_html( __("Dedicated Agent", Q_Support::$text_domain ) ) 
                ,esc_url( Q_SUPPORT_DOMAIN.'meta/support-process/' )
                ,esc_html( __("Support Process", Q_Support::$text_domain ) ) 
            );
            
            // grab the q_support_settings_fields ##
            $q_support_settings_fields = $this->get_q_support_settings_fields();
            
            // grab the saved q_support_settings for the current user ##
            $q_support_settings = $this->get_q_support_settings();
            #wp_die( pr( $q_support_settings ) );
            
?>
            <form id="q_support_settings" name="q_support_settings" method="post">
                <table class="form-table">
                    <tbody>
<?php                        

                        foreach ( $q_support_settings_fields as $field ) {

?>                   
                        <tr valign="top">
                            <th scope="row"><label for="<?php echo $field["name"]; ?>"><?php echo $field["label"]; ?>:</label></th>
                            <td>
                                <select name="q_support_settings_<?php echo $field["name"]; ?>" id="q_support_settings_<?php echo $field["name"]; ?>" class="required">
<?php

                                    foreach ( $field["options"] as $option => $value ) {
                                        
                                        ?><option value="<?php echo $option; ?>" <?php selected( $q_support_settings[$field["name"]], $option ); ?>><?php echo $value; ?></option><?php
                                        
                                    }

?>
                                </select>
                                <p class="description"><?php echo $field["desc"]; ?></p>
                            </td>
                        </tr>
<?php                        

                        } // field loop ##

?> 
                    </tbody>
                </table>

                <p>
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( "Save Settings", Q_Support::$text_domain ); ?>">
<?php

                    // note about slow saving, as we do the first load in of settings ##
                    printf( 
                        __('
                            <p><strong>Note:</strong> The settings page may take a while to save if you have selected to include WordPress Data.</p>'
                            , Q_Support::$text_domain 
                        )
                    );

?>
                </p>
                
            </form>


<?php

            
            // tidy up ##
            echo '</div>';
            
        }
        
         
        /**
         * Save user plugin settings
         * 
         * @since       0.5
         * @return      mixed     HTML message on success or False if errors encountered
         */
        public function ajax_q_support_settings() 
        {
            
            // check nonce ##
            check_ajax_referer( 'q_support_nonce', 'nonce' );
            
            // check for "connected" - in case there is nothing to do here ##
            if ( ! $this->get_q_support_user_connected() ) { 
                
                $return = __( "You need to Connect your account first.", Q_Support::$text_domain );
                
                // return it ##
                echo json_encode($return);

                // all AJAX calls must die!! ##
                die();
                
            }
            
            if ( ! $this->get_current_user("ID") ) {
                
                $return = __( "User Account Error!", Q_Support::$text_domain );
                
                // return it ##
                echo json_encode($return);

                // all AJAX calls must die!! ##
                die();
                
            }
            
            // let's see if the required data was passed ##
            if ( 
                isset( $_POST['q_support_settings_target'] ) && $_POST['q_support_settings_target'] // support audience ##
                #&& isset( $_POST['q_support_settings_agent'] ) && $_POST['q_support_settings_agent'] // dedicated agent ##
                && isset( $_POST['q_support_settings_wordpress'] ) && $_POST['q_support_settings_wordpress'] // send WP settings ##
                && isset( $_POST['q_support_settings_server'] ) && $_POST['q_support_settings_server'] // send server settings ##
                && isset( $_POST['q_support_settings_client'] ) && $_POST['q_support_settings_client'] // send client settings ##
            ) {
                
                // declare a new array ##
                $q_support_settings = array();
                
                // sanitize post data and add to named variables ##
                $q_support_settings['target']= Q_Plugin::sanitize( $_POST['q_support_settings_target'] );
                $q_support_settings['agent'] = Q_Plugin::sanitize( $_POST['q_support_settings_agent'] );
                $q_support_settings['wordpress'] = Q_Plugin::sanitize( $_POST['q_support_settings_wordpress'] );
                $q_support_settings['server'] = Q_Plugin::sanitize( $_POST['q_support_settings_server'] );
                $q_support_settings['client'] = Q_Plugin::sanitize( $_POST['q_support_settings_client'] );
                
                // save it all ##
                $this->set_q_support_settings( $q_support_settings );
                
                // now do some data pre-loading ##
                if ( $q_support_settings['wordpress'] == 'yes' ) {
                    
                    Q_Data::get_htaccess();
                    Q_Data::get_wp_config();
                    Q_Data::get_wordpress();
                    Q_Data::get_post_types();
                    Q_Data::get_theme();
                    Q_Data::get_plugins();
                    Q_Data::get_sidebars();
                    
                }
                
                // return it ##
                echo json_encode( __( 'Account Settings Saved', Q_Support::$text_domain ) );
                
                // all AJAX calls must die!! ##
                die();
                
            } else {
                
                // return it ##
                echo json_encode( __( 'Error Saving Settings!', Q_Support::$text_domain ) );
                
                // all AJAX calls must die!! ##
                die();
                
            }

        }
        
        
        /**
         * Get wp-suppport.co Agents
         * 
         * @since       0.2
         * @return      Array  list of agent names via API call 
         */
        public static function get_agents() 
        {
            
            // let's cache this for 1 hour ##
            $get_agents = get_transient( 'get_agents' );
            
            if ( empty( $get_agents ) ) {
                
                // default data ##
                $get_agents = array(
                    '0' => __( 'Select', Q_Support::$text_domain )
                );   
                
                // wp_remote_post call to API ## 
                $post_data = array( 
                    'method'        => 'get_agents'
                    ,'key'          => 'agents_'.str_replace( ".", "_", Q_SUPPORT_VERSION ) // versioned key ##
                );

                // Authentication ##
                $headers    = array( 
                    'Authorization' => 'Basic ' . base64_encode( $post_data['key'] ) // very basic ##
                );

                // do it ! ##
                $result     = wp_remote_post ( 
                                Q_Support::$q_support_api_url 
                                ,   array( 
                                        'headers'   => $headers
                                    ,   'body'      => $post_data
                                    ,   'timeout'   => Q_Support::$q_support_api_timeout, // set timeout ##
                                ) 
                            );


                if ( is_wp_error( $result ) ) {

                    // could give some warning here ?

                } else {

                    // decode the results ##
                    $results = isset( $result["body"] ) ? json_decode( $result["body"] ) : wp_die( $result ) ;

                    // returned true ##
                    if ( isset( $results->status ) && $results->status === true && isset( $results->response ) ) {

                        add_option( 'q_support_get_agents', $results->response );

                        foreach( $results->response as $agent ) {

                            $get_agents[$agent->ID] = $agent->user_nicename; // add each agent to the array ##

                        }

                    }

                }
                
                // save transient data - 1 hour ##
                set_transient('get_agents', $get_agents, 3600 );
                
            }
            
            // kick it back ##
            return $get_agents;
            
        }
        
        
        /**
         * Connect user to wp-support.co API
         * 
         * @since       0.3
         * 
         * @return      mixed     HTML message on success or False if errors encountered ##
         */
        public function ajax_q_support_connect() 
        {
            
            // check nonce ##
            check_ajax_referer( 'q_support_nonce', 'nonce' );
            
            // check for "connected" - in case there is nothing to do here ##
            if ( self::get_q_support_user_connected() ) { 
                
                $return = __( "Account already connected", Q_Support::$text_domain );
                
            }
            
            // get plugin options ##           
            $q_support_options = self::get_q_support_options();
            
            // get user ##
            $user = self::get_current_user();

            // check details ##
            if ( $user && is_user_logged_in() && $user->user_login && $user->user_email ) {

                // grab a shiny new key ##
                $q_support_key = Q_Plugin::generate_key();
                
                // let's dump and check some things here ##
                $q_dump = array(
                        'key'       => $q_support_key
                    ,   'username'  => $user->user_login
                    ,   'email'    => $user->user_email
                );

                // store it ##
                if ( Q_PLUGIN_DEBUG ) { update_option( "q_support_connect_wps", $q_dump ); }
                
                // http://kovshenin.com/2011/a-note-on-wp_http-and-http-requests-in-wordpress/
                $headers    = array( 
                    'Authorization' => 'Basic ' . base64_encode( "$user->user_login:$user->user_pass" ) // very basic ##
                );
                
                // wp_remote_post call to API ## 
                $post_data = array( 
                        'method'   => 'connect'
                    ,   'username' => $user->user_login
                    ,   'email'    => $user->user_email
                    ,   'key'      => $q_support_key
                );

                // do it ! ##
                $result     = wp_remote_post ( 
                                Q_Support::$q_support_api_url 
                                ,array( 
                                        'headers'   => $headers
                                    ,   'body'      => $post_data
                                    ,   'timeout'   => Q_Support::$q_support_api_timeout, // set timeout ##
                                ) 
                            );


                if ( is_wp_error( $result ) ) {

                    $error_message = $result->get_error_message();
                    $q_support_response = $error_message;
                    $return = $error_message;
                    
                    // save it all ##
                    self::set_q_support_settings( array( "connect_response" => $q_support_response ) );

                } else {

                    // decode the results ##
                    $results = isset( $result["body"] ) ? json_decode( $result["body"] ) : wp_die( $result ) ;
                    
                    // save API results ##
                    self::set_q_support_settings( array( 
                            "connect_response"  => isset( $results->response ) ? $results->response : '' 
                        ,   "connect_debug"     => isset( $results->debug ) ? $results->debug : '' 
                        ,   "connect_code"      => isset( $result["response"] ) ? $result["response"] : '' 
                    ));
                    
                    // if user was created - update the "connected" and move on ##
                    if ( isset( $results->status ) && $results->status === true ) {
                        
                        // last check - for returned key ##
                        if ( ! isset( $results->key ) ) {
                            
                            $return = __( 'Unknown Connection Error', Q_Support::$text_domain );
                            
                            // save it all ##
                            self::set_q_support_settings( array( 
                                    "connect_response"  => isset( $q_support_response ) ? $return : '' 
                            ));
                    
                        } else {
                            
                            // let's dump and check some things here ##
                            $q_dump = array(
                                    'key'           => $results->key
                            );

                            // store it ##
                            if ( Q_PLUGIN_DEBUG ) { update_option( "q_support_connect_wps_result", $q_dump ); }
                            
                            // save it all ##
                            self::set_q_support_settings( array( 
                                    "connected"     => true
                                ,   "key"           => $results->key
                            ));
                            
                            // add capacity 'can_request_support' to user ##
                            // http://wordpress.stackexchange.com/questions/57977/how-to-assign-capabilities-to-user-not-to-user-role/60433#60433
                            $user = new WP_User( $user->ID );
                            $user->add_cap( 'can_request_support');

                            // notice ##
                            $return = $results->response.' - '.sprintf( 
                                        __( 'You can now make <a href="%s">%s</a>', Q_Support::$text_domain )
                                        ,esc_url( Q_Support::$plugin_url )
                                        ,esc_html( __("Support Requests", Q_Support::$text_domain ) ) 
                                    );
                        
                        }
                        
                    } else { // status came back false ##

                        // just send back the API response ##
                        $return = $results->response;

                    }

                }
                
            } else {

                $return = __( "Connection Error!", Q_Support::$text_domain );

            }
            
            // return it ##
            echo json_encode($return);

            // all AJAX calls must die!! ##
            die();

        }
        
        
        /**
         * Get Current User Info
         * 
         * @since       0.2
         * @link        http://codex.wordpress.org/Function_Reference/get_currentuserinfo 
         * @return      Mixed   Array user details or string of single user value
         */
        public static function get_current_user( $field = null ) 
        {
            
            // user is not logged in - nothing to get ##
            if ( ! is_user_logged_in() ) { return false; }
            
            // grab the current user data ##
            global $current_user;
            get_currentuserinfo();
            
            // check if a specific field was requested from the user data - if not return the entire object ##
            if ( ! $field ) { return $current_user; }
            
            // return data ##
            return $current_user->$field;
                    
        }
        
        
        /**
         * Get Plugin Default Options
         * 
         * @since       0.3
         * @return      Array  Default plugin options
         */
        public static function get_q_support_options_default() 
        {
            
            // define all required options ##
            return array(
                
                // q_support options page ##
                
                // "get_server" method ##
                'get_server__phpinfo' => false,
                
            );
            
       }
        
        
        /**
         * Get Saved Plugin Settings ##
         * 
         * @since       0.3
         * @return      Array   Containing WPS Plugin settings for use inside WP ##
         */
        public static function get_q_support_options() 
        {
            
            // get_option "q_support_options" - default to an empty array ##
            $q_support_options = get_option( 'q_support_options', false );
            
            // nothing cooking, so grab the default options ##
            if ( false === $q_support_options ) {
                
                #wp_die(pr( self::get_q_support_options_default() ));
                
                // create new option for plugin settings - set to autoload ##
                add_option( 'q_support_options', self::get_q_support_options_default(), '', 'yes' );
                
                // get_option "q_support_options" - default to an empty array ##
                $q_support_options = get_option( 'q_support_options', array() );
                
            }
            
            //return array ##
            return $q_support_options;
            
        }
        
        
        /**
         * Helper function to check if User is connected to the wp-support forum
         * 
         * @since       0.3
         * @return      boolean   
         */
        public static function get_q_support_user_connected() 
        {
            
            // get user settings ##
            $q_support_settings = self::get_q_support_settings();
            
            if ( 
                    ! $q_support_settings
                    || ! isset ( $q_support_settings["connected"] )
                    || ! isset ( $q_support_settings["key"] )
                ) 
            { 
                
                return FALSE; 
                
            } else {
                
                return TRUE;
                
            }
            
        }
        
        
        /**
         * Helper function to get user q_support_key
         * 
         * @since       0.3
         * @return      Mixed   string of user key on success | boolean false on failure   
         */
        public static function get_q_support_user_key() 
        {
            
            // get user settings ##
            $q_support_settings = self::get_q_support_settings();
            
            if ( 
                    ! $q_support_settings
                    || ! isset ( $q_support_settings["connected"] )
                    || ! isset ( $q_support_settings["key"] )
                ) 
            { 
                
                return FALSE; 
                
            } else {
                
                return $q_support_settings["key"];
                
            }
            
        }
        
        
        /**
         * Get Plugin Settings
         * 
         * @since       0.3
         * @return      Mixed    Array of plugin settings OR Boolean FALSE if errors encountered 
         */
        public static function get_q_support_settings() 
        {
            
            $q_support_settings = get_user_meta( self::get_current_user("ID"), 'q_support_settings', true );
            
            // if the settings are missing - add the defaults ##
            if ( ! $q_support_settings || count( $q_support_settings ) == 0 || ! is_array ( $q_support_settings ) || $q_support_settings = '' ) {
                
                // save default settings ##
                update_user_meta( self::get_current_user("ID"), 'q_support_settings', self::$q_support_settings_default );
                
                // kick back the defaults ##
                return self::$q_support_settings_default;
                
            }
            
            // return settings ##
            return get_user_meta( self::get_current_user("ID"), 'q_support_settings', true );
            
        }
        
        
        /**
         * Set User Plugin Settings
         * 
         * @since       0.3
         * @return      Boolean 
         */
        public static function set_q_support_settings( $settings = null ) 
        {
            
            #pr( $settings );
            
            // sanity check ##
            if ( ! $settings || ! is_array( $settings ) ) { return false; }
            
            // get user settings ##
            $q_support_settings = self::get_q_support_settings();
            
            // kick back if not found - which would be odd ##
            if ( empty ( $q_support_settings ) ) { 
                
                return false; 
                
            }
            
            // sanitize array values ##
            $clean_by_reference = function( &$val ) {
                
                if ( is_integer( $val ) || $val === 0 ) {
                    #pr( "input sanitize value: {$val}" );
                    $val = Q_Plugin::sanitize( $val, 'integer' );
                } else {
                    $val = Q_Plugin::sanitize( $val );
                }
                
            };
            array_walk_recursive( $settings, $clean_by_reference );

            // loop over each key ##
            foreach( $settings as $key => $value ) {
                
                $q_support_settings[sanitize_text_field($key)] = $value;
                
            }
            
            // remove keys with empty values ##
            array_filter( $q_support_settings );
            
            #wp_die(pr($q_support_settings));
            
            // update user meta ##
            update_user_meta( self::get_current_user("ID"), 'q_support_settings', $q_support_settings );
            
        }
        
        
    }

}