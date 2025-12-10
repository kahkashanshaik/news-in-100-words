<?php
/**
 * AI Provider Interface
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News\Providers;

/**
 * Provider interface for AI services
 */
interface ProviderInterface {

	/**
	 * Generate summary from content
	 *
	 * @param string $content Content to summarize.
	 * @param array  $options Generation options (length, language, etc.).
	 * @return array Response with summary and metadata.
	 */
	public function generate_summary(string $content, array $options = []): array;

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	public function get_name(): string;

	/**
	 * Get available models
	 *
	 * @return array
	 */
	public function get_models(): array;

	/**
	 * Validate API key
	 *
	 * @param string $api_key API key to validate.
	 * @return bool
	 */
	public function validate_api_key(string $api_key): bool;
}

