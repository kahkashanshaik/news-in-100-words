<?php
/**
 * Main Plugin Class
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary;

use AI_Blog_Summary\Editors\Gutenberg;
use AI_Blog_Summary\Editors\Classic;
use AI_Blog_Summary\REST\API;
use AI_Blog_Summary\Admin\SettingsPage;
use AI_Blog_Summary\Frontend\Display;
use AI_Blog_Summary\AutoGenerator;

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
	 * Register meta fields for REST API
	 *
	 * @return void
	 */
	public function register_meta_fields(): void {
		$post_types = get_post_types(array('public' => true), 'names');
		foreach ($post_types as $post_type) {
			register_post_meta(
				$post_type,
				'ai_post_summary',
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
				'ai_show_summary_icon',
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
			'ai-blog-summary',
			false,
			dirname(AI_BLOG_SUMMARY_PLUGIN_BASENAME) . '/languages'
		);
	}
}

