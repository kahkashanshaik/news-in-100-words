import { useState, useEffect } from 'react';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TextareaControl, Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SummaryPanel = () => {
	const [summary, setSummary] = useState(window.aiBlogSummary?.summary || '');
	const [isGenerating, setIsGenerating] = useState(false);
	const [showIcon, setShowIcon] = useState(window.aiBlogSummary?.showIcon !== false);
	const [status, setStatus] = useState('');

	const generateSummary = async (regenerate = false) => {
		if (isGenerating) return;

		setIsGenerating(true);
		setStatus('');

		try {
			const response = await fetch(`${window.aiBlogSummary.apiUrl}generate`, {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': window.aiBlogSummary.nonce,
				},
				body: JSON.stringify({
					post_id: window.aiBlogSummary.postId,
					length: window.aiBlogSummary.defaultLength,
					language: window.aiBlogSummary.defaultLanguage,
				}),
			});

			const data = await response.json();

			if (data.success) {
				setSummary(data.summary);
				setStatus(__('Summary generated successfully!', 'ai-blog-summary'));
			} else {
				setStatus(__('Error: ', 'ai-blog-summary') + (data.message || __('Failed to generate summary', 'ai-blog-summary')));
			}
		} catch (error) {
			setStatus(__('Error: ', 'ai-blog-summary') + error.message);
		} finally {
			setIsGenerating(false);
		}
	};

	const handleSummaryChange = (value) => {
		setSummary(value);
		// Save to postmeta via WordPress data store
		const { dispatch } = wp.data;
		dispatch('core/editor').editPost({
			meta: {
				...wp.data.select('core/editor').getEditedPostAttribute('meta'),
				ai_post_summary: value,
			},
		});
	};

	const handleShowIconChange = (checked) => {
		setShowIcon(checked);
		// Save to postmeta via WordPress data store
		const { dispatch } = wp.data;
		dispatch('core/editor').editPost({
			meta: {
				...wp.data.select('core/editor').getEditedPostAttribute('meta'),
				ai_show_summary_icon: checked ? '1' : '0',
			},
		});
	};

	return (
		<PluginDocumentSettingPanel
			name="ai-blog-summary-panel"
			title={__('AI Blog Summary', 'ai-blog-summary')}
		>
			<TextareaControl
				label={__('Summary', 'ai-blog-summary')}
				value={summary}
				onChange={handleSummaryChange}
				rows={5}
			/>

			<div style={{ marginTop: '10px', display: 'flex', gap: '10px' }}>
				<Button
					variant="secondary"
					onClick={() => generateSummary(false)}
					disabled={isGenerating}
				>
					{isGenerating ? <Spinner /> : __('Generate Summary', 'ai-blog-summary')}
				</Button>
				<Button
					variant="secondary"
					onClick={() => generateSummary(true)}
					disabled={isGenerating}
				>
					{isGenerating ? <Spinner /> : __('Regenerate', 'ai-blog-summary')}
				</Button>
			</div>

			<div style={{ marginTop: '10px' }}>
				<label>
					<input
						type="checkbox"
						checked={showIcon}
						onChange={(e) => handleShowIconChange(e.target.checked)}
					/>
					{__('Show summary icon on front-end', 'ai-blog-summary')}
				</label>
			</div>

			{status && (
				<div style={{ marginTop: '10px', color: status.includes('Error') ? 'red' : 'green' }}>
					{status}
				</div>
			)}
		</PluginDocumentSettingPanel>
	);
};

export default SummaryPanel;

