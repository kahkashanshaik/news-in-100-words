<?php
/**
 * REST API Endpoints
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News\REST;

use Hundred_Words_News\SummaryManager;
use Hundred_Words_News\Providers\OpenAI;
use Hundred_Words_News\Admin\Settings;

/**
 * REST API class
 */
class API {

	/**
	 * Summary manager instance
	 *
	 * @var SummaryManager
	 */
	private SummaryManager $summary_manager;

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->summary_manager = new SummaryManager();
		$this->settings        = new Settings();
	}

	/**
	 * Register REST routes
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'news-in-100-words/v1',
			'/generate',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'generate_summary'),
				'permission_callback' => array($this, 'check_edit_post_permission'),
				'args'                => array(
					'post_id'  => array(
						'required' => true,
						'type'     => 'integer',
					),
					'length'   => array(
						'default' => 'medium',
						'type'    => 'string',
					),
					'language' => array(
						'default' => 'en',
						'type'    => 'string',
					),
				),
			)
		);

		register_rest_route(
			'news-in-100-words/v1',
			'/track',
			array(
				'methods'             => 'POST',
				'callback'            => array($this, 'track_interaction'),
				'permission_callback' => array($this, 'check_logged_in_permission'),
				'args'                => array(
					'post_id' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		register_rest_route(
			'news-in-100-words/v1',
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array($this, 'get_settings'),
				'permission_callback' => array($this, 'check_admin_permission'),
			)
		);
	}

	/**
	 * Generate summary endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function generate_summary(\WP_REST_Request $request) {
		// Verify nonce.
		$nonce = $request->get_header('X-WP-Nonce');
		if (! wp_verify_nonce($nonce, 'wp_rest')) {
			return new \WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
		}

		$post_id  = absint($request->get_param('post_id'));
		$length   = sanitize_text_field($request->get_param('length') ?? 'medium');
		$language = sanitize_text_field($request->get_param('language') ?? 'en');

		// Validate post_id.
		if (! $post_id || $post_id <= 0) {
			return new \WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
		}

		$post = get_post($post_id);
		if (! $post) {
			return new \WP_Error('invalid_post', 'Post not found', array('status' => 404));
		}

		// Permission check is handled in permission_callback, but validate post exists.
		// Validate length parameter.
		$valid_lengths = array('short', 'medium', 'large');
		if (! in_array($length, $valid_lengths, true)) {
			$length = 'medium';
		}

		// Get post content.
		$content = $post->post_title . "\n\n" . $post->post_content;
		$content = $this->clean_content_for_ai($content);

		// Check if content is empty after cleaning.
		if (empty(trim($content))) {
			return new \WP_Error('empty_content', 'Post content is empty or contains no text', array('status' => 400));
		}

		// Get provider settings.
		$provider_name = $this->settings->get_provider();
		$api_key       = $this->settings->get_api_key();
		$model         = $this->settings->get_model();
		$timeout       = $this->settings->get_timeout();

		if (empty($api_key)) {
			return new \WP_Error('no_api_key', 'API key not configured', array('status' => 400));
		}

		// Initialize provider.
		$provider = new OpenAI($api_key, $model, $timeout);

		// Add delay to avoid rate limits.
		$delay = $this->settings->get_api_delay();
		if ($delay > 0) {
			usleep($delay * 1000); // Convert to microseconds.
		}

		// Generate summary.
		$result = $provider->generate_summary(
			$content,
			array(
				'length'   => $length,
				'language' => $language,
			)
		);

		if (! $result['success']) {
			// Log error.
			// error_log('AI Summary Generation Error: ' . $result['error']);
			return new \WP_Error('generation_failed', $result['error'], array('status' => 500));
		}

		// Save summary.
		$this->summary_manager->save_summary($post_id, $result['summary']);
		$this->summary_manager->save_language($post_id, $language);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'summary' => $result['summary'],
			),
			200
		);
	}

	/**
	 * Track interaction endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function track_interaction(\WP_REST_Request $request) {
		// Verify nonce.
		$nonce = $request->get_header('X-WP-Nonce');
		if (! wp_verify_nonce($nonce, 'wp_rest')) {
			return new \WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
		}

		$post_id = absint($request->get_param('post_id'));

		// Validate post_id.
		if (! $post_id || $post_id <= 0) {
			return new \WP_Error('invalid_post', 'Invalid post ID', array('status' => 400));
		}

		if (! get_post($post_id)) {
			return new \WP_Error('invalid_post', 'Post not found', array('status' => 404));
		}

		$count = $this->summary_manager->increment_clicks($post_id);

		return new \WP_REST_Response(
			array(
				'success' => true,
				'count'   => $count,
			),
			200
		);
	}

	/**
	 * Get settings endpoint
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_settings(\WP_REST_Request $request) {
		// Verify nonce.
		$nonce = $request->get_header('X-WP-Nonce');
		if (! wp_verify_nonce($nonce, 'wp_rest')) {
			return new \WP_Error('invalid_nonce', 'Invalid nonce', array('status' => 403));
		}

		return new \WP_REST_Response(
			array(
				'provider'      => $this->settings->get_provider(),
				'api_key_set'   => ! empty($this->settings->get_api_key()),
				'model'         => $this->settings->get_model(),
				'timeout'       => $this->settings->get_timeout(),
				'default_length' => $this->settings->get_default_length(),
				'default_language' => $this->settings->get_default_language(),
			),
			200
		);
	}

	/**
	 * Check logged in permission
	 *
	 * @return bool
	 */
	public function check_logged_in_permission(): bool {
		return is_user_logged_in();
	}

	/**
	 * Check edit post permission
	 * Verifies that the user can edit the specific post being requested
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function check_edit_post_permission(\WP_REST_Request $request): bool {
		// User must be logged in.
		if (! is_user_logged_in()) {
			return false;
		}

		$post_id = absint($request->get_param('post_id'));

		// Validate post_id.
		if (! $post_id || $post_id <= 0) {
			return false;
		}

		// Check if user can edit this specific post.
		return current_user_can('edit_post', $post_id);
	}

	/**
	 * Check admin permission
	 *
	 * @return bool
	 */
	public function check_admin_permission(): bool {
		return current_user_can('manage_options');
	}

	/**
	 * Clean content for AI processing
	 * Removes images, iframes, and other media elements before stripping tags
	 *
	 * @param string $content Raw post content.
	 * @return string Cleaned text content.
	 */
	private function clean_content_for_ai(string $content): string {
		// Remove iframes (YouTube, Vimeo, embeds, etc.)
		$content = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $content);
		
		// Remove images (img tags)
		$content = preg_replace('/<img[^>]*>/i', '', $content);
		
		// Remove picture elements (which may contain img tags)
		$content = preg_replace('/<picture[^>]*>.*?<\/picture>/is', '', $content);
		
		// Remove video tags
		$content = preg_replace('/<video[^>]*>.*?<\/video>/is', '', $content);
		
		// Remove audio tags
		$content = preg_replace('/<audio[^>]*>.*?<\/audio>/is', '', $content);
		
		// Remove embed tags
		$content = preg_replace('/<embed[^>]*>/i', '', $content);
		
		// Remove object tags (often used for embeds)
		$content = preg_replace('/<object[^>]*>.*?<\/object>/is', '', $content);
		
		// Strip all remaining HTML tags and get plain text
		$content = wp_strip_all_tags($content);
		
		// Clean up extra whitespace
		$content = preg_replace('/\s+/', ' ', $content);
		$content = trim($content);
		
		return $content;
	}
}

