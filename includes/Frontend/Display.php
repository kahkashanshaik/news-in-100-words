<?php

/**
 * Front-End Display
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary\Frontend;

use AI_Blog_Summary\SummaryManager;
use AI_Blog_Summary\Admin\Settings;

/**
 * Front-end display class
 */
class Display
{

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
	 * Track processed post IDs to prevent recursion
	 *
	 * @var array
	 */
	private static array $processing = array();

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->summary_manager = new SummaryManager();
		$this->settings        = new Settings();
	}

	/**
	 * Initialize front-end display
	 *
	 * @return void
	 */
	public function init(): void
	{
		add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		add_filter('the_title', array($this, 'add_icon_to_title'), 10, 2);
		add_action('wp_footer', array($this, 'render_popup'));
		
		// Don't add icon to titles on Thunderbolt pages
		add_action('wp', array($this, 'maybe_disable_icon_on_thunderbolt'));
	}

	/**
	 * Enqueue front-end assets
	 *
	 * @return void
	 */
	public function enqueue_assets(): void
	{
		$plugin_url = AI_BLOG_SUMMARY_PLUGIN_URL;

		wp_enqueue_script(
			'ai-blog-summary-frontend',
			$plugin_url . 'dist/js/frontend.js',
			array(),
			AI_BLOG_SUMMARY_VERSION,
			true
		);

		wp_enqueue_style(
			'ai-blog-summary-frontend',
			$plugin_url . 'dist/css/frontend.css',
			array(),
			AI_BLOG_SUMMARY_VERSION
		);

		$settings = $this->settings->get_all();

		wp_localize_script(
			'ai-blog-summary-frontend',
			'aiBlogSummaryFrontend',
			array(
				'apiUrl' => rest_url('ai-summary/v1/'),
				'nonce'  => wp_create_nonce('wp_rest'),
				'readmoreButtonColor' => $settings['readmore_button_color'] ?? '#dc2626',
			)
		);
	}

	/**
	 * Maybe disable icon on Thunderbolt pages
	 *
	 * @return void
	 */
	public function maybe_disable_icon_on_thunderbolt(): void
	{
		global $post;
		if ($post && has_shortcode($post->post_content, 'thunderbolt_news')) {
			remove_filter('the_title', array($this, 'add_icon_to_title'), 10);
		}
	}

	/**
	 * Add icon to post title
	 *
	 * @param string $title Post title.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function add_icon_to_title(string $title, int $post_id = 0): string
	{
		// Prevent infinite recursion
		if (isset(self::$processing[$post_id])) {
			return $title;
		}

		// Don't add icon on Thunderbolt pages
		global $post;
		if ($post && has_shortcode($post->post_content, 'thunderbolt_news')) {
			return $title;
		}

		// Only add icon in the loop or on single posts.
		if (! in_the_loop() && ! is_singular()) {
			return $title;
		}

		if (0 === $post_id) {
			$post_id = get_the_ID();
		}

		if (0 === $post_id) {
			return $title;
		}

		// Mark as processing
		self::$processing[$post_id] = true;

		// Check if post has summary and icon should be shown.
		if (
			! $this->summary_manager->has_summary($post_id) ||
			! $this->summary_manager->should_show_icon($post_id)
		) {
			unset(self::$processing[$post_id]);
			return $title;
		}

		$summary = $this->summary_manager->get_summary($post_id);
		$settings = $this->settings->get_all();
		$icon_size = $settings['icon_size'] ?? 'medium';
		$icon_color = $settings['icon_color'] ?? '#3b82f6';

		// Get post data for popup - use raw post title to avoid recursion
		$post = get_post($post_id);
		if (! $post) {
			unset(self::$processing[$post_id]);
			return $title;
		}

		// Use raw post title instead of get_the_title() to avoid filter recursion
		$post_title = $post->post_title;
		$post_permalink = get_permalink($post_id);

		// Format date from post object to avoid filter recursion
		$post_date = '';
		if (! empty($post->post_date)) {
			$post_timestamp = strtotime($post->post_date);
			$post_date = date('M j, Y', $post_timestamp);
		}

		$featured_image = get_the_post_thumbnail_url($post_id, 'large');
		$category = '';
		$categories = get_the_category($post_id);
		if (!empty($categories)) {
			$category = $categories[0]->name;
		}

		// Size classes.
		$size_classes = array(
			'small'  => 'ai-summary-icon-small',
			'medium' => 'ai-summary-icon-medium',
			'large'  => 'ai-summary-icon-large',
		);
		$size_class = $size_classes[$icon_size] ?? 'ai-summary-icon-medium';

		// Encode summary for HTML attribute (escape quotes but preserve HTML structure)
		$summary_encoded = htmlspecialchars($summary, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		
		$icon = sprintf(
			'<span class="ai-summary-icon %s" data-post-id="%d" data-summary="%s" data-title="%s" data-permalink="%s" data-date="%s" data-image="%s" data-category="%s" style="color: %s;" aria-label="%s" role="button" tabindex="0">
				⚡
			</span>',
			esc_attr($size_class),
			esc_attr($post_id),
			$summary_encoded,
			esc_attr($post_title),
			esc_url($post_permalink),
			esc_attr($post_date),
			esc_url($featured_image ? $featured_image : ''),
			esc_attr($category),
			esc_attr($icon_color),
			esc_attr__('View summary', 'ai-blog-summary')
		);

		// Unmark as processing
		unset(self::$processing[$post_id]);

		return $title . ' ' . $icon;
	}

	/**
	 * Render popup modal
	 *
	 * @return void
	 */
	public function render_popup(): void
	{
		// Don't render popup on Thunderbolt pages
		global $post;
		if ($post && has_shortcode($post->post_content, 'thunderbolt_news')) {
			return;
		}

		$settings = $this->settings->get_all();
		$popup_theme = $settings['popup_theme'] ?? 'auto';
?>
		<div id="ai-summary-popup" class="ai-summary-popup ai-summary-popup-<?php echo esc_attr($popup_theme); ?>" role="dialog" aria-labelledby="ai-summary-popup-title" aria-hidden="true">
			<div class="ai-summary-popup-overlay"></div>
			<div class="ai-summary-popup-wrapper">
				<div class="ai-summary-popup-card">
					<button class="ai-summary-popup-close" aria-label="<?php esc_attr_e('Close', 'ai-blog-summary'); ?>">&times;</button>

					<!-- Featured Image -->
					<div class="ai-summary-popup-image">
						<img src="" alt="" id="ai-summary-popup-img">
					</div>

					<!-- Card Content -->
					<div class="ai-summary-popup-card-content">
						<!-- Title -->
						<h2 id="ai-summary-popup-title" class="ai-summary-popup-title"></h2>

						<!-- Date and Info Icon -->
						<div class="ai-summary-popup-meta">
							<span class="ai-summary-popup-date"></span>
							<span class="ai-summary-popup-info-icon" aria-label="<?php esc_attr_e('AI Summary Information', 'ai-blog-summary'); ?>">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none" />
									<path d="M8 11V8M8 5H8.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
								</svg>
								<div class="ai-summary-popup-tooltip">
									<?php esc_html_e('Summary is AI-generated, newsroom-reviewed', 'ai-blog-summary'); ?>
								</div>
							</span>
						</div>

						<!-- AI Generated Summary -->
						<!-- <div class="ai-summary-popup-label"><?php esc_html_e('AI Generated News Summary', 'ai-blog-summary'); ?></div> -->
						<div class="ai-summary-popup-body"></div>

						<!-- Read More Button -->
						<div class="read-more-button-wrapper">
							<a href="#" class="ai-summary-popup-readmore" target="_blank" rel="noopener noreferrer"
								style="background-color: <?php echo esc_attr($settings['readmore_button_color'] ?? '#dc2626'); ?>;">
								<?php esc_html_e('Read more', 'ai-blog-summary'); ?>
							</a>
						</div>
					</div>

					<!-- Share Buttons Sidebar -->
					<div class="ai-summary-popup-share">
						<div class="ai-summary-popup-share-icon">
							<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
								<path d="M15 13C14.24 13 13.56 13.3 13.04 13.76L7.91 10.35C7.96 10.24 8 10.12 8 10C8 9.88 7.96 9.76 7.91 9.65L12.96 6.24C13.5 6.72 14.21 7 15 7C16.66 7 18 5.66 18 4C18 2.34 16.66 1 15 1C13.34 1 12 2.34 12 4C12 4.12 12.04 4.24 12.09 4.35L7.04 7.76C6.5 7.28 5.79 7 5 7C3.34 7 2 8.34 2 10C2 11.66 3.34 13 5 13C5.79 13 6.5 12.72 7.04 12.24L12.16 15.65C12.11 15.76 12.08 15.88 12.08 16C12.08 17.61 13.39 18.92 15 18.92C16.61 18.92 17.92 17.61 17.92 16C17.92 14.39 16.61 13.08 15 13.08Z" fill="currentColor" />
							</svg>
						</div>
						<div class="ai-summary-popup-share-buttons">
							<a href="#" class="ai-summary-share-btn ai-summary-share-facebook" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Facebook', 'ai-blog-summary'); ?>">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
									<path d="M18.05.811q.439 0 .744.305t.305.744v16.637q0 .439-.305.744t-.744.305h-4.732v-7.221h2.415l.342-2.854h-2.757v-1.83q0-.659.293-1t1.073-.342h1.488V3.762q-.976-.098-2.171-.098-1.634 0-2.635.964t-1 2.634v2.115H7.951v2.854h2.415v7.221H1.783q-.439 0-.744-.305t-.305-.744V1.859q0-.439.305-.744T1.783.81H18.05z" />
								</svg>
								<span>Facebook</span>
							</a>
							<a href="#" class="ai-summary-share-btn ai-summary-share-twitter" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Twitter', 'ai-blog-summary'); ?>">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
									<path d="M19.615 2.373a8.227 8.227 0 01-2.357.646 4.115 4.115 0 001.804-2.27 8.22 8.22 0 01-2.606.996 4.103 4.103 0 00-6.991 3.743 11.65 11.65 0 01-8.457-4.287 4.107 4.107 0 001.27 5.477A4.073 4.073 0 01.8 6.577v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84c7.545 0 11.67-6.25 11.67-11.667 0-.18-.005-.362-.013-.54a8.163 8.163 0 002.007-2.093l-.047-.02z" />
								</svg>
								<span>X Twitter</span>
							</a>
							<a href="#" class="ai-summary-share-btn ai-summary-share-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on WhatsApp', 'ai-blog-summary'); ?>">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
									<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0010.03 0C4.421 0 0 4.417 0 9.827c0 1.75.444 3.397 1.229 4.838L0 20l5.548-1.101a11.722 11.722 0 004.48.86h.004c5.609 0 10.03-4.417 10.03-9.828 0-2.606-1.01-5.055-2.844-6.9" />
								</svg>
								<span>WhatsApp</span>
							</a>
							<a href="#" class="ai-summary-share-btn ai-summary-share-reddit" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Reddit', 'ai-blog-summary'); ?>">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
									<path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm5.894 6.894c-.552 0-1 .448-1 1s.448 1 1 1 1-.448 1-1-.448-1-1-1zm-11.788 0c-.552 0-1 .448-1 1s.448 1 1 1 1-.448 1-1-.448-1-1-1zm9.894 2.5c-.828 0-1.5.672-1.5 1.5 0 .552-.448 1-1 1s-1-.448-1-1c0-1.933 1.567-3.5 3.5-3.5s3.5 1.567 3.5 3.5c0 .552-.448 1-1 1s-1-.448-1-1c0-.828-.672-1.5-1.5-1.5zm-1.5 4.5c0-1.38-1.12-2.5-2.5-2.5s-2.5 1.12-2.5 2.5c0 .552-.448 1-1 1s-1-.448-1-1c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5c0 .552-.448 1-1 1s-1-.448-1-1z" />
								</svg>
								<span>Reddit</span>
							</a>
							<a href="#" class="ai-summary-share-btn ai-summary-share-email" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share via Email', 'ai-blog-summary'); ?>">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
									<path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
									<path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
								</svg>
								<span>Email</span>
							</a>
							<a href="#" class="ai-summary-share-btn ai-summary-share-link" aria-label="<?php esc_attr_e('Copy Link', 'ai-blog-summary'); ?>">
								<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
									<path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
									<path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
								</svg>
								<span>Copy Link</span>
							</a>
						</div>
					</div>
				</div>
			</div>
	<?php
	}
}
