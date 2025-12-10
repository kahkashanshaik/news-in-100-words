<?php
/**
 * Auto Generator
 *
 * Handles automatic summary generation on post save
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News;

use Hundred_Words_News\Providers\OpenAI;
use Hundred_Words_News\Admin\Settings;

/**
 * Auto Generator class
 */
class AutoGenerator {

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
	 * Initialize auto generator
	 *
	 * @return void
	 */
	public function init(): void {
		if ($this->settings->is_auto_generate_enabled()) {
			add_action('save_post', array($this, 'maybe_generate_summary'), 10, 2);
		}
	}

	/**
	 * Maybe generate summary on post save
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function maybe_generate_summary(int $post_id, \WP_Post $post): void {
		// Skip autosaves and revisions.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		if (wp_is_post_revision($post_id)) {
			return;
		}

		// Only generate for published posts.
		if ('publish' !== $post->post_status) {
			return;
		}

		// Only generate if no summary exists.
		if ($this->summary_manager->has_summary($post_id)) {
			return;
		}

		// Get API settings.
		$api_key = $this->settings->get_api_key();
		if (empty($api_key)) {
			return;
		}

		$model   = $this->settings->get_model();
		$timeout = $this->settings->get_timeout();
		$delay   = $this->settings->get_api_delay();

		// Add delay to avoid rate limits.
		if ($delay > 0) {
			usleep($delay * 1000);
		}

		// Get post content.
		$content = $post->post_title . "\n\n" . $post->post_content;
		$content = $this->clean_content_for_ai($content);

		if (empty($content)) {
			return;
		}

		// Initialize provider.
		$provider = new OpenAI($api_key, $model, $timeout);

		// Generate summary.
		$result = $provider->generate_summary(
			$content,
			array(
				'length'   => $this->settings->get_default_length(),
				'language' => $this->settings->get_default_language(),
			)
		);
		if ($result['success']) {
			$this->summary_manager->save_summary($post_id, $result['summary']);
			$this->summary_manager->save_language($post_id, $this->settings->get_default_language());
		} else {
			// Log error.
			error_log('AI Summary Auto-Generation Error for Post ' . $post_id . ': ' . $result['error']);
		}
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
		
		// Remove shortcodes that might contain media (optional - you may want to keep some)
		// $content = strip_shortcodes($content);
		
		// Strip all remaining HTML tags and get plain text
		$content = wp_strip_all_tags($content);
		
		// Clean up extra whitespace
		$content = preg_replace('/\s+/', ' ', $content);
		$content = trim($content);
		
		return $content;
	}
}

