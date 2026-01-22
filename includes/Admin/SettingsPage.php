<?php
/**
 * Admin Settings Page
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News\Admin;

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
		add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'), 10, 1);
	}

	/**
	 * Add menu page
	 *
	 * @return void
	 */
	public function add_menu_page(): void {
		add_menu_page(
			__('100 Words News', 'news-in-100-words'),
			__('100 Words News', 'news-in-100-words'),
			'manage_options',
			'news-in-100-words',
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
		register_setting(
			'hundred_words_news_settings',
			'hundred_words_news_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array($this, 'sanitize_settings'),
			)
		);
	}

	/**
	 * Enqueue assets
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets(string $hook): void {
		wp_enqueue_script(
			'news-in-100-words-admin',
			HUNDRED_WORDS_NEWS_PLUGIN_URL . 'assets/admin/js/index.js',
			array('jquery'),
			HUNDRED_WORDS_NEWS_VERSION,
			true
		);
		wp_enqueue_style(
			'news-in-100-words-admin',
			HUNDRED_WORDS_NEWS_PLUGIN_URL . 'assets/admin/css/index.css',
			array(),
			HUNDRED_WORDS_NEWS_VERSION
		);
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
		$valid_providers = array('openai');
		$provider = sanitize_text_field($input['provider'] ?? 'openai');
		$sanitized['provider'] = in_array($provider, $valid_providers, true) ? $provider : 'openai';

		// API key: Use trim() for secret values that shouldn't be altered.
		$sanitized['api_key'] = isset($input['api_key']) ? trim((string) $input['api_key']) : '';

		$valid_models = array('gpt-4', 'gpt-4-turbo', 'gpt-3.5-turbo');
		$model = sanitize_text_field($input['model'] ?? 'gpt-3.5-turbo');
		$sanitized['model'] = in_array($model, $valid_models, true) ? $model : 'gpt-3.5-turbo';

		$sanitized['timeout']  = absint($input['timeout'] ?? 30);
		$sanitized['api_delay'] = absint($input['api_delay'] ?? 500);

		// Summary Generation.
		$valid_lengths = array('short', 'medium', 'large');
		$default_length = sanitize_text_field($input['default_length'] ?? 'medium');
		$sanitized['default_length'] = in_array($default_length, $valid_lengths, true) ? $default_length : 'medium';

		$valid_languages = array('en', 'ar', 'hi', 'ka');
		$default_language = sanitize_text_field($input['default_language'] ?? 'en');
		$sanitized['default_language'] = in_array($default_language, $valid_languages, true) ? $default_language : 'en';

		$sanitized['auto_generate'] = isset($input['auto_generate']) ? 1 : 0;

		// Icon Settings.
		$valid_icon_sizes = array('small', 'medium', 'large');
		$icon_size = sanitize_text_field($input['icon_size'] ?? 'medium');
		$sanitized['icon_size'] = in_array($icon_size, $valid_icon_sizes, true) ? $icon_size : 'medium';

		$icon_color = sanitize_text_field($input['icon_color'] ?? '#3b82f6');
		$sanitized['icon_color'] = sanitize_hex_color($icon_color) ?: '#3b82f6';

		// Popup Settings.
		$valid_popup_themes = array('light', 'dark', 'auto');
		$popup_theme = sanitize_text_field($input['popup_theme'] ?? 'auto');
		$sanitized['popup_theme'] = in_array($popup_theme, $valid_popup_themes, true) ? $popup_theme : 'auto';

		$readmore_button_color = sanitize_text_field($input['readmore_button_color'] ?? '#dc2626');
		$sanitized['readmore_button_color'] = sanitize_hex_color($readmore_button_color) ?: '#dc2626';

		$list_bullet_color = sanitize_text_field($input['list_bullet_color'] ?? '#3b82f6');
		$sanitized['list_bullet_color'] = sanitize_hex_color($list_bullet_color) ?: '#3b82f6';

		// Thunderbolt Settings.
		$thunderbolt_input = $input['thunderbolt'] ?? array();
		$sanitized['thunderbolt'] = array();
		$sanitized['thunderbolt']['logo_url'] = esc_url_raw($thunderbolt_input['logo_url'] ?? '');

		$bg_color = sanitize_text_field($thunderbolt_input['bg_color'] ?? '#000000');
		$sanitized['thunderbolt']['bg_color'] = sanitize_hex_color($bg_color) ?: '#000000';

		$valid_nav_positions = array('right-center', 'right-top', 'right-bottom', 'left-center', 'left-top', 'left-bottom');
		$nav_position = sanitize_text_field($thunderbolt_input['nav_position'] ?? 'right-center');
		$sanitized['thunderbolt']['nav_position'] = in_array($nav_position, $valid_nav_positions, true) ? $nav_position : 'right-center';

		$nav_color = sanitize_text_field($thunderbolt_input['nav_color'] ?? '');
		$sanitized['thunderbolt']['nav_color'] = ! empty($nav_color) ? (sanitize_hex_color($nav_color) ?: '') : '';

		$nav_bg_color = sanitize_text_field($thunderbolt_input['nav_bg_color'] ?? '');
		$sanitized['thunderbolt']['nav_bg_color'] = ! empty($nav_bg_color) ? (sanitize_hex_color($nav_bg_color) ?: '') : '';

		$sanitized['thunderbolt']['nav_font_size'] = sanitize_text_field($thunderbolt_input['nav_font_size'] ?? '20px');
		$sanitized['thunderbolt']['show_share'] = isset($thunderbolt_input['show_share']) ? 1 : 0;

		$valid_themes = array('light', 'dark', 'auto');
		$theme = sanitize_text_field($thunderbolt_input['theme'] ?? 'dark');
		$sanitized['thunderbolt']['theme'] = in_array($theme, $valid_themes, true) ? $theme : 'dark';

		$sanitized['thunderbolt']['title_font_size'] = sanitize_text_field($thunderbolt_input['title_font_size'] ?? '1rem');

		$title_color = sanitize_text_field($thunderbolt_input['title_color'] ?? '');
		$sanitized['thunderbolt']['title_color'] = ! empty($title_color) ? (sanitize_hex_color($title_color) ?: '') : '';

		$sanitized['thunderbolt']['meta_font_size'] = sanitize_text_field($thunderbolt_input['meta_font_size'] ?? '0.70rem');

		$meta_color = sanitize_text_field($thunderbolt_input['meta_color'] ?? '');
		$sanitized['thunderbolt']['meta_color'] = ! empty($meta_color) ? (sanitize_hex_color($meta_color) ?: '') : '';

		$sanitized['thunderbolt']['content_font_size'] = sanitize_text_field($thunderbolt_input['content_font_size'] ?? '0.75rem');

		$content_color = sanitize_text_field($thunderbolt_input['content_color'] ?? '');
		$sanitized['thunderbolt']['content_color'] = ! empty($content_color) ? (sanitize_hex_color($content_color) ?: '') : '';

		$bullet_color = sanitize_text_field($thunderbolt_input['bullet_color'] ?? '#3b82f6');
		$sanitized['thunderbolt']['bullet_color'] = sanitize_hex_color($bullet_color) ?: '#3b82f6';

		$card_bg_color = sanitize_text_field($thunderbolt_input['card_bg_color'] ?? '#252525');
		$sanitized['thunderbolt']['card_bg_color'] = sanitize_hex_color($card_bg_color) ?: '#252525';

		$readmore_bg_color = sanitize_text_field($thunderbolt_input['readmore_bg_color'] ?? '#dc2626');
		$sanitized['thunderbolt']['readmore_bg_color'] = sanitize_hex_color($readmore_bg_color) ?: '#dc2626';

		$readmore_text_color = sanitize_text_field($thunderbolt_input['readmore_text_color'] ?? '#ffffff');
		$sanitized['thunderbolt']['readmore_text_color'] = sanitize_hex_color($readmore_text_color) ?: '#ffffff';

		$sanitized['thunderbolt']['readmore_font_size'] = sanitize_text_field($thunderbolt_input['readmore_font_size'] ?? '0.875rem');

		return $sanitized;
	}

	/**
	 * Render settings page
	 *
	 * @return void
	 */
	public function render_page(): void {
		if (isset($_POST['submit']) && check_admin_referer('hundred_words_news_settings')) {
			$input_settings = isset($_POST['hundred_words_news_settings']) && is_array($_POST['hundred_words_news_settings'])
				? array_map('sanitize_text_field', wp_unslash($_POST['hundred_words_news_settings']))
				: array();
			$sanitized_settings = $this->sanitize_settings($input_settings);
			$existing_settings = $this->settings->get_all();
			$new_settings = array_merge($existing_settings, $sanitized_settings);
			$this->settings->save($new_settings);
			echo '<div class="notice notice-success"><p>' . esc_html__('Settings saved.', 'news-in-100-words') . '</p></div>';
		}

		$settings = $this->settings->get_all();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('Hundred Words News Settings', 'news-in-100-words'); ?></h1>

			<form method="post" action="">
				<?php wp_nonce_field('hundred_words_news_settings'); ?>

				<h2 class="nav-tab-wrapper">
					<a href="#api-model" class="nav-tab nav-tab-active"><?php esc_html_e('API & Model', 'news-in-100-words'); ?></a>
					<a href="#summary-generation" class="nav-tab"><?php esc_html_e('Summary Generation', 'news-in-100-words'); ?></a>
					<a href="#icon-settings" class="nav-tab"><?php esc_html_e('Icon Settings', 'news-in-100-words'); ?></a>
					<a href="#popup-settings" class="nav-tab"><?php esc_html_e('Popup Settings', 'news-in-100-words'); ?></a>
					<a href="#thunderbolt-page" class="nav-tab"><?php esc_html_e('Thunderbolt Page', 'news-in-100-words'); ?></a>
				</h2>

				<div id="api-model" class="tab-content">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="provider"><?php esc_html_e('AI Provider', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<select name="hundred_words_news_settings[provider]" id="provider">
									<option value="openai" <?php selected($settings['provider'], 'openai'); ?>>OpenAI</option>
								</select>
								<p class="description"><?php esc_html_e('Select the AI provider to use for summary generation.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_key"><?php esc_html_e('API Key', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<input type="password" name="hundred_words_news_settings[api_key]" id="api_key" 
									   value="<?php echo esc_attr($settings['api_key']); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Enter your API key for the selected provider.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="model"><?php esc_html_e('Model', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<select name="hundred_words_news_settings[model]" id="model">
									<option value="gpt-4" <?php selected($settings['model'], 'gpt-4'); ?>>GPT-4</option>
									<option value="gpt-4-turbo" <?php selected($settings['model'], 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
									<option value="gpt-3.5-turbo" <?php selected($settings['model'], 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
								</select>
								<p class="description"><?php esc_html_e('Select the AI model to use.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="timeout"><?php esc_html_e('Timeout (seconds)', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<input type="number" name="hundred_words_news_settings[timeout]" id="timeout" 
									   value="<?php echo esc_attr($settings['timeout']); ?>" min="10" max="120" class="small-text">
								<p class="description"><?php esc_html_e('API request timeout in seconds.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_delay"><?php esc_html_e('API Delay (ms)', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<input type="number" name="hundred_words_news_settings[api_delay]" id="api_delay" 
									   value="<?php echo esc_attr($settings['api_delay']); ?>" min="0" max="5000" class="small-text">
								<p class="description"><?php esc_html_e('Delay between API calls in milliseconds to avoid rate limits.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div id="summary-generation" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="default_length"><?php esc_html_e('Default Length', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<select name="hundred_words_news_settings[default_length]" id="default_length">
									<option value="short" <?php selected($settings['default_length'], 'short'); ?>><?php esc_html_e('1 paragraph', 'news-in-100-words'); ?></option>
									<option value="medium" <?php selected($settings['default_length'], 'medium'); ?>><?php esc_html_e('2 paragraphs', 'news-in-100-words'); ?></option>
									<option value="large" <?php selected($settings['default_length'], 'large'); ?>><?php esc_html_e('3 paragraphs', 'news-in-100-words'); ?></option>
								</select>
								<p class="description"><?php esc_html_e('Default summary length preset. The summary will be formatted as bullet points.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="default_language"><?php esc_html_e('Default Language', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<select name="hundred_words_news_settings[default_language]" id="default_language">
									<option value="en" <?php selected($settings['default_language'], 'en'); ?>>English</option>
									<option value="ar" <?php selected($settings['default_language'], 'ar'); ?>>Arabic</option>
				                    <option value="hi" <?php selected($settings['default_language'], 'hi'); ?>>Hindi</option>
									<option value="ka" <?php selected($settings['default_language'], 'ka'); ?>>Kannada</option>
								</select>
								<p class="description"><?php esc_html_e('Default language for summaries.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e('Auto-Generate', 'news-in-100-words'); ?>
							</th>
							<td>
								<label>
									<input type="checkbox" name="hundred_words_news_settings[auto_generate]" value="1" 
										   <?php checked($settings['auto_generate'], 1); ?>>
									<?php esc_html_e('Automatically generate summary when post is saved (if no summary exists)', 'news-in-100-words'); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div id="icon-settings" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="icon_size"><?php esc_html_e('Icon Size', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<select name="hundred_words_news_settings[icon_size]" id="icon_size">
									<option value="small" <?php selected($settings['icon_size'], 'small'); ?>>Small</option>
									<option value="medium" <?php selected($settings['icon_size'], 'medium'); ?>>Medium</option>
									<option value="large" <?php selected($settings['icon_size'], 'large'); ?>>Large</option>
								</select>
								<p class="description"><?php esc_html_e('Size of the summary icon.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="icon_color"><?php esc_html_e('Icon Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<input type="color" name="hundred_words_news_settings[icon_color]" id="icon_color" 
									   value="<?php echo esc_attr($settings['icon_color']); ?>">
								<p class="description"><?php esc_html_e('Color of the summary icon.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div id="popup-settings" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="popup_theme"><?php esc_html_e('Popup Theme', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<select name="hundred_words_news_settings[popup_theme]" id="popup_theme">
									<option value="light" <?php selected($settings['popup_theme'], 'light'); ?>>Light</option>
									<option value="dark" <?php selected($settings['popup_theme'], 'dark'); ?>>Dark</option>
									<option value="auto" <?php selected($settings['popup_theme'], 'auto'); ?>>Auto (System Preference)</option>
								</select>
								<p class="description"><?php esc_html_e('Theme for the summary popup modal.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="readmore_button_color"><?php esc_html_e('Read More Button Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<input type="color" name="hundred_words_news_settings[readmore_button_color]" id="readmore_button_color" 
									   value="<?php echo esc_attr($settings['readmore_button_color'] ?? '#dc2626'); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Background color for the "Read more" button in the popup.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="list_bullet_color"><?php esc_html_e('List Bullet Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<input type="color" name="hundred_words_news_settings[list_bullet_color]" id="list_bullet_color" 
									   value="<?php echo esc_attr($settings['list_bullet_color'] ?? '#3b82f6'); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for the bullet points in the summary list.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<div id="thunderbolt-page" class="tab-content" style="display:none;">
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="thunderbolt_logo_url"><?php esc_html_e('Logo Image URL', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$thunderbolt_settings = $settings['thunderbolt'] ?? array();
								$logo_url = $thunderbolt_settings['logo_url'] ?? '';
								?>
								<input type="url" name="hundred_words_news_settings[thunderbolt][logo_url]" id="thunderbolt_logo_url" 
									   value="<?php echo esc_attr($logo_url); ?>" class="regular-text" placeholder="https://example.com/logo.png">
								<p class="description"><?php esc_html_e('URL of the logo image to display at the top left. Leave empty to hide logo.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_theme"><?php esc_html_e('Theme', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$theme = $thunderbolt_settings['theme'] ?? 'dark';
								?>
								<select name="hundred_words_news_settings[thunderbolt][theme]" id="thunderbolt_theme">
									<option value="dark" <?php selected($theme, 'dark'); ?>><?php esc_html_e('Dark', 'news-in-100-words'); ?></option>
									<option value="light" <?php selected($theme, 'light'); ?>><?php esc_html_e('Light', 'news-in-100-words'); ?></option>
									<option value="auto" <?php selected($theme, 'auto'); ?>><?php esc_html_e('Auto (System Preference)', 'news-in-100-words'); ?></option>
								</select>
								<p class="description"><?php esc_html_e('Theme for the Thunderbolt page.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_bg_color"><?php esc_html_e('Background Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$bg_color = $thunderbolt_settings['bg_color'] ?? '#000000';
								$preset_colors = array(
									'#000000' => __('Black', 'news-in-100-words'),
									'#1a1a1a' => __('Dark Gray', 'news-in-100-words'),
									'#0a0e27' => __('Navy Blue', 'news-in-100-words'),
									'#1a0d0d' => __('Dark Red', 'news-in-100-words'),
									'#0d1a0d' => __('Dark Green', 'news-in-100-words'),
								);
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][bg_color]" id="thunderbolt_bg_color" 
									   value="<?php echo esc_attr($bg_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Background color for the Thunderbolt page.', 'news-in-100-words'); ?></p>
								<!-- <div style="margin-top: 10px;">
									<strong><?php esc_html_e('Preset Colors:', 'news-in-100-words'); ?></strong>
									<div style="display: flex; gap: 10px; margin-top: 5px; flex-wrap: wrap;">
										<?php foreach ($preset_colors as $color => $label) : ?>
											<button type="button" class="button thunderbolt-preset-color" 
													data-color="<?php echo esc_attr($color); ?>"
													style="background-color: <?php echo esc_attr($color); ?>; color: <?php echo esc_attr($color === '#000000' ? '#fff' : '#000'); ?>; border: 2px solid <?php echo esc_attr($bg_color === $color ? '#0073aa' : 'transparent'); ?>;">
												<?php echo esc_html($label); ?>
											</button>
										<?php endforeach; ?>
									</div>
								</div> -->
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_card_bg_color"><?php esc_html_e('Card Background Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$card_bg_color = $thunderbolt_settings['card_bg_color'] ?? '#252525';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][card_bg_color]" id="thunderbolt_card_bg_color" 
									   value="<?php echo esc_attr($card_bg_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Background color for individual post cards.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<?php esc_html_e('Show Share Sidebar', 'news-in-100-words'); ?>
							</th>
							<td>
								<?php
								$show_share = isset($thunderbolt_settings['show_share']) ? (bool) $thunderbolt_settings['show_share'] : true;
								?>
								<label>
									<input type="checkbox" name="hundred_words_news_settings[thunderbolt][show_share]" value="1" 
										   <?php checked($show_share, true); ?>>
									<?php esc_html_e('Show share buttons sidebar on post cards', 'news-in-100-words'); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								<h3 style="margin: 20px 0 10px 0;"><?php esc_html_e('Navigation Settings', 'news-in-100-words'); ?></h3>
							</th>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_nav_position"><?php esc_html_e('Navigation Arrows Position', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$nav_position = $thunderbolt_settings['nav_position'] ?? 'right-center';
								$nav_positions = array(
									'right-center' => __('Right Side (Centered)', 'news-in-100-words'),
									'right-top' => __('Right Side (Top)', 'news-in-100-words'),
									'right-bottom' => __('Right Side (Bottom)', 'news-in-100-words'),
									'left-center' => __('Left Side (Centered)', 'news-in-100-words'),
									'left-top' => __('Left Side (Top)', 'news-in-100-words'),
									'left-bottom' => __('Left Side (Bottom)', 'news-in-100-words'),
								);
								?>
								<select name="hundred_words_news_settings[thunderbolt][nav_position]" id="thunderbolt_nav_position">
									<?php foreach ($nav_positions as $value => $label) : ?>
										<option value="<?php echo esc_attr($value); ?>" <?php selected($nav_position, $value); ?>>
											<?php echo esc_html($label); ?>
										</option>
									<?php endforeach; ?>
								</select>
								<p class="description"><?php esc_html_e('Position of navigation arrows on desktop and tablet.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_nav_color"><?php esc_html_e('Navigation Arrows Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$nav_color = $thunderbolt_settings['nav_color'] ?? '';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][nav_color]" id="thunderbolt_nav_color" 
									   value="<?php echo esc_attr($nav_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for navigation arrow icons. Leave empty to use theme default.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_nav_bg_color"><?php esc_html_e('Navigation Arrows Background Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$nav_bg_color = $thunderbolt_settings['nav_bg_color'] ?? '';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][nav_bg_color]" id="thunderbolt_nav_bg_color" 
									   value="<?php echo esc_attr($nav_bg_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Background color for navigation arrows. Leave empty to use transparent background.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_nav_font_size"><?php esc_html_e('Navigation Arrows Font Size', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$nav_font_size = $thunderbolt_settings['nav_font_size'] ?? '20px';
								?>
								<input type="text" name="hundred_words_news_settings[thunderbolt][nav_font_size]" id="thunderbolt_nav_font_size" 
									   value="<?php echo esc_attr($nav_font_size); ?>" class="regular-text" placeholder="20px">
								<p class="description"><?php esc_html_e('Font size for navigation arrows (e.g., 20px, 1.5rem).', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								<h3 style="margin: 20px 0 10px 0;"><?php esc_html_e('Typography Settings', 'news-in-100-words'); ?></h3>
							</th>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_title_font_size"><?php esc_html_e('Title Font Size', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$title_font_size = $thunderbolt_settings['title_font_size'] ?? '1rem';
								?>
								<input type="text" name="hundred_words_news_settings[thunderbolt][title_font_size]" id="thunderbolt_title_font_size" 
									   value="<?php echo esc_attr($title_font_size); ?>" class="regular-text" placeholder="1rem">
								<p class="description"><?php esc_html_e('Font size for post titles (e.g., 1rem, 18px, 1.2em).', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_title_color"><?php esc_html_e('Title Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$title_color = $thunderbolt_settings['title_color'] ?? '';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][title_color]" id="thunderbolt_title_color" 
									   value="<?php echo esc_attr($title_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for post titles. Leave empty to use theme default.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_meta_font_size"><?php esc_html_e('Metadata Font Size', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$meta_font_size = $thunderbolt_settings['meta_font_size'] ?? '0.70rem';
								?>
								<input type="text" name="hundred_words_news_settings[thunderbolt][meta_font_size]" id="thunderbolt_meta_font_size" 
									   value="<?php echo esc_attr($meta_font_size); ?>" class="regular-text" placeholder="0.70rem">
								<p class="description"><?php esc_html_e('Font size for category and date metadata (e.g., 0.70rem, 12px).', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_meta_color"><?php esc_html_e('Metadata Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$meta_color = $thunderbolt_settings['meta_color'] ?? '';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][meta_color]" id="thunderbolt_meta_color" 
									   value="<?php echo esc_attr($meta_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for category and date metadata. Leave empty to use theme default.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_content_font_size"><?php esc_html_e('Content Font Size', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$content_font_size = $thunderbolt_settings['content_font_size'] ?? '0.75rem';
								?>
								<input type="text" name="hundred_words_news_settings[thunderbolt][content_font_size]" id="thunderbolt_content_font_size" 
									   value="<?php echo esc_attr($content_font_size); ?>" class="regular-text" placeholder="0.75rem">
								<p class="description"><?php esc_html_e('Font size for summary content text (e.g., 0.75rem, 14px).', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_content_color"><?php esc_html_e('Content Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$content_color = $thunderbolt_settings['content_color'] ?? '';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][content_color]" id="thunderbolt_content_color" 
									   value="<?php echo esc_attr($content_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for summary content text. Leave empty to use theme default.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_bullet_color"><?php esc_html_e('Content Bullet Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$bullet_color = $thunderbolt_settings['bullet_color'] ?? '#3b82f6';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][bullet_color]" id="thunderbolt_bullet_color" 
									   value="<?php echo esc_attr($bullet_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Color for bullet points in the summary list.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								<h3 style="margin: 20px 0 10px 0;"><?php esc_html_e('Read More Button Settings', 'news-in-100-words'); ?></h3>
							</th>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_readmore_bg_color"><?php esc_html_e('Read More Background Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$readmore_bg_color = $thunderbolt_settings['readmore_bg_color'] ?? '#dc2626';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][readmore_bg_color]" id="thunderbolt_readmore_bg_color" 
									   value="<?php echo esc_attr($readmore_bg_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Background color for the "Read more" button.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_readmore_text_color"><?php esc_html_e('Read More Text Color', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$readmore_text_color = $thunderbolt_settings['readmore_text_color'] ?? '#ffffff';
								?>
								<input type="color" name="hundred_words_news_settings[thunderbolt][readmore_text_color]" id="thunderbolt_readmore_text_color" 
									   value="<?php echo esc_attr($readmore_text_color); ?>" class="regular-text">
								<p class="description"><?php esc_html_e('Text color for the "Read more" button.', 'news-in-100-words'); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="thunderbolt_readmore_font_size"><?php esc_html_e('Read More Font Size', 'news-in-100-words'); ?></label>
							</th>
							<td>
								<?php
								$readmore_font_size = $thunderbolt_settings['readmore_font_size'] ?? '0.875rem';
								?>
								<input type="text" name="hundred_words_news_settings[thunderbolt][readmore_font_size]" id="thunderbolt_readmore_font_size" 
									   value="<?php echo esc_attr($readmore_font_size); ?>" class="regular-text" placeholder="0.875rem">
								<p class="description"><?php esc_html_e('Font size for the "Read more" button text (e.g., 0.875rem, 14px).', 'news-in-100-words'); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

