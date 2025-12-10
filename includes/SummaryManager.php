<?php
/**
 * Summary Manager
 *
 * Handles postmeta operations for summaries
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News;

/**
 * Summary Manager class
 */
class SummaryManager {

	/**
	 * Postmeta keys
	 */
	private const META_SUMMARY           = '_hundred_words_news_post_summary';
	private const META_SUMMARY_VARIANTS  = '_hundred_words_news_post_summary_variants';
	private const META_SUMMARY_CLICKS    = '_hundred_words_news_summary_clicks';
	private const META_SUMMARY_LANGUAGE  = '_hundred_words_news_summary_language';
	private const META_SUMMARY_GENERATED = '_hundred_words_news_summary_generated_at';
	private const META_SHOW_ICON         = '_hundred_words_news_show_summary_icon';

	/**
	 * Get summary for post
	 *
	 * @param int $post_id Post ID.
	 * @return string|null
	 */
	public function get_summary(int $post_id): ?string {
		$summary = get_post_meta($post_id, self::META_SUMMARY, true);
		return $summary ?: null;
	}

	/**
	 * Save summary for post
	 *
	 * @param int    $post_id Post ID.
	 * @param string $summary Summary text.
	 * @return bool
	 */
	public function save_summary(int $post_id, string $summary): bool {
		$result = update_post_meta($post_id, self::META_SUMMARY, $summary);
		if ($result) {
			update_post_meta($post_id, self::META_SUMMARY_GENERATED, current_time('mysql'));
		}
		return (bool) $result;
	}

	/**
	 * Get summary variants
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_variants(int $post_id): array {
		$variants = get_post_meta($post_id, self::META_SUMMARY_VARIANTS, true);
		return is_array($variants) ? $variants : array();
	}

	/**
	 * Save summary variant
	 *
	 * @param int    $post_id Post ID.
	 * @param string $variant Variant text.
	 * @return bool
	 */
	public function save_variant(int $post_id, string $variant): bool {
		$variants   = $this->get_variants($post_id);
		$variants[] = $variant;
		return update_post_meta($post_id, self::META_SUMMARY_VARIANTS, $variants);
	}

	/**
	 * Get click count for post
	 *
	 * @param int $post_id Post ID.
	 * @return int
	 */
	public function get_clicks(int $post_id): int {
		return (int) get_post_meta($post_id, self::META_SUMMARY_CLICKS, true);
	}

	/**
	 * Increment click count
	 *
	 * @param int $post_id Post ID.
	 * @return int New count.
	 */
	public function increment_clicks(int $post_id): int {
		$current = $this->get_clicks($post_id);
		$new     = $current + 1;
		update_post_meta($post_id, self::META_SUMMARY_CLICKS, $new);
		return $new;
	}

	/**
	 * Get summary language
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public function get_language(int $post_id): string {
		return get_post_meta($post_id, self::META_SUMMARY_LANGUAGE, true) ?: 'en';
	}

	/**
	 * Save summary language
	 *
	 * @param int    $post_id Post ID.
	 * @param string $language Language code.
	 * @return bool
	 */
	public function save_language(int $post_id, string $language): bool {
		return (bool) update_post_meta($post_id, self::META_SUMMARY_LANGUAGE, $language);
	}

	/**
	 * Get generated timestamp
	 *
	 * @param int $post_id Post ID.
	 * @return string|null
	 */
	public function get_generated_at(int $post_id): ?string {
		return get_post_meta($post_id, self::META_SUMMARY_GENERATED, true) ?: null;
	}

	/**
	 * Check if post has summary
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function has_summary(int $post_id): bool {
		return ! empty($this->get_summary($post_id));
	}

	/**
	 * Get show icon setting for post
	 *
	 * @param int $post_id Post ID.
	 * @return bool
	 */
	public function should_show_icon(int $post_id): bool {
		$show = get_post_meta($post_id, self::META_SHOW_ICON, true);
		// Default to true if not set.
		return '' === $show ? true : (bool) $show;
	}

	/**
	 * Set show icon setting for post
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $show Whether to show icon.
	 * @return bool
	 */
	public function set_show_icon(int $post_id, bool $show): bool {
		return update_post_meta($post_id, self::META_SHOW_ICON, $show);
	}

	/**
	 * Get global stats (calculated from postmeta)
	 *
	 * @return array
	 */
	public function get_global_stats(): array {
		global $wpdb;

		$total_posts_with_summary = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
				self::META_SUMMARY
			)
		);

		$total_clicks = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM {$wpdb->postmeta} WHERE meta_key = %s",
				self::META_SUMMARY_CLICKS
			)
		);

		return array(
			'total_posts_with_summary' => (int) $total_posts_with_summary,
			'total_clicks'             => (int) $total_clicks,
		);
	}
}

