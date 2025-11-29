<?php
/**
 * Settings Manager
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary\Admin;

/**
 * Settings class
 */
class Settings {

	/**
	 * Option name
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'ai_blog_summary_settings';

	/**
	 * Default settings
	 *
	 * @var array
	 */
	private array $defaults = array(
		'provider'         => 'openai',
		'api_key'          => '',
		'model'            => 'gpt-3.5-turbo',
		'timeout'          => 30,
		'api_delay'        => 500, // milliseconds.
		'default_length'  => 'medium',
		'default_language' => 'en',
		'auto_generate'    => true,
		'icon_size'        => 'medium',
		'icon_color'       => '#3b82f6',
		'popup_theme'      => 'auto',
		'readmore_button_color' => '#dc2626',
		'list_bullet_color' => '#3b82f6',
	);

	/**
	 * Get all settings
	 *
	 * @return array
	 */
	public function get_all(): array {
		$settings = get_option(self::OPTION_NAME, array());
		return wp_parse_args($settings, $this->defaults);
	}

	/**
	 * Get setting value
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get(string $key, $default = null) {
		$settings = $this->get_all();
		$value    = $settings[ $key ] ?? ( $default ?? $this->defaults[ $key ] ?? null );
		
		// Convert boolean-like values to actual booleans for boolean settings.
		if ('auto_generate' === $key && ! is_bool($value)) {
			$value = (bool) $value;
		}
		
		return $value;
	}

	/**
	 * Save settings
	 *
	 * @param array $settings Settings array.
	 * @return bool
	 */
	public function save(array $settings): bool {
		$sanitized = $this->sanitize($settings);
		return update_option(self::OPTION_NAME, $sanitized);
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $settings Settings array.
	 * @return array
	 */
	private function sanitize(array $settings): array {
		$sanitized = array();

		foreach ($settings as $key => $value) {
			switch ($key) {
				case 'api_key':
					$sanitized[ $key ] = sanitize_text_field($value);
					break;
				case 'timeout':
				case 'api_delay':
					$sanitized[ $key ] = absint($value);
					break;
				case 'model':
				case 'provider':
				case 'default_length':
				case 'default_language':
				case 'icon_size':
				case 'icon_color':
				case 'popup_theme':
				case 'readmore_button_color':
				case 'list_bullet_color':
					$sanitized[ $key ] = sanitize_text_field($value);
					break;
				case 'auto_generate':
					$sanitized[ $key ] = (bool) $value;
					break;
				default:
					$sanitized[ $key ] = sanitize_text_field($value);
			}
		}

		return wp_parse_args($sanitized, $this->get_all());
	}

	/**
	 * Get provider
	 *
	 * @return string
	 */
	public function get_provider(): string {
		return $this->get('provider', 'openai');
	}

	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function get_api_key(): string {
		return $this->get('api_key', '');
	}

	/**
	 * Get model
	 *
	 * @return string
	 */
	public function get_model(): string {
		return $this->get('model', 'gpt-3.5-turbo');
	}

	/**
	 * Get timeout
	 *
	 * @return int
	 */
	public function get_timeout(): int {
		return $this->get('timeout', 30);
	}

	/**
	 * Get API delay
	 *
	 * @return int
	 */
	public function get_api_delay(): int {
		return $this->get('api_delay', 500);
	}

	/**
	 * Get default length
	 *
	 * @return string
	 */
	public function get_default_length(): string {
		return $this->get('default_length', 'medium');
	}

	/**
	 * Get default language
	 *
	 * @return string
	 */
	public function get_default_language(): string {
		return $this->get('default_language', 'en');
	}

	/**
	 * Is auto-generate enabled
	 *
	 * @return bool
	 */
	public function is_auto_generate_enabled(): bool {
		$value = $this->get('auto_generate', true);
		// Ensure boolean return type (handle cases where it's stored as 1/0 or '1'/'0').
		if (is_bool($value)) {
			return $value;
		}
		return (bool) $value;
	}

	/**
	 * Get read more button color
	 *
	 * @return string
	 */
	public function get_readmore_button_color(): string {
		return $this->get('readmore_button_color', '#dc2626');
	}

	/**
	 * Get list bullet color
	 *
	 * @return string
	 */
	public function get_list_bullet_color(): string {
		return $this->get('list_bullet_color', '#3b82f6');
	}
}

