<?php
/**
 * Gutenberg Block Editor Integration
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary\Editors;

use AI_Blog_Summary\SummaryManager;

/**
 * Gutenberg editor integration
 */
class Gutenberg {

	/**
	 * Summary manager instance
	 *
	 * @var SummaryManager
	 */
	private SummaryManager $summary_manager;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->summary_manager = new SummaryManager();
	}

	/**
	 * Initialize Gutenberg integration
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('enqueue_block_editor_assets', array($this, 'enqueue_assets'));
		add_action('rest_insert_post', array($this, 'save_summary_from_rest'), 10, 3);
	}

	/**
	 * Enqueue block editor assets
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		$screen = get_current_screen();
		if (! $screen || ! $screen->is_block_editor()) {
			return;
		}

		$post_id = get_the_ID();

		// Enqueue admin JS.
		wp_enqueue_script(
			'ai-blog-summary-admin',
			AI_BLOG_SUMMARY_PLUGIN_URL . 'dist/js/admin.js',
			array('wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-api-fetch'),
			AI_BLOG_SUMMARY_VERSION,
			true
		);

		wp_enqueue_style(
			'ai-blog-summary-admin',
			AI_BLOG_SUMMARY_PLUGIN_URL . 'dist/css/admin.css',
			array(),
			AI_BLOG_SUMMARY_VERSION
		);

		// Localize script.
		wp_localize_script(
			'ai-blog-summary-admin',
			'aiBlogSummary',
			array(
				'apiUrl'         => rest_url('ai-summary/v1/'),
				'nonce'          => wp_create_nonce('wp_rest'),
				'postId'         => $post_id,
				'summary'        => $this->summary_manager->get_summary($post_id) ?: '',
				'showIcon'       => $this->summary_manager->should_show_icon($post_id),
				'defaultLength'  => get_option('ai_blog_summary_settings')['default_length'] ?? 'medium',
				'defaultLanguage' => get_option('ai_blog_summary_settings')['default_language'] ?? 'en',
			)
		);
	}

	/**
	 * Save summary from REST API request
	 *
	 * @param \WP_Post         $post Inserted or updated post object.
	 * @param \WP_REST_Request $request Request object.
	 * @param bool             $creating True when creating a post, false when updating.
	 * @return void
	 */
	public function save_summary_from_rest(\WP_Post $post, \WP_REST_Request $request, bool $creating): void {
		$meta = $request->get_param('meta');
		if (isset($meta['ai_post_summary'])) {
			$summary = wp_kses_post($meta['ai_post_summary']);
			if (! empty($summary)) {
				$this->summary_manager->save_summary($post->ID, $summary);
			}
		}
		if (isset($meta['ai_show_summary_icon'])) {
			$this->summary_manager->set_show_icon($post->ID, (bool) $meta['ai_show_summary_icon']);
		}
	}
}
