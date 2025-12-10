<?php
/**
 * Plugin Name: News in 100 Words
 * Plugin URI: https://kahkashan.live/wordpress-plugins/hundred-words-news
 * Description: Automatically generates 100 words news for your WordPress posts.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: Kahkashan
 * Author URI: https://kahkashan.live
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:  
 * Domain Path: /languages
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('HUNDRED_WORDS_NEWS_VERSION', '1.0.0');
define('HUNDRED_WORDS_NEWS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('HUNDRED_WORDS_NEWS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('HUNDRED_WORDS_NEWS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once HUNDRED_WORDS_NEWS_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize plugin
if (class_exists('Hundred_Words_News\\Plugin')) {
    Hundred_Words_News\Plugin::get_instance();
}

