<?php
/**
 * Classic Editor Integration
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary\Editors;

use AI_Blog_Summary\SummaryManager;

/**
 * Classic editor integration
 */
class Classic {

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
	 * Initialize classic editor integration
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('add_meta_boxes', array($this, 'add_meta_box'));
		add_action('save_post', array($this, 'save_meta_box'), 10, 2);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
	}

	/**
	 * Add meta box
	 *
	 * @return void
	 */
	public function add_meta_box(): void {
		$screen = get_current_screen();
		if ($screen && $screen->post_type !== 'post') {
			return;
		}

		$post_types = $this->get_supported_post_types();
		foreach ($post_types as $post_type) {
			add_meta_box(
				'ai_blog_summary_meta_box',
				__('AI Blog Summary', 'ai-blog-summary'),
				array($this, 'render_meta_box'),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render meta box
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box(\WP_Post $post): void {
		wp_nonce_field('ai_blog_summary_meta_box', 'ai_blog_summary_nonce');

		$summary  = $this->summary_manager->get_summary($post->ID);
		$show_icon = $this->summary_manager->should_show_icon($post->ID);
		$thunderbolt_news = get_post_meta($post->ID, '_ai_thunderbolt_news', true) === '1';
		$settings = get_option('ai_blog_summary_settings', array());
		$default_length = $settings['default_length'] ?? 'medium';
		$default_language = $settings['default_language'] ?? 'en';

		?>
		<div id="ai-blog-summary-classic-editor" data-post-id="<?php echo esc_attr($post->ID); ?>">
			<div class="ai-summary-field-wrapper">
				<label for="ai_post_summary">
					<strong><?php esc_html_e('Summary', 'ai-blog-summary'); ?></strong>
				</label>
				<?php
				wp_editor(
					$summary,
					'ai_post_summary',
					array(
						'textarea_name' => 'ai_post_summary',
						'media_buttons'  => false,
						'textarea_rows'  => 5,
						'tinymce'        => true,
						'quicktags'      => true,
					)
				);
				?>
			</div>

			<div class="ai-summary-actions">
				<button type="button" class="button button-secondary ai-generate-summary" 
						data-length="<?php echo esc_attr($default_length); ?>"
						data-language="<?php echo esc_attr($default_language); ?>">
					<?php esc_html_e('Generate Summary', 'ai-blog-summary'); ?>
				</button>
				<button type="button" class="button button-secondary ai-regenerate-summary"
						data-length="<?php echo esc_attr($default_length); ?>"
						data-language="<?php echo esc_attr($default_language); ?>">
					<?php esc_html_e('Regenerate Summary', 'ai-blog-summary'); ?>
				</button>
			</div>

			<div class="ai-summary-show-icon">
				<label>
					<input type="checkbox" name="ai_show_summary_icon" value="1" <?php checked($show_icon); ?>>
					<?php esc_html_e('Show summary icon on front-end', 'ai-blog-summary'); ?>
				</label>
			</div>

			<div class="ai-summary-thunderbolt-news">
				<label>
					<input type="checkbox" name="ai_thunderbolt_news" value="1" <?php checked($thunderbolt_news); ?>>
					<?php esc_html_e('Add news to thunderbolt', 'ai-blog-summary'); ?>
				</label>
			</div>

			<div class="ai-summary-status"></div>
		</div>
		<?php
	}

	/**
	 * Save meta box
	 *
	 * @param int     $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function save_meta_box(int $post_id, \WP_Post $post): void {
		// Verify nonce.
		if (! isset($_POST['ai_blog_summary_nonce']) || 
			 ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ai_blog_summary_nonce'])), 'ai_blog_summary_meta_box')) {
			return;
		}

		// Check autosave.
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}

		// Check permissions.
		if (! current_user_can('edit_post', $post_id)) {
			return;
		}

		// Save summary.
		if (isset($_POST['ai_post_summary'])) {
			$summary = wp_kses_post(wp_unslash($_POST['ai_post_summary']));
			if (! empty($summary)) {
				$this->summary_manager->save_summary($post_id, $summary);
			} else {
				// If empty, delete the summary.
				delete_post_meta($post_id, '_ai_post_summary');
			}
		}

		// Save show icon setting.
		$show_icon = isset($_POST['ai_show_summary_icon']) ? true : false;
		$this->summary_manager->set_show_icon($post_id, $show_icon);

		// Save thunderbolt news setting.
		$thunderbolt_news = isset($_POST['ai_thunderbolt_news']) ? true : false;
		update_post_meta($post_id, '_ai_thunderbolt_news', $thunderbolt_news ? '1' : '0');
	}

	/**
	 * Enqueue assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets(string $hook): void {
		if (! in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		// Only show for posts, not pages
		$screen = get_current_screen();
		if ($screen && $screen->post_type !== 'post') {
			return;
		}

		// Check if classic editor is being used.
		if (function_exists('is_gutenberg_page') && is_gutenberg_page()) {
			return;
		}

		wp_enqueue_script(
			'ai-blog-summary-admin',
			AI_BLOG_SUMMARY_PLUGIN_URL . 'dist/js/admin.js',
			array('jquery'),
			AI_BLOG_SUMMARY_VERSION,
			true
		);

		wp_enqueue_style(
			'ai-blog-summary-admin',
			AI_BLOG_SUMMARY_PLUGIN_URL . 'dist/css/admin.css',
			array(),
			AI_BLOG_SUMMARY_VERSION
		);

		wp_localize_script(
			'ai-blog-summary-admin',
			'aiBlogSummary',
			array(
				'apiUrl'  => rest_url('ai-summary/v1/'),
				'nonce'   => wp_create_nonce('wp_rest'),
			)
		);
	}

	/**
	 * Get supported post types
	 *
	 * @return array
	 */
	private function get_supported_post_types(): array {
		$post_types = get_post_types(array('public' => true), 'names');
		$post_types[] = 'page';
		return array_unique($post_types);
	}
}

