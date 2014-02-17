<?php

/**
 * Admin modification
 * 
 * @since       0.9
 */

if ( ! class_exists( 'Q_Admin' ) ) 
{
    
    // add CPT column class ##
    require_once plugin_dir_path( __FILE__ ) . 'CPT_columns.class.php';
    
    // user limiter ##
    require_once plugin_dir_path( __FILE__ ) . 'Q_Limit_To_Current_User.class.php';
    
    // Cron ##
    require_once plugin_dir_path( __FILE__ ) . 'q_support_cron.php';
    
    // Poll ##
    require_once plugin_dir_path( __FILE__ ) . 'q_support_poll.php';
    
    class Q_Admin
    {
        
        /*--------------------------------------------*
        * Attributes
        *--------------------------------------------*/

        /** Refers to a single instance of this class. */
        private static $instance = null;

        // help_tab variables ##
        public $help_tabs = array();
        
         // question categories ##
        public $question_categories = array ();
        
        // link to settings page ##
        public $plugin_url_settings = 'edit.php?post_type=q_support&page=settings';
        
        
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
            
            // admin menus - front and back-end ##
            add_action( 'admin_bar_menu', array ( $this, 'admin_bar_menu' ), 100 );
            
            if ( is_admin() ) {
                
                // admin_init ##
                add_action('admin_init', array( $this, 'admin_init' ), 2 );
                
                // set text domain
                add_action( 'admin_init', array( $this, 'load_plugin_textdomain' ), 6 );
                
                // add scripts ##
                add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 100000 );
                
                // add help tab to admin UI ##
                add_action( 'in_admin_header', array( $this, 'add_help_tab' ), 0 );
                
                // add custom post type ##
                add_action( 'init', array( $this, 'register_post_type' ), 1 );

                // modify cpt post row actions ##
                add_action( 'post_row_actions', array( $this, 'post_row_actions' ), 10, 2 );

                // modify cpt columns ###
                add_action( 'admin_init', array( $this, 'manage_columns' ), 4 );
                
                // user columns ##
                add_action( 'manage_users_custom_column', array( $this, 'manage_users_custom_column' ), 0, 3 );
                add_filter( 'manage_users_columns', array( $this,  'manage_users_columns' ) );
                
                // limit results to current user ##
                add_action( 'admin_init', array( $this, 'limit_results' ), 5 );
                
                // upgrade and activation notices ##
                add_action( 'admin_init', array( $this, 'admin_update_notice' ), 5 );
                add_action( 'admin_notices', array( $this, 'admin_notices' ) );
                
                // add settings link to plugin ##
                add_filter( "plugin_action_links_".plugin_basename(__FILE__), array( $this, 'add_plugin_settings_link' ) );
                
                // AJAX save support request ##
                add_action('wp_ajax_q_support_form', array( $this, 'ajax_q_support_form' ));
                
                // clean up when a user deletes a local question ##
                add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );
                
            }
            
        } // end constructor
        
        
        /*--------------------------------------------*
        * Functions
        *--------------------------------------------*/
        
        
        /**
         * Admin Init
         * 
         * @since       0.3
         * @return      void
         */
        public function admin_init() 
        {
            
            // get plugin options ##
            $q_support_options = Q_Settings::get_q_support_options();
            
            // sort out API URL ##
            $q_support_options["api_url"] = Q_SUPPORT_DOMAIN.'api/';
            
            if ( defined( 'Q_PLUGIN_DEBUG' ) && Q_PLUGIN_DEBUG === true ) {
                #$q_support_options["api_url"] = 'http://localhost/wps/wordpress/api/'; // @todo - remove on full public release ##
            }
            
            // api url ##
            Q_Support::$q_support_api_url = $q_support_options["api_url"];
            
            // api timeout ##
            $q_support_options["api_timeout"] = Q_Support::$q_support_api_timeout;
            
            // save options ##
            update_option( 'q_support_options', $q_support_options );
            
            // define question categories ##
            $this->question_categories = array (
                    __('General', Q_Support::$text_domain )
                ,   __('Themes', Q_Support::$text_domain )
                ,   __('Plugins', Q_Support::$text_domain )
                ,   __('Widgets', Q_Support::$text_domain )
                ,   __('Advanced', Q_Support::$text_domain )
                ,   __('Code', Q_Support::$text_domain )
                ,   __('Design', Q_Support::$text_domain )
                ,   __('Support', Q_Support::$text_domain )
                ,   __('Other', Q_Support::$text_domain )
            );            
            
        }

        
        
         /**
         * Load Text Domain for translations ##
         * 
         * @since       0.3
         * 
         * @link        http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way ##
         */
        public static function load_plugin_textdomain() 
        {
            
            load_plugin_textdomain( Q_Support::$text_domain, FALSE, dirname(plugin_basename(__FILE__)).'/languages/' );
            
        }
        
        
        
        /*
        * Add Support Form to Help Tab
        * 
        * @since        0.3
        * @return       string  HTML code for help tab ##  
        **/
        public function q_support_form( $screen, $tab ) 
        {
            
            // is the user added correctly already ? ##
            if ( ! Q_Settings::get_q_support_user_connected() ) {
  
                // notice space ##
                echo '<div class="q_support_response updated"><p></p></div>';
                
?>
                <p>
                <?php 
                printf( 
                    __(
                        'To submit support requests, you first need to <a href="#" class="button q_support_connect q_support_space_both">%s</a> your account - <a href="%s" target="_blank">%s</a>'
                        ,Q_Support::$text_domain
                    )
                    ,esc_html( __("Connect", Q_Support::$text_domain ) ) 
                    ,esc_url( Q_SUPPORT_DOMAIN.'meta/terms-of-use/' ) // terms ##
                    ,esc_html( __("Terms of Use", Q_Support::$text_domain ) ) 
                ); ?>
                </p>
<?php
                
            // all good - let's show the form ##
            } else {
            
?>
            <p><?php _e("Complete the following form to request support for a problem you are having.", Q_Support::$text_domain); ?></p>
            <form id="q_support_request" name="q_support_request" method="post">
                
                <div class="q_support_response updated"><p></p></div>
                
                <table class="form-table">
                    <tbody>
                        
                        <tr valign="top">
                            <th scope="row"><label for="title"><?php _e("Support title", Q_Support::$text_domain) ?>:</label></th>
                            <td><input name="q_title" type="text" id="q_title" value="" class="large-text required"></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><label for="title"><?php _e("Describe the problem", Q_Support::$text_domain) ?>:</label></th>
                            <td><textarea name="q_question" rows="6" cols="30" id="q_question" class="large-text code required" style="resize: vertical;"></textarea></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><label for="url"><?php _e("Problem URL", Q_Support::$text_domain) ?>:</label></th>
                            <td><input name="q_url" type="text" id="q_url" value="<?php echo self::get_current_url(); ?>" class="large-text required"></td>
                        </tr>
                        
                        <tr valign="top">
                            <th scope="row"><label for="url"><?php _e("Category", Q_Support::$text_domain) ?>:</label></th>
                            <td>
                                <select name="q_category" id="q_category">
<?php

                                    foreach ( $this->question_categories as $category ) {
                                        
                                        echo "<option value='{$category}'>{$category}</option>";
                                        
                                    }

?>
                                </select>
                            </td>
                        </tr>
                        
                    </tbody>
                </table>
                
                <p class="q_support_submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Get Support"></p>
                
            </form>
            <hr/>
            <p>
                <?php printf( 
                    '<a href="%s" class="button q_support_space_right">%s</a>'
                    , esc_url( $this->plugin_url_settings )// link to settings ##
                    , esc_html( __("View Settings", Q_Support::$text_domain ) ) 
                );  ?>
                <?php _e("Be careful to never share your password or confidential information with anyone that you do not trust!", Q_Support::$text_domain); ?>
            </p>
<?php
            
            } // check for user connect ##

	}
        
        
        
        
        /*
         * Get the current URL ##
         * 
         * @since 0.3
         * @return      string  url of current page ##
         */
        public static function get_current_url()
        {
            
            // protocol ##
            $protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
            
            // grab the domain ##
            $domain = $_SERVER['HTTP_HOST'];
            
            // find out the path to the current file:
            $path = $_SERVER['SCRIPT_NAME'];
            
            // find out the QueryString:
            $queryString = $_SERVER['QUERY_STRING'] ? "?".$_SERVER['QUERY_STRING'] : '';

            // string it all together ##
            #$url = $protocol . $domain . $path . $queryString;
            $url = $protocol . $domain;

            // send it back ##
            return $url;
            
        }
        
        
        
        
        
        /*
        * AJAX Capture Support Form Request
        * 
        * @since        0.3
        * @return       string      HTML for help tab feedback
        **/
        public static function ajax_q_support_form() 
        {
            
            // check nonce ##
            check_ajax_referer( 'q_support_nonce', 'nonce' );
            
            // get user ##
            $user = Q_Settings::get_current_user();
            
            // validate user ##
            if ( ! $user ) { 
            
                // return it ##
                echo json_encode( __( 'User Account Error', Q_Support::$text_domain ) );

                // all AJAX calls must die!! ##
                die();
            
            } 
            
            // get user plugin settings ##
            $q_support_settings = Q_Settings::get_q_support_settings();
            
            // let's see if the required data was passed ##
            if ( 
                isset( $_POST['q_title'] ) && $_POST['q_title'] // question title ##
                && isset( $_POST['q_question'] ) && $_POST['q_question'] // question itself ##
                && isset( $_POST['q_url'] ) && $_POST['q_url'] // question URL ##
                && isset( $_POST['q_category'] ) && $_POST['q_category'] // question Category ##
            ) {
                
                // sanitize post data and add to named variables ##
                $title = Q_Plugin::sanitize( $_POST['q_title'], 'stripslashes', true );
                $question = Q_Plugin::sanitize( $_POST['q_question'], 'stripslashes', true );
                $category = Q_Plugin::sanitize( $_POST['q_category'], 'stripslashes', true );
                
                // question URL ##
                $url = Q_Plugin::sanitize( $_POST['q_url'], 'stripslashes', true );
                
                // returns absolute path to dump file on this WP install ##
                $dump = Q_Support::get_plugin_url(). '/dump/'. Q_Data::create_dump( Q_Plugin::generate_key() );
                
                // question security ##
                $agent = false; 
                $secure = false;
                if ( $q_support_settings["target"] == 'agent' && $q_support_settings["agent"] ) {
                
                    $agent = (int)Q_Plugin::sanitize( $q_support_settings["agent"], 'integer' );
                    $secure = 1; // yes - we need to secure this ##
                
                }
                
                // Save Support Request to WP ##
                $request = array(
                        'post_title'       => $title
                    ,   'post_type'        => 'q_support'
                    ,   'post_content'     => $question
                    ,   'post_status'      => 'publish'
                    ,   'post_author'      => $user->ID
                );

                // Insert the post into the database
                $post_id = wp_insert_post( $request );
                
                // save all that lovely post meta also ##
                add_post_meta( $post_id, 'q_support_url', $url ); // URL ##
                add_post_meta( $post_id, 'q_support_dump', $dump ); // dump ##
                if ( $secure ) add_post_meta( $post_id, 'q_support_secure', $secure ); // secure ##
                if ( $agent ) add_post_meta( $post_id, 'q_support_agent', $agent ); // agent ##
                add_post_meta( $post_id, 'q_support_category', $category ); // category ##
                add_post_meta( $post_id, 'q_support_settings', $q_support_settings ); // save plugin settings at time of submission ##
                add_post_meta( $post_id, 'q_support_submitted', 'false' ); // sync flag - default to false ##
                
                // now to submit the question to the API -------------------------- ##
                
                // check details ##
                if ( $user && is_user_logged_in() && $user->user_login && $user->user_email ) {
                    
                    // grab key from the user's meta data ##
                    $q_support_key = Q_Settings::get_q_support_user_key();
                    
                    // no key - throw an error ##
                    if ( ! $q_support_key ) {
                        
                        // kick back out ##
                        echo json_encode( __( 'User Account Key Error', Q_Support::$text_domain ) );

                        // all AJAX calls must die!! ##
                        die();
                        
                    }
                    
                    // store it ##
                    Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__key' => $q_support_key );
                    
                    // let's dump and check some things here ##
                    $q_dump = array(
                            'title'     => $title
                        ,   'question'  => $question
                        ,   'category'  => isset ( $category ) ? $category : __( 'General', Q_Support::$text_domain )
                        ,   'tag'       => isset( $tag ) ? $tag : __( 'General', Q_Support::$text_domain )
                        ,   'url'       => $url
                        ,   'key'       => $q_support_key
                        ,   'user'      => $user->ID
                        ,   'dump'      => $dump
                        ,   'secure'    => $secure
                        ,   'agent'     => $agent
                    );

                    // store it ##
                    if ( Q_PLUGIN_DEBUG ) { update_option( "q_support_question_wps", $q_dump ); }
                    
                    // wp_remote_post call to API ## 
                    $post_data = array( 
                        'method'        => 'set_question'
                        ,'key'          => $q_support_key
                        ,'title'        => $title
                        ,'question'     => $question
                        ,'category'     => isset ( $category ) ? $category : __( 'General', Q_Support::$text_domain )
                        ,'tag'          => isset( $tag ) ? $tag : __( 'General', Q_Support::$text_domain )
                        ,'url'          => $url // question URL ##
                        ,'dump'         => $dump // dump file ##
                        ,'secure'       => $secure // is it? ##
                        ,'agent'        => $agent // who to? ##
                    );

                    // http://kovshenin.com/2011/a-note-on-wp_http-and-http-requests-in-wordpress/
                    $headers    = array( 
                        'Authorization' => 'Basic ' . base64_encode( "$user->user_login:$user->user_pass" ) // very basic ##
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
                    
                    #echo print_r ( $result["body"] );
                    #die();
                    
                    // store it ##
                    Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__result' => var_export( $result, true ) );

                    if ( ! $result ) {

                        // store it ##
                        Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__result' => 'empty' );
                        
                        // prepare notice message ##
                        $return = __("Unknown Error");
                    
                    } else if ( is_wp_error( $result ) ) {

                        // grab error ##
                        $error_message = $result->get_error_message();
                        
                        // save to the post ##
                        add_post_meta( $post_id, 'q_support_response', $error_message ); // response ##
                        
                        // store it ##
                        Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__response' => $error_message );
                        
                        // prepare notice message ##
                        $return = $error_message;

                    } else {

                        // decode the results ##
                        $results = isset( $result["body"] ) ? json_decode( $result["body"] ) : wp_die( $result ) ;

                        // save API results ##
                        add_post_meta( $post_id, 'q_support_response', isset ( $results->response ) ? $results->response : '' ); // response ##
                        add_post_meta( $post_id, 'q_support_debug', isset( $results->debug ) ? $results->debug : '' ); // debug ##
                        add_post_meta( $post_id, 'q_support_code', isset( $result["response"] ) ? $result["response"] : '' ); // api_code ##

                        // if user was created - update the "connected" and move on ##
                        if ( isset( $results->status ) && $results->status === true ) {
                            
                            // last check - for returned key ##
                            if ( ! isset( $results->key ) ) {

                                // grab error ##
                                $error_message = $result->get_error_message();

                                // save to the post ##
                                add_post_meta( $post_id, 'q_support_response', isset ( $results->response ) ? $results->response : '' ); // response ##
                                
                                // store it ##
                                Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__response' => isset( $results->response ) ? $results->response : '' );
                                
                                // prepare notice message ##
                                $return = $error_message;

                            } else {
                                
                                // store it ##
                                Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__question_key' => isset ( $results->key ) ? $results->key : '' );
                                
                                // save the question key in post_meta ##
                                add_post_meta( $post_id, 'q_support_question_key', isset ( $results->key ) ? $results->key : '', true );

                                // update post meta flag to TRUE ##
                                update_post_meta( $post_id, 'q_support_submitted', isset ( $results->status ) ? 'true' : 'false', 'false' );

                                // get question URL ##
                                $question_url = isset( $results->url ) ? $results->url : '' ;
                                add_post_meta( $post_id, 'q_support_question_url', $question_url, true );

                                // notice // add the question link ##
                                $return = $results->response.' - '.sprintf( 
                                            __( '<a href="%s"target="_blank">%s</a>', Q_Support::$text_domain )
                                            ,esc_url( Q_SUPPORT_DOMAIN.'view/question/'.$question_url )
                                            ,esc_html( __("View the Question", Q_Support::$text_domain ) ) 
                                        );

                            }
                                
                        } else { // status came back false ##
                            
                            // store it ##
                            Q_Plugin::$store["ajax_q_support_form"] = array( 'q_support_form__status' => 'false' );
                            
                            // just send back the API response ##
                            $return = isset( $results->response ) ? $results->response : __( 'Error!' );

                        }

                    }


                } else {
                    
                    // store it ##
                    Q_Plugin::$store[] = array( 'key' => 'q_store', 'value' => array( 'q_support_form__missing_user' => 'true' ) );
                    
                    $return = __( 'Error - Missing User Data.', Q_Support::$text_domain );
                    
                }
                
            } else {
                
                // store it ##
                Q_Plugin::$store[] = array( 'key' => 'q_store', 'value' => array( 'q_support_form__no_data_passed' => 'true' ) );
                
                $return = __( 'Error - No data passed.', Q_Support::$text_domain );

            }

            // return it ##
            echo json_encode( $return );

            // all AJAX calls must die!! ##
            die();
            
	}
        
        
        
        
        
        
        
        
        
        /**
         * Add a Screen Options panel to the plugin settings page
         * 
         * @since       0.3
         * @link        http://pippinsplugins.com/add-screen-options-tab-to-your-wordpress-plugin/
         * @link        http://w-shadow.com/blog/2010/06/29/adding-stuff-to-wordpress-screen-options/
         * @return      void
         */
        public static function add_screen_options() 
        {

            global $q_support_page;

            $screen = get_current_screen();

            // get out of here if we are not on our settings page
            if( ! is_object($screen) || $screen->id != $q_support_page ) {
                return;
            }
            
            $args = array(
                'label' => __('Question Per Page', Q_Support::$text_domain),
                'default' => 10,
                'option' => 'x_per_page'
            );
            
            add_screen_option( 'per_page', $args );
                
        }
        
        
        /**
         * Add Help Tab
         * 
         * @since        0.3
         * @link         https://codex.wordpress.org/Function_Reference/add_help_tab ##
         * @return       void
         */
        public function add_help_tab() 
        {
            
            $this->help_tabs = array(
            
                // The assoc key represents the ID cannot contain spaces ##
                'support' => array(
                    'title'   => __( 'Support', Q_Support::$text_domain )
                )

            );
            
            $help_tabs = get_current_screen()->get_help_tabs();
            get_current_screen()->remove_help_tabs();

            foreach( $this->help_tabs as $id => $data ) {
                get_current_screen()->add_help_tab( array (
                        'id' => $id
                    ,   'title' => __( $data['title'], 'q_support' )
                    ,   'callback' => array( $this, 'q_support_form' )
                ));
            }
                
            if ( count( $help_tabs ) ) {
                foreach ( $help_tabs as $help_tab ) {
                    get_current_screen()->add_help_tab( $help_tab );
                }
            }
            
	}
        
        
        /**
          * Add Admin Bar menu item ##
          * 
          * @since      0.3
          * @link       http://blog.rutwick.com/add-items-anywhere-to-the-wp-3-3-admin-bar
          * @return     void
          */
        public function admin_bar_menu( $admin_bar )
        {
            
            // get user settings ##
            $q_support_settings = Q_Settings::get_q_support_settings();
            
            if ( ! $q_support_settings ) { 
                
                return false; // nothing to display ##
                
            }
            
            // raw totals, defaults and type casting ##
            $comments_new = isset( $q_support_settings["comments_new"] ) ? (int)$q_support_settings["comments_new"] : 0 ;
            $comments_old = isset( $q_support_settings["comments_old"] ) ? (int)$q_support_settings["comments_old"] : 0 ;
            $answers_new = isset( $q_support_settings["answers_new"] ) ? (int)$q_support_settings["answers_new"] : 0 ;
            $answers_old = isset( $q_support_settings["answers_old"] ) ? (int)$q_support_settings["answers_old"] : 0 ;
            
            #pr($q_support_settings);
            
            // has the viewer clicked to view the q_support page from the admin_menu - if so, archive the new poll data ##
            if ( isset( $_GET["q_clear"] ) ) {
                
                // save new totals to user meta ##
                Q_Settings::set_q_support_settings( array( 
                        'comments_old'  => $comments_new // replace old value with new value ##
                    ,   'answers_old'   => $answers_new // replace old value with new value ##
                ));
                
                // format string to print in menu ##
                $update = sprintf(
                        __(
                                'Support'
                            ,   Q_Support::$text_domain
                        )
                    );
            
            // no new notifications ##
            } else if ( 
                ( $comments_new - $comments_old === 0 && $answers_new - $answers_old === 0 )
                ||
                ( $comments_new === 0 && $answers_new === 0 )
            ) {
                
                // format string to print in menu ##
                $update = sprintf(
                        __(
                                'Support'
                            ,   Q_Support::$text_domain
                        )
                    );
                
            // not clearing totals, so subtract new from old ##
            } else {
                
                // update displayed totals ##
                (int)$comments_new = $comments_new - $comments_old;
                (int)$answers_new = $answers_new - $answers_old;
                
                // format string to print in menu ##
                $update = sprintf(
                        __(
                                'Support:<span> %s Answers %s Comments</span>'
                            ,   Q_Support::$text_domain
                        )
                        , "<span class='count count-".$answers_new."'>{$answers_new}</span>"
                        , "<span class='count count-".$comments_new."'>{$comments_new}</span>"
                    );
                
            }
            
            // The properties of the new item ##
            $args = array(
                'id'    => 'q_support'
                ,'title' => $update
                ,'href'  => admin_url( Q_Support::$plugin_url )."&q_clear"
                ,'meta'  => array(
                    'title' => __( 'Support', Q_Support::$text_domain ),
                )
                //,'parent' => 'my-account'
            );

            // This is where the magic works. ##
            $admin_bar->add_menu( $args);
            
        }
        
        
        
               
        /**
         * Register Post Type to store sent support requests and replies ##
         * 
         * @since       0.3
         * @return void
         */
        public function register_post_type() 
        {
            
            // get plugin options ##           
            $q_support_options = Q_Settings::get_q_support_options();
            
            $labels = array(
                'name'                      => __('Support Requests', Q_Support::$text_domain )
                ,'singular_name'            => __('Support Request', Q_Support::$text_domain )
                ,'add_new'                  => __('Ask a Question', Q_Support::$text_domain )
                ,'add_new_item'             => __('Add New Requests', Q_Support::$text_domain )
                ,'edit_item'                => __('Edit Support Request', Q_Support::$text_domain )
                ,'new_item'                 => __('New Support Request', Q_Support::$text_domain )
                ,'all_items'                => __('Questions', Q_Support::$text_domain )
                ,'view_item'                => __('View Support Request', Q_Support::$text_domain )
                ,'search_items'             => __('Search Requests', Q_Support::$text_domain )
                ,'not_found'                => __('<p style="margin: 10px;">No Support Requests found <a href="#" class="add-new-h2 q_support_inline">Ask a Question</a></p>', Q_Support::$text_domain )
                ,'not_found_in_trash'       => __('No Support Requests found in Trash', Q_Support::$text_domain )
                ,'parent_item_colon'        => ''
                ,'menu_name'                => __('Support', Q_Support::$text_domain )
            );

            $args = array(
                'labels'                    => $labels
                ,'public'                   => false
                ,'publicly_queryable'       => false
                ,'show_ui'                  => true
                ,'show_admin_column'        => true
                ,'show_in_menu'             => true
                ,'query_var'                => false
                ,'rewrite'                  => array( 'slug' => 'support' )
                ,'capability_type'          => 'post'
                ,'has_archive'              => true
                ,'hierarchical'             => false
                ,'supports'                 => array( 'title', 'editor', 'author', 'thumbnail', 'custom-fields' )
                ,'menu_position'            => 75 // below Tools
                ,'menu_icon'                => Q_Support::get_plugin_url( 'images/icon_wp_support_16_bw.png' ), // 16 x 16 ##
            );

            // add new CPT to WP ##
            register_post_type( 'q_support', $args );
            
            // add flag that CPT added ##
            $q_support_options["cpt"] = true;
            
            // save it all ##
            update_option( 'q_support_options', $q_support_options );
            
        }
        
        
        /**
         * manipulate post row actions ##
         * 
         * @since       0.3
         * @link        http://wordpress.stackexchange.com/questions/14973/row-actions-for-custom-post-types
         */
        public function post_row_actions( $actions, $post )
        {
            
            // check for our post type ##
            if ( $post->post_type == "q_support" ) {
                
                // unset edit ##
                unset( $actions['inline hide-if-no-js'] );
                unset( $actions['edit'] );
                
                // get the question slug ##
                $question_url = get_post_meta( $post->ID, 'q_support_question_url', true );
                
                if ( $question_url ) {
                
                    // add action to view question on wp-support.co ##
                    $actions['view'] = '<a href="'.Q_SUPPORT_DOMAIN.'view/question/'.$question_url.'" class="_blank">View</a>';
                
                }
                
            }
            
            return $actions;
            
        }
        
        
        /**
         * Manage CPT Column ##
         * 
         * @since       0.3
         * @link        https://gist.github.com/bainternet/80a8406c9e714ad6ad37
         * @return      Array   Containing all new column headers
         */
        public function manage_columns() 
        {
            
            // create an instance
            $CPT_columns = new CPT_columns('q_support');
            
            // remove columns ## 
            $CPT_columns->remove_column('date'); // date ##
            $CPT_columns->remove_column('author'); // author
            $CPT_columns->remove_column('thumbnail'); // thumbnails ##
            
            // add "url" column
            /*
            $CPT_columns->add_column('url',
                array(
                    'label'         => __('URL')
                    ,'type'         => 'text'
                )
            );
            */
            
            // add answers column
            $CPT_columns->add_column('answers',
                array(
                    'label'         => __('Answers')
                    ,'type'         => 'text'
                )
            );
            
            // add comments column
            $CPT_columns->add_column('q_comments',
                array(
                    'label'         => __('Comments')
                    ,'type'         => 'text'
                )
            );
            
            // add views column
            $CPT_columns->add_column('views',
                array(
                    'label'         => __('Views')
                    ,'type'         => 'text'
                )
            );
            
            // add views column
            $CPT_columns->add_column('votes',
                array(
                    'label'         => __('Votes')
                    ,'type'         => 'text'
                )
            );
            
            // add submitted column
            $CPT_columns->add_column('submitted',
                array(
                    'label'         => __('Status')
                    ,'type'         => 'text'
                )
            );
            
            // add asked date column
            $CPT_columns->add_column('asked',
                array(
                    'label'         => __('Asked')
                    ,'type'         => 'text'
                )
            );
            
            // add some filters ##
            #add_filter( 'cpt_columns_text_url', array( $this, 'manage_columns_url' ), 10 ,4 );
            add_filter( 'cpt_columns_text_answers', array( $this, 'manage_columns_answers' ), 10 ,4 );
            add_filter( 'cpt_columns_text_q_comments', array( $this, 'manage_columns_q_comments' ), 10 ,4 );
            add_filter( 'cpt_columns_text_views', array( $this, 'manage_columns_views' ), 10 ,4 );
            add_filter( 'cpt_columns_text_votes', array( $this, 'manage_columns_votes' ), 10 ,4 );
            add_filter( 'cpt_columns_text_submitted', array( $this, 'manage_columns_submitted' ), 10 ,4 );
            add_filter( 'cpt_columns_text_asked', array( $this, 'manage_columns_asked' ), 10 ,4 );
            
        }
        
        
        /**
         * Manage "URL" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_url( $text, $post_id, $column, $column_name )
        {
            
            $question_url = get_post_meta( $post_id, 'q_support_question_url', true );
            
            return 
                ( $question_url ) ? // do we have an URL ## 
                sprintf( 
                    '<a href="%s" class="_blank">%s</a>'
                    , esc_url( Q_SUPPORT_DOMAIN.'view/question/'.$question_url )
                    , esc_html( __("Open", Q_Support::$text_domain ) ) 
                ) : // if so return the link ##
                '-' ; // or a dead space ##
            
        }
        
        
        /**
         * Manage "Answers" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_answers( $text, $post_id, $column, $column_name )
        {
            
            return get_post_meta( $post_id, 'q_support_question_answers' ) ? get_post_meta( $post_id, 'q_support_question_answers', true ) : 0;
            
        }
        
        
        /**
         * Manage "Comments" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_q_comments( $text, $post_id, $column, $column_name )
        {
            
            return get_post_meta( $post_id, 'q_support_question_comments' ) ? get_post_meta( $post_id, 'q_support_question_comments', true ) : 0;
            
        }
        
        
        /**
         * Manage "Views" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_views( $text, $post_id, $column, $column_name )
        {
            
            return get_post_meta( $post_id, 'q_support_question_views' ) ? get_post_meta( $post_id, 'q_support_question_views', true ) : 0;
            
        }
        
        
        /**
         * Manage "Votes" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_votes( $text, $post_id, $column, $column_name )
        {
            
            // get number of votes ##
            $votes_up = get_post_meta( $post_id, 'q_support_question_votes_up' ) ? (int)get_post_meta( $post_id, 'q_support_question_votes_up', true ) : 0 ;
            $votes_down = get_post_meta( $post_id, 'q_support_question_votes_down' ) ? (int)get_post_meta( $post_id, 'q_support_question_votes_down', true ) : 0 ;
            
            // kick back formatted votes ##
            return sprintf(
                "%s %s"
                , '<span class="wps_icons thumb_up" title="'.__("Up Votes", Q_Support::$text_domain ).'">'.$votes_up.'</span>'
                , '<span class="wps_icons thumb_down" title="'.__("Down Votes", Q_Support::$text_domain ).'">'.$votes_down.'</span>'
            );
            
        }
        
        
        /**
         * Manage "Synced" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_submitted( $text, $post_id, $column, $column_name )
        {
            
            $q_support_submitted = get_post_meta( $post_id, "q_support_submitted", true );
            
            return ( $q_support_submitted ) ? '<span class="wps_icons flag_green" title="'.__("Connected to WP Support").'"></span>' : '<span class="flag_red" title="'.__("Not connected to WP Support").'"></span>' ;
            
        }
        
        
        /**
         * Manage "Asked" Column ##
         * 
         * @since       0.3
         * @return      String   HTML
         */
        public function manage_columns_asked( $text, $post_id, $column, $column_name )
        {
            
            #return $this->human_time_diff( get_post_time('G', true, $post_id ) );
            return human_time_diff( get_post_time('U', false, $post_id ), current_time('timestamp') ) .' '.__( "ago", Q_Support::$text_domain );
            // get_the_time('U'), current_time('timestamp') ) . ' ago';
            
        }
        
        
        /**
         * Manage Users columns
         * 
         * @since       0.7
         * @return      Array 
         */
        public function manage_users_columns( $column ) 
        {
            
            $column['wps_key'] = 'WPS Key';
            
            return $column;
            
        }

        
        /**
         * Print Users Column Data
         *      
         * @since       0.7
         * @return      void 
         */
        public static function manage_users_custom_column( $val, $column_name, $user_id ) 
        {
            
            // grab each users meta ##
            $q_support_settings = get_user_meta( $user_id, 'q_support_settings', true );
            
            switch ( $column_name ) {
                
                case 'wps_key' :
                    
                    return $q_support_settings && isset ( $q_support_settings["key"] ) ? $q_support_settings["key"] : '-';
                    break;

            }

        }
        
        
        /**
         * Limit the results displayed to the current user
         * 
         * @since       0.4
         * @return      void
         */
        public function limit_results()
        {
            
            // load the scripts on only the plugin admin page 
            if ( $this->get_current_post_type() == 'q_support' ) {
            
                // quick check ##
                if ( ! class_exists ( 'Q_Limit_To_Current_User' ) ) { return false; }

                // add the action to filter ##
                $q_limit_to_current_user = New Q_Limit_To_Current_User();
                add_action( 'pre_get_posts', array ( $q_limit_to_current_user, 'query_set_only_author' ) );

            }
            
        }
        
        
        /**
         * Get Current Post Type
         * 
         * @since       0.8 
         * @global      type $post
         * @global      type $typenow
         * @global      type $current_screen
         * @return      Mixed       String Post Type Name OR boolean false
         */
        public function get_current_post_type() 
        {

            global $post, $typenow, $current_screen;

            // we have a post so we can just get the post type from that
            if ( $post && $post->post_type ){
                return $post->post_type;
            }

            // check the global $typenow - set in admin.php
            elseif( $typenow ) {
                return $typenow;
            }

            // check the global $current_screen object - set in sceen.php
            elseif( $current_screen && $current_screen->post_type ) {
                return $current_screen->post_type;
            }

            //lastly check the post_type querystring
            elseif( isset( $_REQUEST['post_type'] ) ) {
                return sanitize_key( $_REQUEST['post_type'] );
            }

            // we do not know the post type!
            return null;

        }
        
        
        /**
         * Add notice to WP admin if plugin is upgraded ##
         * 
         * @since       0.3
         * @return      void
         */
        public function admin_update_notice() 
        {
            
            // get plugin options ##           
            $q_support_options = Q_Settings::get_q_support_options();
            
            // check the plugin version ##
            $version = isset( $q_support_options["version"] ) ? $q_support_options["version"] : Q_SUPPORT_VERSION;
            if ( $version != Q_SUPPORT_VERSION ) {
                
                 // update stored version number ##
                $q_support_options["version"] = Q_SUPPORT_VERSION;
                
                // store notice for later use ##
                $q_support_options["deferred_admin_notices"][] = __( "WordPress Support: Upgraded version $version to ".Q_SUPPORT_VERSION.".", Q_Support::$text_domain );
                
            }
            
            // save it all ##
            update_option( 'q_support_options', $q_support_options );
        
        }
        
        
        /**
         * Add notices to WP admin
         * 
         * @since       0.3
         */
        public function admin_notices() 
        {
            
            // get plugin name ##
            #if ( isset( $_GET['page'] ) && ( $_GET['page'] == 'q_support' ) ) {
            
                // get plugin options ##           
                $q_support_options = Q_Settings::get_q_support_options();
                
                // grab notices or return an empty array ##
                $notices = isset( $q_support_options["deferred_admin_notices"] ) ? $q_support_options["deferred_admin_notices"] : array();
                
                // Suppress "Plugin activated" notice.
                if ( isset( $q_support_options["deferred_admin_notices"] ) ) { unset( $_GET['activate'] ); }
                
                // check for notices in the the querstring with $_GET ##
                if ( isset( $_GET["settings-updated"] ) ) {

                    if ( isset ( $_GET["page"] ) && $_GET["page"] === 'settings' ) {

                        $notices[] = __("Support Settings Saved.", Q_Support::$text_domain); // add saved message ##

                    }

                }

                // do we have some notices ?
                if ( $notices ) {

                    // loop over each notice ##
                    foreach ( $notices as $notice ) {
                        #if ( !$notice ) continue; // skip over empty values ##
                        echo "<div class='updated'><p>$notice</p></div>";
                    }

                }

                // tidy up notices ##

                // delete them, so we don't do this again ##
                unset( $q_support_options["deferred_admin_notices"] );

                // save it all ##
                update_option( 'q_support_options', $q_support_options );
                
            #}
            
        }
        
        
        
        /*
         * Add settings link on plugin page ##
         * 
         * @since       0.3
         * @src         http://bavotasan.com/2009/a-settings-link-for-your-wordpress-plugins/
         * @return      Array   Links to show on plugin option
         */
        public function add_plugin_settings_link($links) 
        { 
            
            $settings_link = '<a href="'.Q_Support::$plugin_url.'">'.__( "Settings", Q_Support::$text_domain ).'</a>'; 
            array_push( $links, $settings_link ); 
            return $links; 
            
        }
 
        
        
        
        /**
         * Enqueue Plugin Scripts & Styles
         * 
         * @since       0.2
         * @return      void
         */
        public function admin_enqueue_scripts() 
        {
            
            wp_register_style( 'q-support-css', Q_Support::get_plugin_url( 'css/q-support.css' ) );
            wp_enqueue_style( 'q-support-css' );
            wp_enqueue_script( 'q-support-js', Q_Support::get_plugin_url( 'javascript/q-support.js' ), array( 'jquery' ), Q_SUPPORT_VERSION, true );
            wp_enqueue_script( 'jquery-validate-js', Q_Support::get_plugin_url( 'javascript/jquery.validate.min.js' ), array( 'jquery' ), '1.11.1', false );

            $params = array(
                'ajax_nonce' => wp_create_nonce( 'q_support_nonce' ),
            );
            wp_localize_script( 'q-support-js', 'ajax_object', $params );
            
        }
        
        
        
        /** 
         * Time Diff method to filter the_time function
         * 
         * @since           0.8.3
         * @link            http://www.jasonbobich.com/wordpress/a-better-way-to-add-time-ago-to-your-wordpress-theme/
         * @return          string
         */
        public function human_time_diff( $date ) 
        {

            if ( ! $date ) { return false; }
            
            #$date = get_post_time('G', true, $post);

            // Array of time period chunks
            $chunks = array(
                array( 60 * 60 * 24 * 365 , __( 'year', 'q-textdomain' ), __( 'years', 'q-textdomain' ) ),
                array( 60 * 60 * 24 * 30 , __( 'month', 'q-textdomain' ), __( 'months', 'q-textdomain' ) ),
                array( 60 * 60 * 24 * 7, __( 'week', 'q-textdomain' ), __( 'weeks', 'q-textdomain' ) ),
                array( 60 * 60 * 24 , __( 'day', 'q-textdomain' ), __( 'days', 'q-textdomain' ) ),
                array( 60 * 60 , __( 'hour', 'q-textdomain' ), __( 'hours', 'q-textdomain' ) ),
                array( 60 , __( 'minute', 'q-textdomain' ), __( 'minutes', 'q-textdomain' ) ),
                array( 1, __( 'second', 'q-textdomain' ), __( 'seconds', 'q-textdomain' ) )
            );

            if ( $date && !is_numeric( $date ) ) {
                #pr($date);
                $time_chunks = explode( ':', str_replace( ' ', ':', $date ) );
                #echo 'TIME: '.pr($time_chunks);
                $date_chunks = explode( '-', str_replace( ' ', '-', $date ) );
                #echo 'DATE: '.pr($date_chunks);
                $date = gmmktime( (int)$time_chunks[1], (int)$time_chunks[2], (int)$time_chunks[3], (int)$date_chunks[1], (int)$date_chunks[2], (int)$date_chunks[0] );
            }

            $current_time = current_time( 'mysql', $gmt = 0 );
            $newer_date = strtotime( $current_time );

            // Difference in seconds
            $since = $newer_date - $date;

            // Something went wrong with date calculation and we ended up with a negative date.
            if ( 0 > $since )
                return __( 'sometime', 'q-textdomain' );

            /**
             * We only want to output one chunks of time here, eg:
             * x years
             * xx months
             * so there's only one bit of calculation below:
             */

            //Step one: the first chunk
            for ( $i = 0, $j = count($chunks); $i < $j; $i++) {
                $seconds = $chunks[$i][0];

                // Finding the biggest chunk (if the chunk fits, break)
                if ( ( $count = floor($since / $seconds) ) != 0 )
                    break;
            }

            // Set output var
            $output = ( 1 == $count ) ? '1 '. $chunks[$i][1] : $count . ' ' . $chunks[$i][2];


            if ( !(int)trim($output) ){
                $output = '0 ' . __( 'seconds', 'q-textdomain' );
            }

            $output .= __(' ago', 'q-textdomain');

            return $output;
            
        }
        
        
        /**
         * Clean up before a post is deleted
         * Using "before_delete_post" allows us to still access the post_metadata before that is deleted
         * 
         * @global      string        $post_type
         * @param       integer       $postid
         * @return      void
         */
        public function before_delete_post( $postid )
        {
            
            #wp_die(get_post_type( $postid ));
            
            // Check the post type ##
            if ( get_post_type( $postid ) != 'q_support' ) return;

            // get the dump url ##
            $dump = get_post_meta( $postid, 'q_support_dump', true );
            
            // key found, so let's try and find the matching dump file ##
            if ( $dump ) {
                
                // parse the URL ##
                $parse_url = parse_url( $dump );
                
                // preg_match to get our script name ##
                preg_match( '~([^/]+)\..+$~', $parse_url['path'], $script_name );
                
                // grab the script name ##
                $script_name = ( isset ( $script_name ) && isset( $script_name[0] ) ) ? $script_name[0] : '' ;
                
                // path to zipped dump file ##
                $path = Q_SUPPORT_PATH ."dump/". $script_name;
                
                // debug ##
                if ( Q_PLUGIN_DEBUG ) {
                    
                    // grab the saved option ##
                    $q_support_before_delete_post = get_option( 'q_support_before_delete_post', array() );
                    
                    // add our new elements ##
                    $q_support_before_delete_post[$postid] = array( 'post_id' => $postid, 'path' => $path, 'script_name' => $script_name );
                    
                    // save it all ##
                    update_option( "q_support_before_delete_post", $q_support_before_delete_post );
                
                }
                    
                if ( $script_name ) {
                
                    // unlink it ##
                    @unlink( $path );

                }
                
            }
            
        }
        
        
        /**
         * Delete multiple user_meta keys
         * 
         * @param       array   $keys    Array of option names to delete
         * @since       0.3
         * @return      void
         */
        public static function delete_user_meta( $keys = null ) 
        {
    
            if ( ! $keys || ! is_array ( $keys ) ) { return; }

            foreach( $keys as $key ) {

                $meta_type  = 'user';
                $user_id    = 0; // This will be ignored, since we are deleting for all users.
                $meta_key   = $key;
                $meta_value = ''; // Also ignored. The meta will be deleted regardless of value.
                $delete_all = true;

                delete_metadata( $meta_type, $user_id, $meta_key, $meta_value, $delete_all );

            }

        }
        
        
    }

}