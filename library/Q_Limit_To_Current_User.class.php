<?php

/**
 * Limit the posts viewable to the current user
 * 
 * @link        http://wordpress.stackexchange.com/questions/49154/hide-other-users-posts-in-admin-panel
 * @since       0.4
 */

if ( ! class_exists( 'Q_Limit_To_Current_User' ) ) {
    
    class Q_Limit_To_Current_User {
        
        // Show only posts and media related to logged in author
        function query_set_only_author( $wp_query ) 
        {
            
            global $current_user;

            if( is_admin() && ! current_user_can( Q_SUPPORT_CAPABILITY_ADMIN ) ) {
                
                $wp_query->set( 'author', $current_user->ID );
                add_filter('views_edit-post', array ( $this, 'fix_post_counts' ) );
                add_filter('views_upload', array ( $this, 'fix_media_counts' ) );
                
            }
            
        }

        // Fix post counts
        function fix_post_counts($views) 
        {
            
            global $current_user, $wp_query;
            unset($views['mine']);
            $types = array(
                array( 'status' =>  NULL ),
                array( 'status' => 'publish' ),
                array( 'status' => 'draft' ),
                array( 'status' => 'pending' ),
                array( 'status' => 'trash' )
            );
            foreach( $types as $type ) {
                $query = array(
                    'author'      => $current_user->ID,
                    'post_type'   => 'post',
                    'post_status' => $type['status']
                );
                $result = new WP_Query($query);
                if( $type['status'] == NULL ):
                    $class = ($wp_query->query_vars['post_status'] == NULL) ? ' class="current"' : '';
                    $views['all'] = sprintf(__('<a href="%s"'. $class .'>All <span class="count">(%d)</span></a>', 'all'),
                        admin_url('edit.php?post_type=post'),
                        $result->found_posts);
                elseif( $type['status'] == 'publish' ):
                    $class = ($wp_query->query_vars['post_status'] == 'publish') ? ' class="current"' : '';
                    $views['publish'] = sprintf(__('<a href="%s"'. $class .'>Published <span class="count">(%d)</span></a>', 'publish'),
                        admin_url('edit.php?post_status=publish&post_type=post'),
                        $result->found_posts);
                elseif( $type['status'] == 'draft' ):
                    $class = ($wp_query->query_vars['post_status'] == 'draft') ? ' class="current"' : '';
                    $views['draft'] = sprintf(__('<a href="%s"'. $class .'>Draft'. ((sizeof($result->posts) > 1) ? "s" : "") .' <span class="count">(%d)</span></a>', 'draft'),
                        admin_url('edit.php?post_status=draft&post_type=post'),
                        $result->found_posts);
                elseif( $type['status'] == 'pending' ):
                    $class = ($wp_query->query_vars['post_status'] == 'pending') ? ' class="current"' : '';
                    $views['pending'] = sprintf(__('<a href="%s"'. $class .'>Pending <span class="count">(%d)</span></a>', 'pending'),
                        admin_url('edit.php?post_status=pending&post_type=post'),
                        $result->found_posts);
                elseif( $type['status'] == 'trash' ):
                    $class = ($wp_query->query_vars['post_status'] == 'trash') ? ' class="current"' : '';
                    $views['trash'] = sprintf(__('<a href="%s"'. $class .'>Trash <span class="count">(%d)</span></a>', 'trash'),
                        admin_url('edit.php?post_status=trash&post_type=post'),
                        $result->found_posts);
                endif;
            }
            return $views;
        }

        
        // Fix media counts
        function fix_media_counts($views) 
        {
            
            global $wpdb, $current_user, $post_mime_types, $avail_post_mime_types;
            
            $views = array();
            
            $count = $wpdb->get_results( "
                SELECT post_mime_type, COUNT( * ) AS num_posts 
                FROM $wpdb->posts 
                WHERE post_type = 'attachment' 
                AND post_author = $current_user->ID 
                AND post_status != 'trash' 
                GROUP BY post_mime_type
            ", ARRAY_A );
            
            foreach( $count as $row )
                $_num_posts[$row['post_mime_type']] = $row['num_posts'];
            
            $_total_posts = array_sum($_num_posts);
            
            $detached = isset( $_REQUEST['detached'] ) || isset( $_REQUEST['find_detached'] );
            
            if ( !isset( $total_orphans ) )
                $total_orphans = $wpdb->get_var("
                    SELECT COUNT( * ) 
                    FROM $wpdb->posts 
                    WHERE post_type = 'attachment' 
                    AND post_author = $current_user->ID 
                    AND post_status != 'trash' 
                    AND post_parent < 1
                ");
            
            $matches = wp_match_mime_types(array_keys($post_mime_types), array_keys($_num_posts));
            foreach ( $matches as $type => $reals )
                foreach ( $reals as $real )
                    $num_posts[$type] = ( isset( $num_posts[$type] ) ) ? $num_posts[$type] + $_num_posts[$real] : $_num_posts[$real];
            $class = ( empty($_GET['post_mime_type']) && !$detached && !isset($_GET['status']) ) ? ' class="current"' : '';
            $views['all'] = "<a href='upload.php'$class>" . sprintf( __('All <span class="count">(%s)</span>', 'uploaded files' ), number_format_i18n( $_total_posts )) . '</a>';
            foreach ( $post_mime_types as $mime_type => $label ) {
                $class = '';
                if ( !wp_match_mime_types($mime_type, $avail_post_mime_types) )
                    continue;
                if ( !empty($_GET['post_mime_type']) && wp_match_mime_types($mime_type, $_GET['post_mime_type']) )
                    $class = ' class="current"';
                if ( !empty( $num_posts[$mime_type] ) )
                    $views[$mime_type] = "<a href='upload.php?post_mime_type=$mime_type'$class>" . sprintf( translate_nooped_plural( $label[2], $num_posts[$mime_type] ), $num_posts[$mime_type] ) . '</a>';
            }
            $views['detached'] = '<a href="upload.php?detached=1"' . ( $detached ? ' class="current"' : '' ) . '>' . sprintf( __( 'Unattached <span class="count">(%s)</span>', 'detached files' ), $total_orphans ) . '</a>';
            return $views;
            
        }
        
    }
    
}

