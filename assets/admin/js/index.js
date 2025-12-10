// Admin JavaScript entry point
// Works for both Classic and Gutenberg editors
import '../css/index.css';

if (typeof jQuery !== 'undefined') {
	jQuery(document).ready(function($) {
		// Support both classic and gutenberg editor containers
		const $container = $('#hwn-classic-editor, #hwn-gutenberg-editor');
		if ($container.length === 0) return;

		const postId = $container.data('post-id');
		const apiUrl = window.hundredWordsNewsAdmin?.apiUrl || '';
		const nonce = window.hundredWordsNewsAdmin?.nonce || '';

		// Generate summary
		$container.on('click', '.hwn-generate-summary, .hwn-regenerate-summary', function() {
			const $button = $(this);
			const length = $button.data('length') || 'medium';
			const language = $button.data('language') || 'en';
			const $status = $container.find('.hwn-summary-status');

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
						if (typeof tinymce !== 'undefined' && tinymce.get('hwn_post_summary')) {
							tinymce.get('hwn_post_summary').setContent(response.summary);
						} else {
							$('#hwn_post_summary').val(response.summary);
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

