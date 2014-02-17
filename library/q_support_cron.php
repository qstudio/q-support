<?php

/**
 * Set-up and run Cron tasks
 * 
 * @since       0.8.4
 */

// add new schedule to cron ##
add_filter( 'cron_schedules', 'q_support_cron_add_schedule' );

// set-up the cron task ##
add_action( 'plugins_loaded', 'q_support_setup_schedule' );

// clear schedule ##
#add_action( 'plugins_loaded', 'q_support_clear_schedule' );

// hook into our cron schedule ##
add_action( 'q_support_poll', 'q_support_poll' );


/**
* Filter Cron Schedules to allow for shorter delay
* 
* @since       0.4
* @param       Array    $schedules
* @return      Array
* @link        http://codex.wordpress.org/Function_Reference/wp_get_schedules
*/
function q_support_cron_add_schedule( $schedules ) 
{

   // Adds once weekly to the existing schedules.
   $schedules['fively'] = array(
       'interval' => 300,
       'display' => __( 'Each Five Minutes' )
   );
   
   // Adds once weekly to the existing schedules.
   $schedules['onely'] = array(
       'interval' => 60,
       'display' => __( 'Each One Minute' )
   );

   return $schedules;

}


/**
 * Setup a cron task
 */
function q_support_setup_schedule()
{

   if ( ! wp_next_scheduled( 'q_support_poll' ) ) {

       wp_schedule_event( time(), 'fively', 'q_support_poll' );

   }

}


/**
 * Clear a defined task
 */
function q_support_clear_schedule()
{

   // clear poll ##
   wp_clear_scheduled_hook( 'q_support_poll' );

}
