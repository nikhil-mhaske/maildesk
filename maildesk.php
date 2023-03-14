<?php
/*
* Plugin Name: Maildesk
* Plugin URI: https://nikhil.wisdmlabs.net
* Author: Nikhil Mhaske
* Author URI: https://nikhil.wisdmlabs.net
* Description: Posts summary on Admin Mail at End of the Day
* Text Domain: maildesk
*/

add_action( 'wp', 'schedule_daily_post_summary' );

//Made for Trial of Sending Mail Every Minute
if( ! function_exists( 'md_add_cron_schedules' ) ) :
    function md_add_cron_schedules( $schedules = array() ) {
    
    $schedules['every_minute'] = array(
    'interval' => 60,
    'display' => __( 'Every Minute', 'maildesk' ),
    );
    return $schedules;
    }
    add_filter( 'cron_schedules', 'md_add_cron_schedules' );
endif;


function schedule_daily_post_summary() {
    if ( ! wp_next_scheduled( 'send_daily_post_summary' ) ) {
        wp_schedule_event( time(), 'daily', 'send_daily_post_summary' );
    }
}

add_action( 'send_daily_post_summary', 'send_daily_post_summary_callback' );


function get_daily_post_summary() {
    $args = array(
        'date_query' => array(
            array(
                'after' => '24 hours ago',
            ),
        ),
    );
    $query = new WP_Query( $args );
    $posts = $query->posts;
    $summary = array();

    foreach ( $posts as $post ) {
        $post_data = array(
            'title' => $post->post_title,
            'url' => get_permalink( $post->ID ),
            'meta_title' => get_post_meta( $post->ID, '_yoast_wpseo_title', true ),
            'meta_description' => get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ),
            'meta_keywords' => get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true ),
        );
        array_push( $summary, $post_data );
    }

    return $summary;
}


function send_daily_post_summary_callback() {
    $to = get_option( 'admin_email' );
    $subject = 'Daily Post Summary';
    $summary = get_daily_post_summary();
    $message = '';

    foreach ( $summary as $post_data ) {
        $message .= 'Title: ' . $post_data['title'] . "\n";
        $message .= 'URL: ' . $post_data['url'] . "\n";
        $message .= 'Meta Title: ' . $post_data['meta_title'] . "\n";
        $message .= 'Meta Description: ' . $post_data['meta_description'] . "\n";
        $message .= 'Meta Keywords: ' . $post_data['meta_keywords'] . "\n";
        $message .= "\n";
    }
    $headers = 'Content-Type: text/html; charset=UTF-8';

    mail( $to , $subject, $message , $headers);
    
}

?>