// Frontend JavaScript entry point
import '../css/index.css';

(function() {
	'use strict';

	const apiUrl = window.aiBlogSummaryFrontend?.apiUrl || '';
	const nonce = window.aiBlogSummaryFrontend?.nonce || '';

	// Build share URLs
	function buildShareUrl(platform, url, title) {
		const encodedUrl = encodeURIComponent(url);
		const encodedTitle = encodeURIComponent(title);
		
		switch(platform) {
			case 'facebook':
				return `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`;
			case 'twitter':
				return `https://twitter.com/intent/tweet?url=${encodedUrl}&text=${encodedTitle}`;
			case 'whatsapp':
				return `https://wa.me/?text=${encodedTitle}%20${encodedUrl}`;
			case 'reddit':
				return `https://reddit.com/submit?url=${encodedUrl}&title=${encodedTitle}`;
			case 'email':
				return `mailto:?subject=${encodedTitle}&body=${encodedUrl}`;
			default:
				return url;
		}
	}

	// Handle share button clicks
	function handleShareClick(e, platform, url, title) {
		if (platform === 'link') {
			e.preventDefault();
			copyToClipboard(url);
			return false;
		}
		// For other platforms, let the link work normally (opens in new tab)
		return true;
	}

	// Copy link to clipboard
	function copyToClipboard(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			navigator.clipboard.writeText(text).then(() => {
				// Show feedback (you can add a toast notification here)
				const linkBtn = document.querySelector('.ai-summary-share-link');
				if (linkBtn) {
					const originalText = linkBtn.querySelector('span').textContent;
					linkBtn.querySelector('span').textContent = 'Copied!';
					setTimeout(() => {
						linkBtn.querySelector('span').textContent = originalText;
					}, 2000);
				}
			}).catch(() => {
				// Fallback for older browsers
				const textArea = document.createElement('textarea');
				textArea.value = text;
				document.body.appendChild(textArea);
				textArea.select();
				document.execCommand('copy');
				document.body.removeChild(textArea);
			});
		}
	}

	// Initialize popup
	function initPopup() {
		const popup = document.getElementById('ai-summary-popup');
		const overlay = popup?.querySelector('.ai-summary-popup-overlay');
		const closeBtn = popup?.querySelector('.ai-summary-popup-close');
		const body = popup?.querySelector('.ai-summary-popup-body');
		const title = popup?.querySelector('.ai-summary-popup-title');
		const date = popup?.querySelector('.ai-summary-popup-date');
		const image = popup?.querySelector('#ai-summary-popup-img');
		const readMoreBtn = popup?.querySelector('.ai-summary-popup-readmore');
		const shareContainer = popup?.querySelector('.ai-summary-popup-share');
		const shareButtons = popup?.querySelectorAll('.ai-summary-share-btn');
		const infoIcon = popup?.querySelector('.ai-summary-popup-info-icon');
		const linkBtn = popup?.querySelector('.ai-summary-share-link');

		if (!popup || !overlay || !closeBtn || !body || !title || !date || !readMoreBtn) return;

		function openPopup(summary, postId, postTitle, permalink, postDate, featuredImage, category) {
			// Populate popup content
			title.textContent = postTitle || '';
			date.textContent = category ? `${category} | Updated: ${postDate}` : `Updated: ${postDate}`;
			
			// Display summary as HTML (supports bullet points)
			// Decode HTML entities that were escaped for the HTML attribute
			const tempTextarea = document.createElement('textarea');
			tempTextarea.innerHTML = summary || '';
			body.innerHTML = tempTextarea.value || summary || '';
			
			readMoreBtn.href = permalink || '#';
			
			// Set read more button color from settings
			const readmoreColor = window.aiBlogSummaryFrontend?.readmoreButtonColor || '#dc2626';
			if (readMoreBtn) {
				readMoreBtn.style.backgroundColor = readmoreColor;
				// Set hover color (darker shade)
				readMoreBtn.style.setProperty('--readmore-hover-color', readmoreColor);
			}
			
			// Set featured image
			if (image && featuredImage) {
				image.src = featuredImage;
				image.alt = postTitle || '';
				image.style.display = 'block';
			} else if (image) {
				image.style.display = 'none';
			}

			// Update share button URLs
			if (shareButtons && permalink && postTitle) {
				shareButtons.forEach(btn => {
					const platform = btn.className.match(/ai-summary-share-(\w+)/)?.[1];
					if (platform && platform !== 'link') {
						const shareUrl = buildShareUrl(platform, permalink, postTitle);
						// Use setAttribute to ensure href is properly set
						btn.setAttribute('href', shareUrl);
						// Ensure the link opens in a new window
						btn.setAttribute('target', '_blank');
						btn.setAttribute('rel', 'noopener noreferrer');
						// Remove any event listeners that might prevent default
						btn.onclick = null;
						// Ensure pointer events are enabled
						btn.style.pointerEvents = 'auto';
						btn.style.cursor = 'pointer';
						console.log('Share URL set for', platform, ':', shareUrl);
					}
				});
			}

			// Show popup
			popup.setAttribute('aria-hidden', 'false');
			popup.classList.add('ai-summary-popup-active');
			document.body.style.overflow = 'hidden';

			// Track interaction
			if (postId && apiUrl && nonce) {
				fetch(apiUrl + 'track', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': nonce,
					},
					body: JSON.stringify({ post_id: postId }),
				}).catch(() => {
					// Silently fail if tracking fails
				});
			}
		}

		function closePopup() {
			popup.setAttribute('aria-hidden', 'true');
			popup.classList.remove('ai-summary-popup-active');
			document.body.style.overflow = '';
			
			// Reset content
			if (title) title.textContent = '';
			if (date) date.textContent = '';
			if (body) body.innerHTML = '';
			if (readMoreBtn) readMoreBtn.href = '#';
			if (image) {
				image.src = '';
				image.alt = '';
			}
		}

		// Close handlers
		overlay.addEventListener('click', closePopup);
		closeBtn.addEventListener('click', closePopup);

		// ESC key handler
		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && popup.classList.contains('ai-summary-popup-active')) {
				closePopup();
			}
		});

		// Share buttons hover effect - share icon is always visible, buttons appear on hover
		if (shareContainer) {
			const shareButtons = shareContainer.querySelector('.ai-summary-popup-share-buttons');
			let hideTimeout = null;
			
			// Show share buttons on hover over container (icon)
			shareContainer.addEventListener('mouseenter', function() {
				if (hideTimeout) {
					clearTimeout(hideTimeout);
					hideTimeout = null;
				}
				shareContainer.classList.add('ai-summary-popup-share-active');
			});
			
			// Hide share buttons when mouse leaves the container
			shareContainer.addEventListener('mouseleave', function(e) {
				const relatedTarget = e.relatedTarget;
				// Don't hide if moving to share buttons
				if (relatedTarget && shareButtons && shareButtons.contains(relatedTarget)) {
					return;
				}
				// Delay hiding to allow smooth transition
				hideTimeout = setTimeout(function() {
					shareContainer.classList.remove('ai-summary-popup-share-active');
				}, 150);
			});
			
			// Keep menu open when hovering over share buttons
			if (shareButtons) {
				shareButtons.addEventListener('mouseenter', function() {
					if (hideTimeout) {
						clearTimeout(hideTimeout);
						hideTimeout = null;
					}
					shareContainer.classList.add('ai-summary-popup-share-active');
				});
				
				shareButtons.addEventListener('mouseleave', function(e) {
					const relatedTarget = e.relatedTarget;
					// Don't hide if moving back to share icon
					if (relatedTarget && shareContainer.contains(relatedTarget)) {
						return;
					}
					hideTimeout = setTimeout(function() {
						shareContainer.classList.remove('ai-summary-popup-share-active');
					}, 150);
				});
			}
		}

		// Copy link button handler (set once, not in openPopup)
		if (linkBtn && readMoreBtn) {
			linkBtn.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				const currentUrl = readMoreBtn.href;
				if (currentUrl && currentUrl !== '#') {
					copyToClipboard(currentUrl);
				}
			});
		}

		// Ensure share buttons are clickable - verify they work when clicked
		if (shareButtons) {
			shareButtons.forEach(btn => {
				// Skip copy link button as it's handled separately
				if (btn.classList.contains('ai-summary-share-link')) {
					return;
				}
				
				// Add click handler to verify URL is set and allow navigation
				btn.addEventListener('click', function(e) {
					const href = this.getAttribute('href');
					// If href is not set or is just "#", prevent default
					if (!href || href === '#' || href === window.location.href + '#') {
						e.preventDefault();
						e.stopPropagation();
						console.warn('Share URL not set for:', this.className);
						return false;
					}
					// Otherwise, let the browser handle the link normally
					// The link should open in a new tab due to target="_blank"
				}, true); // Use capture phase
			});
		}

		// Ensure share buttons are clickable - set up click handlers for all share buttons
		if (shareButtons) {
			shareButtons.forEach(btn => {
				// Only handle copy link button specially
				if (btn.classList.contains('ai-summary-share-link')) {
					// Already handled above
					return;
				}
				
				// For other share buttons, ensure they work correctly
				btn.addEventListener('click', function(e) {
					// Check if href is valid (not just "#")
					const href = this.getAttribute('href');
					if (!href || href === '#' || href === window.location.href + '#') {
						e.preventDefault();
						e.stopPropagation();
						console.warn('Share URL not set for:', this.className);
						return false;
					}
					// Let the link work normally - it will open in new tab
					// Don't prevent default, just ensure href is set
				});
			});
		}

		// Info icon tooltip
		if (infoIcon) {
			infoIcon.addEventListener('mouseenter', function() {
				const tooltip = infoIcon.querySelector('.ai-summary-popup-tooltip');
				if (tooltip) {
					tooltip.classList.add('ai-summary-popup-tooltip-visible');
				}
			});
			infoIcon.addEventListener('mouseleave', function() {
				const tooltip = infoIcon.querySelector('.ai-summary-popup-tooltip');
				if (tooltip) {
					tooltip.classList.remove('ai-summary-popup-tooltip-visible');
				}
			});
		}

		// Handle icon clicks
		document.addEventListener('click', function(e) {
			const icon = e.target.closest('.ai-summary-icon');
			if (icon) {
				e.preventDefault();
				const summary = icon.getAttribute('data-summary');
				const postId = icon.getAttribute('data-post-id');
				const postTitle = icon.getAttribute('data-title');
				const permalink = icon.getAttribute('data-permalink');
				const postDate = icon.getAttribute('data-date');
				const featuredImage = icon.getAttribute('data-image');
				const category = icon.getAttribute('data-category');
				
				if (summary) {
					openPopup(summary, postId, postTitle, permalink, postDate, featuredImage, category);
				}
			}
		});

		// Handle keyboard navigation
		document.addEventListener('keydown', function(e) {
			const icon = document.activeElement;
			if (icon && icon.classList.contains('ai-summary-icon') && (e.key === 'Enter' || e.key === ' ')) {
				e.preventDefault();
				const summary = icon.getAttribute('data-summary');
				const postId = icon.getAttribute('data-post-id');
				const postTitle = icon.getAttribute('data-title');
				const permalink = icon.getAttribute('data-permalink');
				const postDate = icon.getAttribute('data-date');
				const featuredImage = icon.getAttribute('data-image');
				const category = icon.getAttribute('data-category');
				
				if (summary) {
					openPopup(summary, postId, postTitle, permalink, postDate, featuredImage, category);
				}
			}
		});
	}

	// Initialize when DOM is ready
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initPopup);
	} else {
		initPopup();
	}
})();
