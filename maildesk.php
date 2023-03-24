<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://nikhil.wisdmlabs.net
 * @since             1.0.0
 * @package           Maildesk
 *
 * @wordpress-plugin
 * Plugin Name:       MailDesk
 * Plugin URI:        https://nikhil.wisdmlabs.net
 * Description:       This is a description of the plugin.
 * Version:           1.0.0
 * Author:            Nikhil
 * Author URI:        https://nikhil.wisdmlabs.net
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       maildesk
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('MAILDESK_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-maildesk-activator.php
 */
function activate_maildesk()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-maildesk-activator.php';
    Maildesk_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-maildesk-deactivator.php
 */
function deactivate_maildesk()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-maildesk-deactivator.php';
    Maildesk_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_maildesk');
register_deactivation_hook(__FILE__, 'deactivate_maildesk');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-maildesk.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_maildesk()
{

    $plugin = new Maildesk();
    $plugin->run();
}
run_maildesk();

//Code Starts Here

add_action('wp', 'schedule_daily_post_summary');

//Made for Trial of Sending Mail Every Minute
if (!function_exists('md_add_cron_schedules')) :
    function md_add_cron_schedules($schedules = array())
    {

        $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => __('Every Minute', 'maildesk'),
        );
        return $schedules;
    }
    add_filter('cron_schedules', 'md_add_cron_schedules');
endif;


function schedule_daily_post_summary()
{
    if (!wp_next_scheduled('send_daily_post_summary')) {
        wp_schedule_event(time(), 'daily', 'send_daily_post_summary');
    }
}

add_action('send_daily_post_summary', 'send_daily_post_summary_callback');


function get_daily_post_summary()
{
    $args = array(
        'date_query' => array(
            array(
                'after' => '24 hours ago',
            ),
        ),
    );
    $query = new WP_Query($args);
    $posts = $query->posts;
    $summary = array();

    foreach ($posts as $post) {
        $post_data = array(
            'title' => $post->post_title,
            'url' => get_permalink($post->ID),
            'meta_title' => get_post_meta($post->ID, '_yoast_wpseo_title', true),
            'meta_description' => get_post_meta($post->ID, '_yoast_wpseo_metadesc', true),
            'meta_keywords' => get_post_meta($post->ID, '_yoast_wpseo_focuskw', true),
            'page_speed' => get_page_speed_score(get_permalink($post->ID)),
        );
        array_push($summary, $post_data);
    }

    return $summary;
}


function send_daily_post_summary_callback()
{
    $to = get_option('admin_email');
    $subject = 'Daily Post Summary';
    $summary = get_daily_post_summary();
    $message = '';

    foreach ($summary as $post_data) {
        $message .= 'Title: ' . $post_data['title'] . "\n";
        $message .= 'URL: ' . $post_data['url'] . "\n";
        $message .= 'Meta Title: ' . $post_data['meta_title'] . "\n";
        $message .= 'Meta Description: ' . $post_data['meta_description'] . "\n";
        $message .= 'Meta Keywords: ' . $post_data['meta_keywords'] . "\n";
        $message .= 'Page Speed Score: ' . $post_data['page_speed'] . " seconds \n";
        $message .= "\n";
    }
    $headers = array(
        'From: nikhil.mhaske@wisdmlabs.com',
        'Content-Type: text/html; charset=UTF-8'
    );

    wp_mail($to, $subject, $message, $headers);
}
//Google Page Speed
function get_page_speed_score($url)
{

    $api_key = "416ca0ef-63e4-4caa-a047-ead672ecc874"; // your api key
    $new_url = "http://www.webpagetest.org/runtest.php?url=" . $url . "&runs=1&f=xml&k=" . $api_key;
    $run_result = simplexml_load_file($new_url);
    $status = $run_result->statusCode;
    if ($status == 400) {
        $error = "API Limit Crossed!";
        return $error;
    } else {
        $test_id = $run_result->data->testId;

        $status_code = 100;

        while ($status_code != 200) {
            sleep(10);
            $xml_result = "http://www.webpagetest.org/xmlResult/" . $test_id . "/";
            $result = simplexml_load_file($xml_result);
            $status_code = $result->statusCode;
            $time = (float) ($result->data->median->firstView->loadTime) / 1000;
        };

        return $time;
    }
}
