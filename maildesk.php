<?php
/*
* Plugin Name: Maildesk
* Plugin URI: https://nikhil.wisdmlabs.net
* Author: Nikhil Mhaske
* Author URI: https://nikhil.wisdmlabs.net
* Description: Posts summary on Admin Mail at End of the Day
*/


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
        );
        array_push( $summary, $post_data );
    }

    return $summary;
}


function send_daily_post_summary() {
    $to = get_option( 'admin_email' );
    $subject = 'Daily Post Summary';
    $summary = get_daily_post_summary();
    $message = '';
    foreach ( $summary as $post_data ) {
        $message .= 'Title: ' . $post_data['title'] . "\n";
        $message .= 'URL: ' . $post_data['url'] . "\n";
    }
    wp_mail( $to, $subject, $message );
}

add_action( 'publish_post', 'send_daily_post_summary' );

?>