<?php

/**
 * Thunderbolt News Display
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News\Frontend;

use Hundred_Words_News\SummaryManager;
use Hundred_Words_News\Admin\Settings;

/**
 * Thunderbolt news display class
 */
class Thunderbolt
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
	 * Constructor
	 */
	public function __construct()
	{
		$this->summary_manager = new SummaryManager();
		$this->settings        = new Settings();
	}

	/**
	 * Initialize Thunderbolt
	 *
	 * @return void
	 */
	public function init(): void
	{
		// Template redirect to bypass theme wrappers (runs early, before theme loads)
		add_action('template_redirect', array($this, 'maybe_render_thunderbolt_fullpage'), 1);

		// Keep shortcode for backward compatibility
		add_shortcode('thunderbolt_news', array($this, 'render_shortcode'));
		add_action('wp', array($this, 'detect_shortcode_and_add_body_class'));
		// add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
		// Remove <p> tags that wpautop might add around shortcode
		// add_filter('the_content', array($this, 'unwrap_thunderbolt_container'), 20);
	}

	/**
	 * Render Thunderbolt shortcode
	 * 
	 * Note: This is kept for backward compatibility but typically won't be called
	 * since template_redirect intercepts pages with this shortcode.
	 *
	 * @param array  $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return string
	 */
	public function render_shortcode(array $atts = array(), string $content = ''): string
	{
		// Use the shared method to generate HTML
		$output = $this->get_thunderbolt_html($atts);
		
		// Add newlines to prevent wpautop from wrapping in <p> tags
		return "\n" . $output . "\n";
	}

	/**
	 * Render post card
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $show_share Whether to show share sidebar.
	 * @return string
	 */
	private function render_post_card(int $post_id, bool $show_share): string
	{
		$post = get_post($post_id);
		if (! $post) {
			return '<!-- Post ' . $post_id . ' not found -->';
		}

		$summary = $this->summary_manager->get_summary($post_id);
		$featured_image = get_the_post_thumbnail_url($post_id, 'large');
		$permalink = get_permalink($post_id);
		$title = get_the_title($post_id);
		$date = get_the_date('M j, Y', $post_id);
		$categories = get_the_category($post_id);
		$category = ! empty($categories) ? $categories[0]->name : '';
		$settings = $this->settings->get_all();
		$thunderbolt_settings = $settings['thunderbolt'] ?? array();
		$theme = $thunderbolt_settings['theme'] ?? 'dark';
		$readmore_bg_color = $thunderbolt_settings['readmore_bg_color'] ?? ($settings['readmore_button_color'] ?? '#dc2626');
		$readmore_text_color = $thunderbolt_settings['readmore_text_color'] ?? '#ffffff';
		$readmore_font_size = $thunderbolt_settings['readmore_font_size'] ?? '0.875rem';
		$title_font_size = $thunderbolt_settings['title_font_size'] ?? '1rem';
		$title_color = $thunderbolt_settings['title_color'] ?? '';
		$meta_font_size = $thunderbolt_settings['meta_font_size'] ?? '0.70rem';
		$meta_color = $thunderbolt_settings['meta_color'] ?? '';
		$content_font_size = $thunderbolt_settings['content_font_size'] ?? '0.75rem';
		$content_color = $thunderbolt_settings['content_color'] ?? '';
		$bullet_color = $thunderbolt_settings['bullet_color'] ?? '#3b82f6';
		$card_bg_color = $thunderbolt_settings['card_bg_color'] ?? '#252525';
		
		// For light theme, use CSS variable (set on container). For dark theme, CSS will override with !important
		$card_bg_style = '';
		if ($theme !== 'light') {
			// For dark/auto theme, set inline style (will be overridden by CSS for dark theme)
			$card_bg_style = 'style="background-color: ' . esc_attr($card_bg_color) . ';"';
		}

		// Build inline styles for colors if set (only for light theme, dark theme uses CSS overrides)
		$title_style = 'font-size: ' . esc_attr($title_font_size);
		if ($title_color && $theme === 'light') {
			$title_style .= '; color: ' . esc_attr($title_color);
		}
		
		$meta_style = 'font-size: ' . esc_attr($meta_font_size);
		if ($meta_color && $theme === 'light') {
			$meta_style .= '; color: ' . esc_attr($meta_color);
		}
		
		$content_style = '';
		if ($content_color && $theme === 'light') {
			$content_style = 'style="color: ' . esc_attr($content_color) . ';"';
		}

		// Format summary
		$summary_html = '';
		if ($summary) {
			$summary_lines = explode("\n", strip_tags(trim($summary)));
			if (count($summary_lines) > 1) {
				$summary_html = '<ul style="--bullet-color: ' . esc_attr($bullet_color) . '; font-size: ' . esc_attr($content_font_size) . ';">';
				foreach ($summary_lines as $line) {
					$line = trim($line);
					if (! empty($line)) {
						$summary_html .= '<li style="color: inherit;">' . esc_html($line) . '</li>';
					}
				}
				$summary_html .= '</ul>';
			} else {
				$summary_html = '<div style="font-size: ' . esc_attr($content_font_size) . ';">' . wp_kses_post($summary) . '</div>';
			}
		}

		// Build share button HTML
		$share_html = '';
		if ($show_share) {
			$share_url = urlencode($permalink);
			$share_title = urlencode($title);
			$share_html = '<div class="thunderbolt-card-share" data-post-url="' . esc_attr($permalink) . '" data-post-title="' . esc_attr($title) . '">
				<button class="thunderbolt-card-share-icon" aria-label="' . esc_attr__('Share', 'hundred-words-news') . '">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M15 13C14.24 13 13.56 13.3 13.04 13.76L7.91 10.35C7.96 10.24 8 10.12 8 10C8 9.88 7.96 9.76 7.91 9.65L12.96 6.24C13.5 6.72 14.21 7 15 7C16.66 7 18 5.66 18 4C18 2.34 16.66 1 15 1C13.34 1 12 2.34 12 4C12 4.12 12.04 4.24 12.09 4.35L7.04 7.76C6.5 7.28 5.79 7 5 7C3.34 7 2 8.34 2 10C2 11.66 3.34 13 5 13C5.79 13 6.5 12.72 7.04 12.24L12.16 15.65C12.11 15.76 12.08 15.88 12.08 16C12.08 17.61 13.39 18.92 15 18.92C16.61 18.92 17.92 17.61 17.92 16C17.92 14.39 16.61 13.08 15 13.08Z" fill="currentColor" />
					</svg>
				</button>
				<div class="thunderbolt-card-share-buttons">
					<a href="https://www.facebook.com/sharer/sharer.php?u=' . esc_url($share_url) . '" class="thunderbolt-share-btn thunderbolt-share-facebook" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__('Share on Facebook', 'hundred-words-news') . '">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M18.05.811q.439 0 .744.305t.305.744v16.637q0 .439-.305.744t-.744.305h-4.732v-7.221h2.415l.342-2.854h-2.757v-1.83q0-.659.293-1t1.073-.342h1.488V3.762q-.976-.098-2.171-.098-1.634 0-2.635.964t-1 2.634v2.115H7.951v2.854h2.415v7.221H1.783q-.439 0-.744-.305t-.305-.744V1.859q0-.439.305-.744T1.783.81H18.05z" />
						</svg>
						<span>Facebook</span>
					</a>
					<a href="https://twitter.com/intent/tweet?url=' . esc_url($share_url) . '&text=' . esc_url($share_title) . '" class="thunderbolt-share-btn thunderbolt-share-twitter" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__('Share on Twitter', 'hundred-words-news') . '">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M19.615 2.373a8.227 8.227 0 01-2.357.646 4.115 4.115 0 001.804-2.27 8.22 8.22 0 01-2.606.996 4.103 4.103 0 00-6.991 3.743 11.65 11.65 0 01-8.457-4.287 4.107 4.107 0 001.27 5.477A4.073 4.073 0 01.8 6.577v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 010 16.407a11.616 11.616 0 006.29 1.84c7.545 0 11.67-6.25 11.67-11.667 0-.18-.005-.362-.013-.54a8.163 8.163 0 002.007-2.093l-.047-.02z" />
						</svg>
						<span>X Twitter</span>
					</a>
					<a href="https://wa.me/?text=' . esc_url($share_url) . '" class="thunderbolt-share-btn thunderbolt-share-whatsapp" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__('Share on WhatsApp', 'hundred-words-news') . '">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0010.03 0C4.421 0 0 4.417 0 9.827c0 1.75.444 3.397 1.229 4.838L0 20l5.548-1.101a11.722 11.722 0 004.48.86h.004c5.609 0 10.03-4.417 10.03-9.828 0-2.606-1.01-5.055-2.844-6.9" />
						</svg>
						<span>WhatsApp</span>
					</a>
					<a href="https://www.reddit.com/submit?url=' . esc_url($share_url) . '&title=' . esc_url($share_title) . '" class="thunderbolt-share-btn thunderbolt-share-reddit" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__('Share on Reddit', 'hundred-words-news') . '">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M10 0C4.477 0 0 4.477 0 10s4.477 10 10 10 10-4.477 10-10S15.523 0 10 0zm5.894 6.894c-.552 0-1 .448-1 1s.448 1 1 1 1-.448 1-1-.448-1-1-1zm-11.788 0c-.552 0-1 .448-1 1s.448 1 1 1 1-.448 1-1-.448-1-1-1zm9.894 2.5c-.828 0-1.5.672-1.5 1.5 0 .552-.448 1-1 1s-1-.448-1-1c0-1.933 1.567-3.5 3.5-3.5s3.5 1.567 3.5 3.5c0 .552-.448 1-1 1s-1-.448-1-1c0-.828-.672-1.5-1.5-1.5zm-1.5 4.5c0-1.38-1.12-2.5-2.5-2.5s-2.5 1.12-2.5 2.5c0 .552-.448 1-1 1s-1-.448-1-1c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5c0 .552-.448 1-1 1s-1-.448-1-1z" />
						</svg>
						<span>Reddit</span>
					</a>
					<a href="mailto:?subject=' . esc_url($share_title) . '&body=' . esc_url($share_url) . '" class="thunderbolt-share-btn thunderbolt-share-email" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr__('Share via Email', 'hundred-words-news') . '">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
							<path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
						</svg>
						<span>Email</span>
					</a>
					<a href="#" class="thunderbolt-share-btn thunderbolt-share-link" data-url="' . esc_url($permalink) . '" aria-label="' . esc_attr__('Copy Link', 'hundred-words-news') . '">
						<svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
							<path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
						</svg>
						<span>Copy Link</span>
					</a>
				</div>
			</div>';
		}

		// Build card HTML - matching popup card structure
		$card_html = '<div class="swiper-slide" data-post-id="' . esc_attr($post_id) . '">
					  <div class="slide-content"' . ($card_bg_style ? ' ' . $card_bg_style : '') . '>';

		// Featured Image - using same structure as popup
		if ($featured_image) {
			$card_html .= '<img class="slide-image" src="' . esc_url($featured_image) . '" alt="' . esc_attr($title) . '">';
		}

		// Card Content
		$card_html .= '<div class="text-content">
						<h2 style="' . esc_attr($title_style) . ';">' . esc_html($title) . '</h2>
						<div class="hwn-popup-meta">
							<p class="meta" style="' . esc_attr($meta_style) . ';">' . esc_html($category) . ' | ' . esc_html($date) . '</p>
							<span class="hwn-popup-info-icon" aria-label="' . esc_attr__('AI Summary Information', 'hundred-words-news') . '">
								<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
									<circle cx="8" cy="8" r="7" stroke="currentColor" stroke-width="1.5" fill="none" />
									<path d="M8 11V8M8 5H8.01" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
								</svg>
								<div class="hwn-popup-tooltip">
									' . esc_html__('Summary is AI-generated, newsroom-reviewed', 'hundred-words-news') . '
								</div>
							</span>
						</div>';
		
		// Summary Body
		if ($summary_html) {
			// Apply content color if set for light theme
			if ($content_color && $theme === 'light') {
				// Add color style to ul or div
				if (strpos($summary_html, '<ul') !== false) {
					$summary_html = str_replace('<ul style="', '<ul style="color: ' . esc_attr($content_color) . '; ', $summary_html);
				} elseif (strpos($summary_html, '<div') !== false) {
					$summary_html = str_replace('<div style="', '<div style="color: ' . esc_attr($content_color) . '; ', $summary_html);
				}
			}
			$card_html .= $summary_html;
		}

		// Read More Button - matching popup structure
		$card_html .= '<a href="' . esc_url($permalink) . '" class="read-more" target="_blank" rel="noopener noreferrer" style="background-color: ' . esc_attr($readmore_bg_color) . '; color: ' . esc_attr($readmore_text_color) . '; font-size: ' . esc_attr($readmore_font_size) . ';">
					  ' . esc_html__('Read more', 'hundred-words-news') . '
					</a>
		</div>';

		$card_html .= $share_html;
		$card_html .= '</div>';
		$card_html .= '</div>';

		return $card_html;
	}

	/**
	 * Detect shortcode and add body class
	 *
	 * @return void
	 */
	public function detect_shortcode_and_add_body_class(): void
	{
		global $post;
		if (! $post || ! is_singular()) {
			return;
		}

		if (has_shortcode($post->post_content, 'thunderbolt_news')) {
			add_filter('body_class', array($this, 'add_thunderbolt_body_class'));
		}
	}

	/**
	 * Add thunderbolt body class
	 *
	 * @param array $classes Body classes.
	 * @return array
	 */
	public function add_thunderbolt_body_class(array $classes): array
	{
		$classes[] = 'thunderbolt-fullpage';
		return $classes;
	}

	/**
	 * Enqueue Thunderbolt assets
	 *
	 * @return void
	 */
	public function enqueue_assets(): void
	{
		global $post;
		if (! $post || ! is_singular()) {
			return;
		}

		if (! has_shortcode($post->post_content, 'thunderbolt_news')) {
			return;
		}

		$plugin_url = HUNDRED_WORDS_NEWS_PLUGIN_URL;

		wp_enqueue_style(
			'hundred-words-news-thunderbolt',
			$plugin_url . 'dist/css/thunderbolt.css',
			array(),
			HUNDRED_WORDS_NEWS_VERSION
		);
        
		// Enqueue Swiper CSS
		wp_enqueue_style(
			'hundred-words-news-swiper-css',
			$plugin_url . 'assets/frontend/css/swiper-bundle.min.css',
			array(),
			HUNDRED_WORDS_NEWS_VERSION
		);

		// Enqueue Swiper JS
		wp_enqueue_script(
			'hundred-words-news-swiper-js',
			$plugin_url . 'assets/frontend/js/swiper-bundle.min.js',
			array(),
			HUNDRED_WORDS_NEWS_VERSION,
			true
		);
		
		wp_enqueue_script(
			'hundred-words-news-thunderbolt',
			$plugin_url . 'dist/js/thunderbolt.js',
			array('jquery'),
			HUNDRED_WORDS_NEWS_VERSION,
			true
		);

		$settings = $this->settings->get_all();
		$thunderbolt_settings = $settings['thunderbolt'] ?? array();

		wp_localize_script(
			'hundred-words-news-thunderbolt',
			'thunderboltSettings',
			array(
				'navPosition' => $thunderbolt_settings['nav_position'] ?? 'right-center',
			)
		);
	}

	/**
	 * Check if page has thunderbolt shortcode and render full page (bypasses theme)
	 *
	 * @return void
	 */
	public function maybe_render_thunderbolt_fullpage(): void
	{
		global $post;

		// Only run on singular pages/posts
		if (! $post || ! is_singular()) {
			return;
		}

		// Check if post content has thunderbolt shortcode
		if (! has_shortcode($post->post_content, 'thunderbolt_news')) {
			return;
		}

		// Render full page HTML and exit (bypasses theme completely)
		$this->render_full_page_html();
		exit;
	}

	/**
	 * Render full page HTML with thunderbolt content directly after body
	 *
	 * @return void
	 */
	private function render_full_page_html(): void
	{
		// Get shortcode attributes from post content
		global $post, $wp_query;

		// Ensure post is set up properly for SEO plugins
		if (! $post) {
			return;
		}

		// Set up query vars for SEO plugins
		$wp_query->is_singular = true;
		$wp_query->is_single = true;
		$wp_query->queried_object = $post;
		$wp_query->queried_object_id = $post->ID;

		// Allow SEO plugins to set up their hooks
		do_action('wp', $wp_query);

		$atts = array();
		if (preg_match('/\[thunderbolt_news([^\]]*)\]/', $post->post_content, $matches)) {
			// Parse shortcode attributes
			if (! empty($matches[1])) {
				$atts_string = trim($matches[1]);
				// Simple attribute parsing
				if (preg_match_all('/(\w+)="([^"]*)"/', $atts_string, $attr_matches, PREG_SET_ORDER)) {
					foreach ($attr_matches as $attr_match) {
						$atts[$attr_match[1]] = $attr_match[2];
					}
				}
			}
		}

		// Get the thunderbolt HTML content
		$thunderbolt_html = $this->get_thunderbolt_html($atts);

		// Enqueue assets
		$plugin_url = HUNDRED_WORDS_NEWS_PLUGIN_URL;
		wp_enqueue_style(
			'hundred-words-news-thunderbolt',
			$plugin_url . 'dist/css/thunderbolt.css',
			array(),
			HUNDRED_WORDS_NEWS_VERSION
		);
        
		// Enqueue Swiper CSS
		wp_enqueue_style(
			'hundred-words-news-swiper-css',
			$plugin_url . 'assets/frontend/css/swiper-bundle.min.css',
			array(),
			HUNDRED_WORDS_NEWS_VERSION
		);

		// Enqueue Swiper JS
		wp_enqueue_script(
			'hundred-words-news-swiper-js',
			$plugin_url . 'assets/frontend/js/swiper-bundle.min.js',
			array('jquery'),
			HUNDRED_WORDS_NEWS_VERSION,
			true
		);
		
		wp_enqueue_script(
			'hundred-words-news-thunderbolt',
			$plugin_url . 'dist/js/thunderbolt.js',
			array('jquery'),
			HUNDRED_WORDS_NEWS_VERSION,
			true
		);

		$settings = $this->settings->get_all();
		$thunderbolt_settings = $settings['thunderbolt'] ?? array();
		$theme = $thunderbolt_settings['theme'] ?? 'dark';
		$bg_color = $thunderbolt_settings['bg_color'] ?? '#000000';
		
		// Build body inline styles based on theme
		$body_inline_styles = '';
		if ($theme === 'light') {
			$body_inline_styles = 'style="background-color: ' . esc_attr($bg_color) . ' !important;"';
		} elseif ($theme === 'dark') {
			$body_inline_styles = 'style="background-color: #000000 !important;"';
		} else {
			// Auto theme - will be handled by CSS media queries
			$body_inline_styles = 'style="background-color: ' . esc_attr($bg_color) . ';"';
		}
		
		wp_localize_script(
			'hundred-words-news-thunderbolt',
			'thunderboltSettings',
			array(
				'navPosition' => $thunderbolt_settings['nav_position'] ?? 'right-center',
				'theme' => $theme,
			)
		);

		// Render minimal HTML - let SEO plugins handle title and meta tags
	?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>

		<head>
			<meta charset="<?php bloginfo('charset'); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php
			// Let SEO plugins output their title and meta tags via wp_head()
			// Don't output a hardcoded title - SEO plugins will handle it
			wp_head();
			?>
			<?php
			// Allow SEO plugins and other plugins to add additional head content
			// do_action('hundred_words_news_thunderbolt_head');
			?>
		</head>

		<body <?php body_class('thunderbolt-fullpage thunderbolt-theme-' . esc_attr($theme)); ?> <?php echo $body_inline_styles; ?>>
			<?php
			// Allow SEO plugins to add body content (e.g., schema markup, analytics)
			// do_action('hundred_words_news_thunderbolt_before_content');

			// Output thunderbolt content
			echo $thunderbolt_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			// Allow SEO plugins to add content after thunderbolt
			// do_action('hundred_words_news_thunderbolt_after_content');

			// wp_footer includes SEO plugin scripts, analytics, etc.
			wp_footer();
			?>
		</body>

		</html>
	<?php
		// Flush output
		ob_end_flush();
	}

	/**
	 * Get thunderbolt HTML content (extracted logic from render_shortcode)
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	private function get_thunderbolt_html(array $atts = array()): string
	{
		// Parse attributes.
		$atts = shortcode_atts(
			array(
				'posts'    => '10',
				'post_type' => 'post',
				'orderby'  => 'date',
				'order'    => 'DESC',
			),
			$atts,
			'thunderbolt_news'
		);

		$posts_per_page = absint($atts['posts']);
		$post_type      = sanitize_text_field($atts['post_type']);
		$orderby        = sanitize_text_field($atts['orderby']);
		$order          = strtoupper(sanitize_text_field($atts['order']));

		// Validate order.
		if (! in_array($order, array('ASC', 'DESC'), true)) {
			$order = 'DESC';
		}

		// Query posts with thunderbolt meta.
		$query_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $posts_per_page,
			'orderby'        => $orderby,
			'order'          => $order,
			'meta_query'     => array(
				array(
					'key'   => '_hundred_words_news_thunderbolt_news',
					'value' => '1',
					'compare' => '=',
				),
			),
		);

		$thunderbolt_posts = new \WP_Query($query_args);
		$post_count = $thunderbolt_posts->post_count;

		if (! $thunderbolt_posts->have_posts()) {
			return '<div class="slider-container no-posts-found" style="background-color: #000000; padding: 2rem; color: white; min-height: 100vh;">
				<p>' . esc_html__('No thunderbolt news found.', 'hundred-words-news') . '</p>
				<p>Debug: Query returned ' . $post_count . ' posts.</p>
			</div>';
		}

		// Get settings.
		$settings = $this->settings->get_all();
		$thunderbolt_settings = $settings['thunderbolt'] ?? array();
		$logo_url = $thunderbolt_settings['logo_url'] ?? '';
		$bg_color = $thunderbolt_settings['bg_color'] ?? '#000000';
		$nav_position = $thunderbolt_settings['nav_position'] ?? 'right-center';
		$nav_color = $thunderbolt_settings['nav_color'] ?? '';
		$nav_bg_color = $thunderbolt_settings['nav_bg_color'] ?? '';
		$nav_font_size = $thunderbolt_settings['nav_font_size'] ?? '20px';
		$show_share = isset($thunderbolt_settings['show_share']) ? (bool) $thunderbolt_settings['show_share'] : true;
		$theme = $thunderbolt_settings['theme'] ?? 'dark';
		$title_font_size = $thunderbolt_settings['title_font_size'] ?? '1rem';
		$title_color = $thunderbolt_settings['title_color'] ?? '';
		$meta_font_size = $thunderbolt_settings['meta_font_size'] ?? '0.70rem';
		$meta_color = $thunderbolt_settings['meta_color'] ?? '';
		$content_font_size = $thunderbolt_settings['content_font_size'] ?? '0.75rem';
		$content_color = $thunderbolt_settings['content_color'] ?? '';
		$bullet_color = $thunderbolt_settings['bullet_color'] ?? '#3b82f6';
		$card_bg_color = $thunderbolt_settings['card_bg_color'] ?? '#252525';
		$readmore_bg_color = $thunderbolt_settings['readmore_bg_color'] ?? '#dc2626';
		$readmore_text_color = $thunderbolt_settings['readmore_text_color'] ?? '#ffffff';
		$readmore_font_size = $thunderbolt_settings['readmore_font_size'] ?? '0.875rem';

		// Build cards HTML first
		$cards_html = '';
		$card_count = 0;
		while ($thunderbolt_posts->have_posts()) {
			$thunderbolt_posts->the_post();
			$post_id = get_the_ID();
			if ($post_id) {
				$card_output = $this->render_post_card($post_id, $show_share);
				if (! empty($card_output)) {
					$cards_html .= $card_output;
					$card_count++;
				}
			}
		}
		wp_reset_postdata();

		// Debug: If no cards were built, return debug info
		if (empty($cards_html)) {
			return '<div class="slider-container" style="background-color: #000000; padding: 2rem; color: white; min-height: 100vh;">
				<p><strong>Debug Info:</strong></p>
				<p>Posts found: ' . $post_count . '</p>
				<p>Cards built: ' . $card_count . '</p>
				<p>Cards HTML length: ' . strlen($cards_html) . '</p>
			</div>';
		}

		// Determine theme class
		$theme_class = 'thunderbolt-theme-' . esc_attr($theme);
		
		// Build inline styles based on theme
		$inline_styles = '';
		$readmore_bg_color = $thunderbolt_settings['readmore_bg_color'] ?? '#dc2626';
		$readmore_text_color = $thunderbolt_settings['readmore_text_color'] ?? '#ffffff';
		if ($theme === 'light') {
			// Light theme: use user-selected colors via CSS variables
			$nav_color_value = $nav_color ? $nav_color : '#1a1a1a';
			$css_vars = '--thunderbolt-bg-color: ' . esc_attr($bg_color) . '; --thunderbolt-card-bg: ' . esc_attr($card_bg_color) . '; --thunderbolt-text-color: #1a1a1a; --thunderbolt-meta-color: #666; --thunderbolt-nav-color: ' . esc_attr($nav_color_value) . '; --readmore-bg-color: ' . esc_attr($readmore_bg_color) . '; --readmore-text-color: ' . esc_attr($readmore_text_color);
			// Add color CSS variables if set
			if ($title_color) {
				$css_vars .= '; --thunderbolt-title-color: ' . esc_attr($title_color);
			}
			if ($meta_color) {
				$css_vars .= '; --thunderbolt-meta-color: ' . esc_attr($meta_color);
			}
			if ($content_color) {
				$css_vars .= '; --thunderbolt-content-color: ' . esc_attr($content_color);
			}
			// Add navigation CSS variables
			if ($nav_bg_color) {
				$css_vars .= '; --thunderbolt-nav-bg-color: ' . esc_attr($nav_bg_color);
			}
			$css_vars .= '; --thunderbolt-nav-font-size: ' . esc_attr($nav_font_size);
			$inline_styles = 'style="' . $css_vars . '; background-color: ' . esc_attr($bg_color) . ';"';
		} elseif ($theme === 'dark') {
			// Dark theme: force black background (CSS will override colors)
			$css_vars = '';
			// Add navigation CSS variables even for dark theme
			if ($nav_color) {
				$css_vars = '--thunderbolt-nav-color: ' . esc_attr($nav_color) . ';';
			}
			if ($nav_bg_color) {
				$css_vars .= ($css_vars ? ' ' : '') . '--thunderbolt-nav-bg-color: ' . esc_attr($nav_bg_color) . ';';
			}
			$css_vars .= ($css_vars ? ' ' : '') . '--thunderbolt-nav-font-size: ' . esc_attr($nav_font_size) . ';';
			$inline_styles = 'style="' . $css_vars . ' background-color: #000000 !important;"';
		} else {
			// Auto theme: use user-selected colors (will be overridden by media queries for dark mode)
			$css_vars = '--thunderbolt-bg-color: ' . esc_attr($bg_color) . '; --thunderbolt-card-bg: ' . esc_attr($card_bg_color) . '; --readmore-bg-color: ' . esc_attr($readmore_bg_color) . '; --readmore-text-color: ' . esc_attr($readmore_text_color);
			// Add color CSS variables if set
			if ($title_color) {
				$css_vars .= '; --thunderbolt-title-color: ' . esc_attr($title_color);
			}
			if ($meta_color) {
				$css_vars .= '; --thunderbolt-meta-color: ' . esc_attr($meta_color);
			}
			if ($content_color) {
				$css_vars .= '; --thunderbolt-content-color: ' . esc_attr($content_color);
			}
			// Add navigation CSS variables
			if ($nav_color) {
				$css_vars .= '; --thunderbolt-nav-color: ' . esc_attr($nav_color);
			}
			if ($nav_bg_color) {
				$css_vars .= '; --thunderbolt-nav-bg-color: ' . esc_attr($nav_bg_color);
			}
			$css_vars .= '; --thunderbolt-nav-font-size: ' . esc_attr($nav_font_size);
			$inline_styles = 'style="' . $css_vars . '; background-color: ' . esc_attr($bg_color) . ';"';
		}
		
		// Build the complete output with dynamic background color
		$output = '<div class="slider-container ' . $theme_class . '" ' . $inline_styles . '>';
		
		// Add logo at top left if URL is provided
		if ($logo_url) {
			$home_url = home_url('/');
			$output .= '<a href="' . esc_url($home_url) . '" class="thunderbolt-logo" aria-label="' . esc_attr__('Home', 'hundred-words-news') . '">';
			$output .= '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr(get_bloginfo('name')) . '" class="thunderbolt-logo-img">';
			$output .= '</a>';
		}
		
		$output .= '<div class="swiper mySwiper swiper-nav-' . esc_attr($nav_position) . '">';
		$output .= '<div class="swiper-wrapper">';
		$output .= $cards_html;
		$output .= '</div>';
		$output .= '<div class="swiper-button-next"></div>';
		$output .= '<div class="swiper-button-prev"></div>';
		$output .= '<div class="swiper-pagination"></div>';
		$output .= '</div>';
		$output .= '</div>';

		return $output;
	}

	/**
	 * Remove <p> tags that wpautop wraps around slider-container
	 *
	 * @param string $content The content.
	 * @return string
	 */
	public function unwrap_thunderbolt_container(string $content): string
	{
		// Remove <p> tags that wrap slider-container
		$content = preg_replace(
			'/<p[^>]*>\s*(<div[^>]*class="slider-container"[^>]*>)/',
			'$1',
			$content
		);
		$content = preg_replace(
			'/(<\/div>\s*<\/div>)\s*<\/p>/',
			'$1',
			$content
		);
		return $content;
	}
}
