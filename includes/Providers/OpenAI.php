<?php
/**
 * OpenAI Provider Implementation
 *
 * @package Hundred_Words_News
 */

declare(strict_types=1);

namespace Hundred_Words_News\Providers;

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

		// Map length to number of bullet points
		$bullet_counts = array(
			'short'  => 1,
			'medium' => 2,
			'large'  => 3,
		);
		$target_bullets = $bullet_counts[ $length ] ?? 2;

		// Build prompt - request specific number of bullet points
		$bullet_text = $target_bullets === 1 ? 'ONE bullet point' : ($target_bullets === 2 ? 'TWO bullet points' : 'THREE bullet points');
		$prompt = sprintf(
			'Please provide a concise summary of the following content. Generate exactly %s. Each bullet point MUST be concise and MUST NOT exceed TWO lines of text. The summary should capture the key insights, major events, or important takeaways from the post. Language: %s. Return only the bullet points, one per line, with bullet point markers (- or •):',
			strtoupper($bullet_text),
			$language
		);

		$bullet_text = $target_bullets === 1 ? 'ONE bullet point' : ($target_bullets === 2 ? 'TWO bullet points' : 'THREE bullet points');
		$messages = array(
			array(
				'role'    => 'system',
				'content' => 'You are an expert content summarizer. 

When a blog post or article is provided, generate exactly ' . strtoupper($bullet_text) . '.  

Each bullet point MUST be concise and MUST NOT exceed TWO lines of text.  

The summary should capture the key insights, major events, or important takeaways from the post.  

Do not add extra commentary, do not exceed the line limit, and do not change the meaning of the original content.

Return only the bullet points.',
			),
			array(
				'role'    => 'user',
				'content' => $prompt . "\n\n" . $content,
			),
		);

		// Estimate tokens needed (roughly 4 characters per token)
		// Each bullet point ~50-100 tokens, so 3 bullets = ~300 tokens max
		$estimated_tokens = 300;

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
		$summary = trim($summary);

		// Format summary as bullet points
		// Split by newlines and format each line as a bullet point
		$lines = preg_split('/\r\n|\r|\n/', $summary);
		$bullet_points = array();
		
		foreach ($lines as $line) {
			$line = trim($line);
			if (empty($line)) {
				continue;
			}
			
			// Remove existing bullet markers if present
			$line = preg_replace('/^[\-\•\*]\s*/', '', $line);
			$line = trim($line);
			
			if (!empty($line)) {
				$bullet_points[] = $line;
			}
		}

		// Ensure we have at least some bullet points
		if (empty($bullet_points)) {
			// Fallback: use the original summary
			$bullet_points = array($summary);
		}

		// Limit to target number of bullets (take first N)
		$bullet_points = array_slice($bullet_points, 0, $target_bullets);

		// Format as bullet list (stored as HTML)
		$formatted_summary = '<ul>';
		foreach ($bullet_points as $point) {
			$formatted_summary .= '<li>' . esc_html($point) . '</li>';
		}
		$formatted_summary .= '</ul>';

		return array(
			'success' => true,
			'summary' => $formatted_summary,
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

