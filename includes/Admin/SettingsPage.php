<?php
/**
 * Admin Settings Page
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary\Admin;

/**
 * Settings page class
 */
class SettingsPage {

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
		$this->settings = new Settings();
	}

	/**
	 * Initialize settings page
	 *
	 * @return void
	 */
	public function init(): void {
		add_action('admin_menu', array($this, 'add_menu_page'), 9);
		add_action('admin_init', array($this, 'register_settings'));
	}

	/**
	 * Add menu page
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__('AI Blog Summary', 'ai-blog-summary'),
			__('AI Blog Summary', 'ai-blog-summary'),
			'manage_options',
			'ai-blog-summary',
			array($this, 'render_page'),
			'dashicons-superhero',
			59 // Position above Settings (60).
		);
	}

	/**
	 * Register settings
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting('ai_blog_summary_settings', 'ai_blog_summary_settings', array($this, 'sanitize_settings'));
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Input data.
	 * @return array
	 */
	public function sanitize_settings(array $input): array {
		$sanitized = array();

		// API & Model.
		$sanitized['provider'] = sanitize_text_field($input['provider'] ?? 'openai');
		$sanitized['api_key']  = sanitize_text_field($input['api_key'] ?? '');
		$sanitized['model']    = sanitize_text_field($input['model'] ?? 'gpt-3.5-turbo');
		$sanitized['timeout']  = absint($input['timeout'] ?? 30);
		$sanitized['api_delay'] = absint($input['api_delay'] ?? 500);

		// Summary Generation.
		$sanitized['default_length']  = sanitize_text_field($input['default_length'] ?? 'medium');
		$sanitized['default_language'] = sanitize_text_field($input['default_language'] ?? 'en');
		$sanitized['auto_generate']    = isset($input['auto_generate']) ? 1 : 0;

		// Icon Settings.
		$sanitized['icon_size']  = sanitize_text_field($input['icon_size'] ?? 'medium');
		$sanitized['icon_color'] = sanitize_text_field($input['icon_color'] ?? '#3b82f6');

		// Popup Settings.
		$sanitized['popup_theme'] = sanitize_text_field($input['popup_theme'] ?? 'auto');
		$sanitized['readmore_button_color'] = sanitize_text_field($input['readmore_button_color'] ?? '#dc2626');
		$sanitized['list_bullet_color'] = sanitize_text_field($input['list_bullet_color'] ?? '#3b82f6');

		return $sanitized;
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_page(): void {
		if (isset($_POST['submit']) && check_admin_referer('ai_blog_summary_settings')) {
			$settings = $this->settings->get_all();
			$new_settings = array_merge($settings, $_POST['ai_blog_summary_settings'] ?? array());
			$this->settings->save($new_settings);
			echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'ai-blog-summary') . '</p></div>';
		}

		$settings = $this->settings->get_all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('AI Blog Summary Settings', 'ai-blog-summary'); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field('ai_blog_summary_settings'); ?>

				<h2 class="nav-tab-wrapper">
					<a href="#api-model" class="nav-tab nav-tab-active"><?php esc_html_e('API & Model', 'ai-blog-summary'); ?></a>
					<a href="#summary-generation" class="nav-tab"><?php esc_html_e('Summary Generation', 'ai-blog-summary'); ?></a>
					<a href="#icon-settings" class="nav-tab"><?php esc_html_e('Icon Settings', 'ai-blog-summary'); ?></a>
					<a href="#popup-settings" class="nav-tab"><?php esc_html_e('Popup Settings', 'ai-blog-summary'); ?></a>
				</h2>

				<div id="api-model" class="tab-content">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="provider"><?php esc_html_e('AI Provider', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<select name="ai_blog_summary_settings[provider]" id="provider">
									<option value="openai" <?php selected($settings['provider'], 'openai'); ?>>OpenAI</option>
								</select>
								<p class="description"><?php esc_html_e('Select the AI provider to use for summary generation.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_key"><?php esc_html_e('API Key', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<input type="password" name="ai_blog_summary_settings[api_key]" id="api_key" 
									   value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Enter your API key for the selected provider.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="model"><?php esc_html_e('Model', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<select name="ai_blog_summary_settings[model]" id="model">
									<option value="gpt-4" <?php selected($settings['model'], 'gpt-4'); ?>>GPT-4</option>
									<option value="gpt-4-turbo" <?php selected($settings['model'], 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
									<option value="gpt-3.5-turbo" <?php selected($settings['model'], 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
								</select>
								<p class="description"><?php esc_html_e('Select the AI model to use.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="timeout"><?php esc_html_e('Timeout (seconds)', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<input type="number" name="ai_blog_summary_settings[timeout]" id="timeout" 
									   value="<?php echo esc_attr($settings['timeout']); ?>" min="10" max="120" class="small-text">
								<p class="description"><?php esc_html_e('API request timeout in seconds.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_delay"><?php esc_html_e('API Delay (ms)', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<input type="number" name="ai_blog_summary_settings[api_delay]" id="api_delay" 
									   value="<?php echo esc_attr($settings['api_delay']); ?>" min="0" max="5000" class="small-text">
								<p class="description"><?php esc_html_e('Delay between API calls in milliseconds to avoid rate limits.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div id="summary-generation" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="default_length"><?php esc_html_e('Default Length', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<select name="ai_blog_summary_settings[default_length]" id="default_length">
									<option value="short" <?php selected($settings['default_length'], 'short'); ?>><?php esc_html_e('Short (50 words)', 'ai-blog-summary'); ?></option>
									<option value="medium" <?php selected($settings['default_length'], 'medium'); ?>><?php esc_html_e('Medium (100 words)', 'ai-blog-summary'); ?></option>
									<option value="large" <?php selected($settings['default_length'], 'large'); ?>><?php esc_html_e('Large (200 words)', 'ai-blog-summary'); ?></option>
								</select>
								<p class="description"><?php esc_html_e('Default summary length preset. The summary will be formatted as bullet points.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="default_language"><?php esc_html_e('Default Language', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<select name="ai_blog_summary_settings[default_language]" id="default_language">
									<option value="en" <?php selected($settings['default_language'], 'en'); ?>>English</option>
									<option value="es" <?php selected($settings['default_language'], 'es'); ?>>Spanish</option>
									<option value="fr" <?php selected($settings['default_language'], 'fr'); ?>>French</option>
									<option value="de" <?php selected($settings['default_language'], 'de'); ?>>German</option>
									<option value="it" <?php selected($settings['default_language'], 'it'); ?>>Italian</option>
									<option value="pt" <?php selected($settings['default_language'], 'pt'); ?>>Portuguese</option>
								</select>
								<p class="description"><?php esc_html_e('Default language for summaries.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e('Auto-Generate', 'ai-blog-summary'); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="ai_blog_summary_settings[auto_generate]" value="1" 
										   <?php checked($settings['auto_generate'], 1); ?>>
									<?php esc_html_e('Automatically generate summary when post is saved (if no summary exists)', 'ai-blog-summary'); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div id="icon-settings" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="icon_size"><?php esc_html_e('Icon Size', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<select name="ai_blog_summary_settings[icon_size]" id="icon_size">
									<option value="small" <?php selected($settings['icon_size'], 'small'); ?>>Small</option>
									<option value="medium" <?php selected($settings['icon_size'], 'medium'); ?>>Medium</option>
									<option value="large" <?php selected($settings['icon_size'], 'large'); ?>>Large</option>
								</select>
								<p class="description"><?php esc_html_e('Size of the summary icon.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="icon_color"><?php esc_html_e('Icon Color', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<input type="color" name="ai_blog_summary_settings[icon_color]" id="icon_color" 
									   value="<?php echo esc_attr($settings['icon_color']); ?>">
								<p class="description"><?php esc_html_e('Color of the summary icon.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div id="popup-settings" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="popup_theme"><?php esc_html_e('Popup Theme', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<select name="ai_blog_summary_settings[popup_theme]" id="popup_theme">
									<option value="light" <?php selected($settings['popup_theme'], 'light'); ?>>Light</option>
									<option value="dark" <?php selected($settings['popup_theme'], 'dark'); ?>>Dark</option>
									<option value="auto" <?php selected($settings['popup_theme'], 'auto'); ?>>Auto (System Preference)</option>
								</select>
								<p class="description"><?php esc_html_e('Theme for the summary popup modal.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="readmore_button_color"><?php esc_html_e('Read More Button Color', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<input type="color" name="ai_blog_summary_settings[readmore_button_color]" id="readmore_button_color" 
									   value="<?php echo esc_attr($settings['readmore_button_color'] ?? '#dc2626'); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Background color for the "Read more" button in the popup.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="list_bullet_color"><?php esc_html_e('List Bullet Color', 'ai-blog-summary'); ?></label>
							</th>
							<td>
								<input type="color" name="ai_blog_summary_settings[list_bullet_color]" id="list_bullet_color" 
									   value="<?php echo esc_attr($settings['list_bullet_color'] ?? '#3b82f6'); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for the bullet points in the summary list.', 'ai-blog-summary'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>

		<script>
		jQuery(document).ready(function($) {
			$('.nav-tab').on('click', function(e) {
				e.preventDefault();
				var target = $(this).attr('href');
				$('.nav-tab').removeClass('nav-tab-active');
				$(this).addClass('nav-tab-active');
				$('.tab-content').hide();
				$(target).show();
			});
		});
		</script>
		<?php
	}
}

