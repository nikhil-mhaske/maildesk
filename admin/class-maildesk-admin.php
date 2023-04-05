<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://nikhil.wisdmlabs.net
 * @since      1.0.0
 *
 * @package    Maildesk
 * @subpackage Maildesk/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Maildesk
 * @subpackage Maildesk/admin
 * @author     Nikhil <nikhil.mhaske@wisdmlabs.com>
 */
class Maildesk_Admin
{

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Maildesk_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Maildesk_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/maildesk-admin.css', array(), $this->version, 'all');
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Maildesk_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Maildesk_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/maildesk-admin.js', array('jquery'), $this->version, false);
	}

	//Made for Trial of Sending Mail Every Minute
    public function md_add_cron_schedules($schedules = array())
    {

        $schedules['every_minute'] = array(
            'interval' => 60,
            'display' => __('Every Minute', 'maildesk'),
        );
        return $schedules;
    }
    


	public function schedule_daily_post_summary()
	{
		if (!wp_next_scheduled('send_daily_post_summary')) {
			wp_schedule_event(time(), 'daily', 'send_daily_post_summary');
		}
	}


	public function get_daily_post_summary()
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
				'page_speed' => $this->get_page_speed_score(get_permalink($post->ID)),
			);
			array_push($summary, $post_data);
		}

		return $summary;
	}


	public function send_daily_post_summary_callback()
	{
		$to = get_option('admin_email');
		$subject = 'Daily Post Summary';
		$summary = $this->get_daily_post_summary();
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

		if($message == ''){
			$message .= 'Hey admin, We do not have new posts in last 24 Hours!';
		}

		wp_mail($to, $subject, $message, $headers);
	}
	//Google Page Speed
	public function get_page_speed_score($url)
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
}
