<?php
/**
 * Gutenberg Block Editor Integration
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News\Editors;

use Hundred_Words_News\SummaryManager;

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
		// Use the same meta box approach as Classic editor
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
		if (! $screen || ! $screen->is_block_editor()) {
			return;
		}

		// Only show for posts, not pages
		if ($screen->post_type !== 'post') {
			return;
		}

		add_meta_box(
			'hundred_words_news_meta_box',
			__('Hundred Words News', 'hundred-words-news'),
			array($this, 'render_meta_box'),
			'post',
			'normal',
			'high'
		);
	}

	/**
	 * Render meta box
	 *
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function render_meta_box(\WP_Post $post): void {
		wp_nonce_field('hundred_words_news_meta_box', 'hundred_words_news_nonce');

		$summary  = $this->summary_manager->get_summary($post->ID);
		$show_icon = $this->summary_manager->should_show_icon($post->ID);
		$thunderbolt_news = get_post_meta($post->ID, '_hundred_words_news_thunderbolt_news', true) === '1';
		$settings = get_option('hundred_words_news_settings', array());
		$default_length = $settings['default_length'] ?? 'medium';
		$default_language = $settings['default_language'] ?? 'en';

		?>
		<div id="hwn-gutenberg-editor" data-post-id="<?php echo esc_attr($post->ID); ?>">
			<div class="hwn-summary-field-wrapper">
				<label for="hwn_post_summary">
					<strong><?php esc_html_e('Summary', 'hundred-words-news'); ?></strong>
				</label>
				<?php
				wp_editor(
					$summary,
					'hwn_post_summary',
					array(
						'textarea_name' => 'hwn_post_summary',
						'media_buttons'  => false,
						'textarea_rows'  => 5,
						'tinymce'        => true,
						'quicktags'      => true,
					)
				);
				?>
			</div>

			<div class="hwn-summary-actions">
				<button type="button" class="button button-secondary hwn-generate-summary" 
						data-length="<?php echo esc_attr($default_length); ?>"
						data-language="<?php echo esc_attr($default_language); ?>">
					<?php esc_html_e('Generate Summary', 'hundred-words-news'); ?>
				</button>
				<button type="button" class="button button-secondary hwn-regenerate-summary"
						data-length="<?php echo esc_attr($default_length); ?>"
						data-language="<?php echo esc_attr($default_language); ?>">
					<?php esc_html_e('Regenerate Summary', 'hundred-words-news'); ?>
				</button>
			</div>

			<div class="hwn-summary-show-icon">
				<label>
					<input type="checkbox" name="hundred_words_news_show_summary_icon" value="1" <?php checked($show_icon); ?>>
					<?php esc_html_e('Show summary icon on front-end', 'hundred-words-news'); ?>
				</label>
			</div>

			<div class="hwn-summary-thunderbolt-news">
				<label>
					<input type="checkbox" name="hundred_words_news_thunderbolt_news" value="1" <?php checked($thunderbolt_news); ?>>
					<?php esc_html_e('Add news to thunderbolt', 'hundred-words-news'); ?>
				</label>
			</div>

			<div class="hwn-summary-status"></div>
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
		if (! isset($_POST['hundred_words_news_nonce']) || 
			 ! wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hundred_words_news_nonce'])), 'hundred_words_news_meta_box')) {
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
		if (isset($_POST['hwn_post_summary'])) {
			$summary = wp_kses_post(wp_unslash($_POST['hwn_post_summary']));
			if (! empty($summary)) {
				$this->summary_manager->save_summary($post_id, $summary);
			} else {
				// If empty, delete the summary.
				delete_post_meta($post_id, '_hundred_words_news_post_summary');
			}
		}

		// Save show icon setting.
		$show_icon = isset($_POST['hundred_words_news_show_summary_icon']) ? true : false;
		$this->summary_manager->set_show_icon($post_id, $show_icon);

		// Save thunderbolt news setting.
		$thunderbolt_news = isset($_POST['hundred_words_news_thunderbolt_news']) ? true : false;
		update_post_meta($post_id, '_hundred_words_news_thunderbolt_news', $thunderbolt_news ? '1' : '0');
	}

	/**
	 * Enqueue block editor assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets(string $hook): void {
		if (! in_array($hook, array('post.php', 'post-new.php'), true)) {
			return;
		}

		$screen = get_current_screen();
		if (! $screen || ! $screen->is_block_editor()) {
			return;
		}

		// Only show for posts, not pages
		if ($screen->post_type !== 'post') {
			return;
		}

		wp_enqueue_script(
			'hundred-words-news-admin',
			HUNDRED_WORDS_NEWS_PLUGIN_URL . 'dist/js/admin.js',
			array('jquery'),
			HUNDRED_WORDS_NEWS_VERSION,
			true
		);

		wp_enqueue_style(
			'hundred-words-news-admin',
			HUNDRED_WORDS_NEWS_PLUGIN_URL . 'dist/css/admin.css',
			array(),
			HUNDRED_WORDS_NEWS_VERSION
		);

		wp_localize_script(
			'hundred-words-news-admin',
			'hundredWordsNewsAdmin',
			array(
				'apiUrl'  => rest_url('hundred-words-news/v1/'),
				'nonce'   => wp_create_nonce('wp_rest'),
			)
		);
	}
}
