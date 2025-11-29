<?php
/**
 * Plugin Name: AI Blog Summary Generator
 * Plugin URI: https://example.com/ai-blog-summary
 * Description: Automatically generates AI-powered summaries for WordPress posts with universal editor support and front-end display.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://kahkashan.live
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-blog-summary
 * Domain Path: /languages
 */

declare(strict_types=1);

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_BLOG_SUMMARY_VERSION', '1.0.0');
define('AI_BLOG_SUMMARY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_BLOG_SUMMARY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_BLOG_SUMMARY_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
require_once AI_BLOG_SUMMARY_PLUGIN_DIR . 'vendor/autoload.php';

// Initialize plugin
if (class_exists('AI_Blog_Summary\\Plugin')) {
    AI_Blog_Summary\Plugin::get_instance();
}

