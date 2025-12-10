<?php
/**
 * Main Plugin Class
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News;

use Hundred_Words_News\Editors\Gutenberg;
use Hundred_Words_News\Editors\Classic;
use Hundred_Words_News\REST\API;
use Hundred_Words_News\Admin\SettingsPage;
use Hundred_Words_News\Frontend\Display;
use Hundred_Words_News\Frontend\Thunderbolt;
use Hundred_Words_News\AutoGenerator;

/**
 * Main plugin class
 */
class Plugin {

	/**
	 * Plugin instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Get plugin instance
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize plugin
	 *
	 * @return void
	 */
	private function init(): void {

		// plugin uninstall
		register_uninstall_hook(__FILE__, array($this, 'plugin_uninstall'));

		// Load textdomain.
		add_action('plugins_loaded', array($this, 'load_textdomain'));

		// Register meta fields.
		add_action('init', array($this, 'register_meta_fields'));

		// Initialize REST API.
		add_action('rest_api_init', array($this, 'init_rest_api'));

		// Initialize editor integrations.
		$this->init_editors();

		// Initialize admin.
		$this->init_admin();

		// Initialize front-end.
		$this->init_frontend();

		// Initialize auto generator.
		$this->init_auto_generator();
	}

	/**
	 * Plugin uninstall
	 *
	 * @return void
	 */
	public function plugin_uninstall(): void {
		// TODO: comment code for future enhancements
		// delete plugin options
		// delete_option('hundred_words_news_settings');
	}

	/**
	 * Register meta fields for REST API
	 *
	 * @return void
	 */
	public function register_meta_fields(): void {
		$post_types = get_post_types(array('public' => true), 'names');
		foreach ($post_types as $post_type) {
			register_post_meta(
				$post_type,
				'hundred_words_news_post_summary',
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
					'auth_callback' => function() {
						return current_user_can('edit_posts');
					},
				)
			);

			register_post_meta(
				$post_type,
				'hundred_words_news_show_summary_icon',
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
					'auth_callback' => function() {
						return current_user_can('edit_posts');
					},
				)
			);

			register_post_meta(
				$post_type,
				'hundred_words_news_thunderbolt_news',
				array(
					'show_in_rest' => true,
					'single'       => true,
					'type'         => 'string',
					'auth_callback' => function() {
						return current_user_can('edit_posts');
					},
				)
			);
		}
	}

	/**
	 * Initialize REST API
	 *
	 * @return void
	 */
	public function init_rest_api(): void {
		$api = new API();
		$api->register_routes();
	}

	/**
	 * Initialize editor integrations
	 *
	 * @return void
	 */
	private function init_editors(): void {
		$gutenberg = new Gutenberg();
		$gutenberg->init();

		$classic = new Classic();
		$classic->init();
	}

	/**
	 * Initialize admin
	 *
	 * @return void
	 */
	private function init_admin(): void {
		if (is_admin()) {
			$settings_page = new SettingsPage();
			$settings_page->init();
		}
	}

	/**
	 * Initialize front-end
	 *
	 * @return void
	 */
	private function init_frontend(): void {
		if (! is_admin()) {
			$display = new Display();
			$display->init();

			$thunderbolt = new Thunderbolt();
			$thunderbolt->init();
		}
	}

	/**
	 * Initialize auto generator
	 *
	 * @return void
	 */
	private function init_auto_generator(): void {
		$auto_generator = new AutoGenerator();
		$auto_generator->init();
	}

	/**
	 * Load plugin textdomain
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'hundred-words-news',
			false,
			dirname(HUNDRED_WORDS_NEWS_PLUGIN_BASENAME) . '/languages'
		);
	}
}

