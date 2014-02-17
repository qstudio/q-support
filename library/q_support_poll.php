<?php

/**
 * Set-up and run Cron tasks
 * 
 * @link        http://wordpress.stackexchange.com/questions/49154/hide-other-users-posts-in-admin-panel
 * @since       0.4
 */


/**
* Poll WP Support to get latest data for questions
* 
* @since        0.4
* @return       void
*/
function q_support_poll()
{

    // get_option "q_support_options" - default to an empty array ##
    $q_support_options = get_option( 'q_support_options', array() );

    // get API URL 
    $q_support_api_url = isset( $q_support_options["api_url"] ) ? $q_support_options["api_url"] : false ;
    
    // get API timeout ##
    $q_support_api_timeout = isset( $q_support_options["api_timeout"] ) ? $q_support_options["api_timeout"] : 20 ;
    
    // include Q_Support
    if ( ! $q_support_api_url ) {
        
        // feedback ##
        update_option( 'q_support_poll', __('API Unavailable', 'q-support') );
        
        // kick out ##
        return false;
        
    }
    
    // grab all support requests ##
    $args = array( 
            'posts_per_page'    => -1  // @note - might need to break this into batches if the number is VERY large
        ,   'post_type'         => 'q_support' 
        ,   'fields'            => 'ids', // Only get post IDs
    );

    $questions = get_posts( $args );
    
    // store it ##
    #Q_Plugin::$store['q_support_poll']['questions']["post_ids"] = var_export ( $questions, true );
    
    // check if get_posts worked ##
    if ( is_wp_error ( $questions ) || ! $questions || ! is_array ( $questions ) ) {
        
        // store it ##
        #Q_Plugin::$store['q_support_poll']["no_questions_to_poll"] = true;
        
        // kick out ##
        return false;
        
    }
    
    // get each question key ##
    $question_keys = array();
    foreach( $questions as $question ) {
        
        // get the question key ##
        $question_keys[$question] = get_post_meta( $question, 'q_support_question_key' ) ? get_post_meta( $question, 'q_support_question_key', true ) : null;
        
    }
    
    // store it ##
    #Q_Plugin::$store['q_support_poll']["wps_questions"] = var_export ( $question_keys, true );
    
    // check if the array of question IDs is empty - array_filter without args drops all null, '', 0 values... ##
    if ( empty ( $question_keys ) ) {
        
        // store it ##
        #Q_Plugin::$store['q_support_poll']["no_wps_questions"] = true;
        
        // kick out ##
        return false;
        
    }
    
    // wp_remote_post call to API ## 
    $post_data = array( 
        'method'        => 'poll' // poll only returns publicly available data - views, votes, comments and answers ##
        ,'key'          => 'poll_'.str_replace( ".", "_", Q_SUPPORT_VERSION ) // versioning key ##
        ,'keys'         => implode( ",", $question_keys ) // keys as string of integers divided by commas ##
    );

    // Authentication ##
    $headers    = array( 
        'Authorization' => 'Basic ' . base64_encode( $post_data["key"] ) // VERY basic ##
    );
    
    // store it ##
    #Q_Plugin::$store['q_support_poll']["post_data"] = var_export ( $post_data, true );
    
    // do it ! ##
    $result     = wp_remote_post ( 
                    $q_support_api_url
                    ,array( 
                            'headers'   => $headers
                        ,   'body'      => $post_data
                        ,   'timeout'   => $q_support_api_timeout
                    ) 
                );

    
    // store it ##
    if ( is_wp_error( $result ) ) {
        
        #Q_Plugin::$store['q_support_poll']["results"] = $result->get_error_message();
    
    } else {
        
        #Q_Plugin::$store['q_support_poll']["results"] = $result["body"];
        
    }
    
    if ( is_wp_error( $result ) ) {

        // grab error ##
        $error_message = $result->get_error_message();
        
        // store it ##
        #Q_Plugin::$store['q_support_poll']["error"] = __("API Error");
        
        // kick out ##
        return false;

    } else {
        
        // check for the body content ##
        if ( ! isset( $result["body"] ) ) {
            
            // store it ##
            #Q_Plugin::$store['q_support_poll']["error"] = __("API Results Error");
            
            // kick out ##
            return false;
            
        }
        
        // decode the results ##
        $results = json_decode( $result["body"] );

        // save API results ##
        $q_support_poll = array(
                'response'      => isset ( $results->response ) ? $results->response : '' // response ##
            ,   'debug'         => isset ( $results->debug ) ? $results->debug : '' // debug ##
            ,   'code'          => isset ( $result["response"] ) ? $result["response"] : '' // api_code ##
        );

        // if API returned a good status - move on ##
        if ( isset ( $results->status ) && $results->status === true ) {
            
            // validate that an array of questions were returned ##
            if ( ! $results->response ) {
                
                // store it ##
                #Q_Plugin::$store['q_support_poll']["error"] = __("API Response Error");
                
                // kick out ##
                return false;
                
            }
            
            // count poll entries ##
            $polls = 0;
            $poll = array();
            
            // loop over each question key and update it's post meta ##
            foreach( $results->response as $key => $question ) {
                
                // did the poll return the question key ? ##
                if ( ! isset( $question->key ) ) { 
                    
                    // store it ##
                    #Q_Plugin::$store['q_support_poll']["no_key"] = $polls;
                    
                    // skip this key ##
                    continue; 
                    
                } 
                
                // find the post_id from it's key ##
                $args = array( 
                        'posts_per_page'    => 1 // one post
                    ,   'post_type'         => 'q_support' // q_support post type ##
                    ,   'meta_query'        => array(
                            array(
                                    'key'   => 'q_support_question_key'
                                ,   'value' => $question->key
                            )
                    )
                    ,   'fields'            => 'ids', // only the ids field
                );

                // get posts ##
                $post_id = get_posts( $args );
                
                // we only need the first array key ##
                $post_id = $post_id[0];
                
                #Q_Plugin::$store['q_support_poll'][$post_id]["post_id"] = $post_id;
                
                // grab the post author ID ##
                $post_author_id = get_post_field( 'post_author', $post_id ) ? get_post_field( 'post_author', $post_id ) : 0 ;
                
                #Q_Plugin::$store['q_support_poll'][$post_id]["post_author_id"] = $post_author_id;
                
                // if this user's array is empty - declare a new array and add our two keys ##
                if ( ! isset( $poll[$post_author_id] ) || ! is_array( $poll[$post_author_id] ) ) {

                    $poll[$post_author_id] = array(
                            'answers_new'   =>  0
                        ,   'comments_new'  =>  0
                    );

                }
                
                // store it ##
                #Q_Plugin::$store['q_support_poll']["poll"][$post_author_id] = var_export( $poll[$post_author_id], true );
                
                // no post id ? ##
                if ( ! $post_id ) { continue; } // skip this row ##
                
                // update question_url - if isset ##
                if ( isset ( $question->post_answers ) ) {
                    
                    update_post_meta( $post_id, 'q_support_question_answers', (int)$question->post_answers );

                    // add to counter ##
                    $poll[$post_author_id]["answers_new"] += (int)$question->post_answers;
                    
                }
                
                // update question_comments - if isset ##
                if ( isset ( $question->post_comments ) ) {
                    
                    update_post_meta( $post_id, 'q_support_question_comments', (int)$question->post_comments );
                    
                    // add to counter ##
                    $poll[$post_author_id]["comments_new"] += (int)$question->post_comments;

                }
                
                // update question_votes - if isset ##
                if ( isset ( $question->post_votes ) ) {
                    
                    update_post_meta( $post_id, 'q_support_question_votes_up', (int)$question->post_votes->up );
                    update_post_meta( $post_id, 'q_support_question_votes_down', (int)$question->post_votes->down );

                }
                
                // update views - if isset ##
                if ( isset ( $question->post_views ) ) {
                    
                    update_post_meta( $post_id, 'q_support_question_views', (int)$question->post_views );

                }
                
                // update question_slug - if isset ##
                if ( isset ( $question->post_slug ) ) {
                    
                    update_post_meta( $post_id, 'q_support_question_url', $question->post_slug );

                }
                
                // iterate ##
                $polls ++;
                
            }
                
            // store it ##
            #Q_Plugin::$store['q_support_poll']["status"] = true;
            #Q_Plugin::$store['q_support_poll']["poll"] = var_export( $poll, true );
                
            // let's update the stored answer and comments number for each user ----- ##
            
            // get all users ##
            $args = array(
                    //'role'      => 'supportee'
                   'fields'    => array ( 'ID' )
            );
            $supportees = get_users( $args );
            
            // no test passed !! ##
            if ( ! $supportees ) {
                
                // store it ##
                #Q_Plugin::$store['q_support_poll']["supportees"] = false;
                
            // all good ##
            } else {
           
                // store it ##
                #Q_Plugin::$store['q_support_poll']["supportees"] = var_export( $supportees, true );
                
                // loop over all supportees and update their meta data with recent comment and answer counts ##
                foreach( $supportees as $supportee ) {
                    
                    // store it ##
                    #Q_Plugin::$store['q_support_poll']["supportee->ID"] = $supportee->ID;
                    
                    // check if we have stored values for this user ##
                    if ( isset ( $poll[$supportee->ID] ) ) {
                        
                        // store it ##
                        #Q_Plugin::$store['q_support_poll']["supportee->ID_isset"][$supportee->ID] = true;
                        
                        // get the user's settings ##
                        $q_support_settings = get_user_meta( (int)$supportee->ID, 'q_support_settings', true );
                        
                        // check settings found and loaded ##
                        if ( $q_support_settings && ! empty( $q_support_settings ) ) {
                            
                            $q_support_settings_merge = array_merge( $q_support_settings, $poll[$supportee->ID] );
                            
                            // store it ##
                            Q_Plugin::$store['q_support_poll']["poll_dump"][$supportee->ID] = var_export( $poll[$supportee->ID], true );
                            Q_Plugin::$store['q_support_poll'][$supportee->ID]["q_support_settings"] = var_export( $q_support_settings, true );
                            Q_Plugin::$store['q_support_poll'][$supportee->ID]["q_support_settings_merge"] = var_export( $q_support_settings_merge, true );
                            
                            // update user meta ##
                            update_user_meta( (int)$supportee->ID, 'q_support_settings', $q_support_settings_merge );
                            
                        }
                        
                    }
                    
                }
                
            }
            
        } else { // status came back false ##
            
            // store it ##
            Q_Plugin::$store['q_support_poll']["status"] = false;
            
            // kick out ##
            return false;

        }

    }
        
}