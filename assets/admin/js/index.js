// Admin JavaScript entry point
import '../css/index.css';
import { registerPlugin } from '@wordpress/plugins';
import SummaryPanel from './components/SummaryPanel';

// Register Gutenberg sidebar panel
registerPlugin('ai-blog-summary-panel', {
	render: SummaryPanel,
	icon: 'lightning',
});

// Classic Editor functionality
if (typeof jQuery !== 'undefined') {
	jQuery(document).ready(function($) {
		const $container = $('#ai-blog-summary-classic-editor');
		if ($container.length === 0) return;

		const postId = $container.data('post-id');
		const apiUrl = window.aiBlogSummary?.apiUrl || '';
		const nonce = window.aiBlogSummary?.nonce || '';

		// Generate summary
		$container.on('click', '.ai-generate-summary, .ai-regenerate-summary', function() {
			const $button = $(this);
			const length = $button.data('length') || 'medium';
			const language = $button.data('language') || 'en';
			const $status = $container.find('.ai-summary-status');

			$button.prop('disabled', true);
			$status.html('<span class="spinner is-active"></span> ' + 'Generating...');

			$.ajax({
				url: apiUrl + 'generate',
				method: 'POST',
				headers: {
					'X-WP-Nonce': nonce,
				},
				data: {
					post_id: postId,
					length: length,
					language: language,
				},
				success: function(response) {
					if (response.success) {
						if (typeof tinymce !== 'undefined' && tinymce.get('ai_post_summary')) {
							tinymce.get('ai_post_summary').setContent(response.summary);
						} else {
							$('#ai_post_summary').val(response.summary);
						}
						$status.html('<span style="color: green;">Summary generated successfully!</span>');
					} else {
						$status.html('<span style="color: red;">Error: ' + (response.message || 'Failed to generate summary') + '</span>');
					}
				},
				error: function(xhr) {
					$status.html('<span style="color: red;">Error: ' + (xhr.responseJSON?.message || 'Request failed') + '</span>');
				},
				complete: function() {
					$button.prop('disabled', false);
				},
			});
		});
	});
}

