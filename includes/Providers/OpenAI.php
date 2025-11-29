<?php
/**
 * OpenAI Provider Implementation
 *
 * @package AI_Blog_Summary
 */

declare(strict_types=1);

namespace AI_Blog_Summary\Providers;

/**
 * OpenAI provider class
 */
class OpenAI implements ProviderInterface {

	/**
	 * API endpoint
	 *
	 * @var string
	 */
	private const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';

	/**
	 * API key
	 *
	 * @var string
	 */
	private string $api_key;

	/**
	 * Default model
	 *
	 * @var string
	 */
	private string $model;

	/**
	 * Timeout in seconds
	 *
	 * @var int
	 */
	private int $timeout;

	/**
	 * Constructor
	 *
	 * @param string $api_key API key.
	 * @param string $model Model name.
	 * @param int    $timeout Timeout in seconds.
	 */
	public function __construct(string $api_key, string $model = 'gpt-3.5-turbo', int $timeout = 30) {
		$this->api_key = $api_key;
		$this->model   = $model;
		$this->timeout = $timeout;
	}

	/**
	 * Generate summary from content
	 *
	 * @param string $content Content to summarize.
	 * @param array  $options Generation options.
	 * @return array Response with summary and metadata.
	 */
	public function generate_summary(string $content, array $options = []): array {
		$length   = $options['length'] ?? 'medium';
		$language = $options['language'] ?? 'en';

		// Map length to word count
		$word_counts = array(
			'short'  => 50,
			'medium' => 100,
			'large'  => 200,
		);
		$target_words = $word_counts[ $length ] ?? 100;

		// Build prompt - generate summary that will be split into bullet points
		$prompt = sprintf(
			'Please provide a concise summary of the following content in approximately %d words. Format the summary as 2-3 short paragraphs (each paragraph should be maximum 2 lines, approximately 20-25 words). Each paragraph should be a separate, complete thought. Language: %s. Write only the summary paragraphs, one per line, no additional text or numbering:',
			$target_words,
			$language
		);

		$messages = array(
			array(
				'role'    => 'system',
				'content' => 'You are a helpful assistant that creates concise, accurate summaries of blog posts. Format your response as 2-3 short paragraphs (each maximum 2 lines, approximately 20-25 words). Each paragraph should be a separate, complete thought. Keep summaries brief and focused on key points. Format paragraphs one per line when multiple paragraphs are requested.',
			),
			array(
				'role'    => 'user',
				'content' => $prompt . "\n\n" . $content,
			),
		);

		// Estimate tokens needed (roughly 4 characters per token)
		$estimated_tokens = $target_words * 1.5; // Allow some buffer for formatting

		$body = array(
			'model'       => $this->model,
			'messages'    => $messages,
			'temperature' => 0.7,
			'max_tokens'  => $estimated_tokens,
		);

		$response = $this->make_request($body);

		if (is_wp_error($response)) {
			return array(
				'success' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$summary = $response['choices'][0]['message']['content'] ?? '';

		return array(
			'success' => true,
			'summary' => trim($summary),
			'model'   => $this->model,
		);
	}

	/**
	 * Make API request with retry logic
	 *
	 * @param array $body Request body.
	 * @param int   $retries Number of retries.
	 * @return array|\WP_Error
	 */
	private function make_request(array $body, int $retries = 3) {
		$args = array(
			'method'  => 'POST',
			'timeout' => $this->timeout,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode($body),
		);

		$attempt = 0;
		while ( $attempt < $retries ) {
			$response = wp_remote_request(self::API_ENDPOINT, $args);

			if (is_wp_error($response)) {
				$attempt++;
				if ( $attempt < $retries ) {
					// Wait before retry (exponential backoff).
					sleep(pow(2, $attempt));
					continue;
				}
				return $response;
			}

			$status_code = wp_remote_retrieve_response_code($response);
			$body_data   = json_decode(wp_remote_retrieve_body($response), true);

			if (200 === $status_code) {
				return $body_data;
			}

			// Retry on 429 (rate limit) or 5xx errors.
			if (429 === $status_code || ( $status_code >= 500 && $status_code < 600 )) {
				$attempt++;
				if ( $attempt < $retries ) {
					sleep(pow(2, $attempt));
					continue;
				}
			}

			$error_message = $body_data['error']['message'] ?? 'Unknown error';
			return new \WP_Error('api_error', $error_message);
		}

		return new \WP_Error('max_retries', 'Maximum retries exceeded');
	}

	/**
	 * Get provider name
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'OpenAI';
	}

	/**
	 * Get available models
	 *
	 * @return array
	 */
	public function get_models(): array {
		return array(
			'gpt-4'         => 'GPT-4',
			'gpt-4-turbo'   => 'GPT-4 Turbo',
			'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
		);
	}

	/**
	 * Validate API key
	 *
	 * @param string $api_key API key to validate.
	 * @return bool
	 */
	public function validate_api_key(string $api_key): bool {
		if (empty($api_key)) {
			return false;
		}

		// Simple format check for OpenAI keys.
		return preg_match('/^sk-[a-zA-Z0-9]{32,}$/', $api_key) === 1;
	}
}

