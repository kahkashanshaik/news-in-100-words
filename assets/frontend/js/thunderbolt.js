// Thunderbolt JavaScript entry point
import '../css/thunderbolt.css';

(function ($) {
	$(document).ready(function () {
		let swiper;

		// Function to initialize Swiper based on screen width
		function initSwiper() {
			const screenWidth = $(window).width();

			// Destroy existing instance if it exists to allow clean reconfiguration
			if (swiper) {
				swiper.destroy(true, true);
			}

			if (screenWidth < 768) {
				// MOBILE: Vertical Inshorts-style
				console.log("Initializing Mobile Vertical Slider");
				swiper = new Swiper(".mySwiper", {
					direction: "vertical",
					slidesPerView: 1,
					spaceBetween: 0,
					mousewheel: true,
					// pagination: {
					//     el: ".swiper-pagination",
					//     clickable: true,
					//     type: "fraction", // Dots
					// },
					effect: "slide", // Simple clean slide
				});
			} else if (screenWidth >= 768 && screenWidth <= 1024) {
				// TABLET: Horizontal Full Width
				console.log("Initializing Tablet Slider");
				swiper = new Swiper(".mySwiper", {
					direction: "horizontal",
					slidesPerView: 1, // Single card
					spaceBetween: 20,
					loop: true,
					navigation: {
						nextEl: ".swiper-button-next",
						prevEl: ".swiper-button-prev",
					},
					pagination: {
						el: ".swiper-pagination",
						clickable: true,
					},
				});
			} else {
				// DESKTOP: Center Mode with cut-off sides
				console.log("Initializing Desktop Slider");
				swiper = new Swiper(".mySwiper", {
					direction: "horizontal",
					slidesPerView: 3, // Allows custom CSS width
					centeredSlides: true,
					spaceBetween: 80, // Moderate space to keep neighbors visible
					loop: false,
					grabCursor: false,
					navigation: {
						nextEl: ".swiper-button-next",
						prevEl: ".swiper-button-prev",
					},
					keyboard: {
						enabled: true,
					},
					// Optional: Autoplay
					// autoplay: {
					//   delay: 3000,
					//   disableOnInteraction: false,
					// },
				});
			}
		}

		// Initial load
		initSwiper();

		// Re-initialize on resize
		// Debounce to prevent too many re-inits during drag resize
		let resizeTimer;
		$(window).on('resize', function () {
			clearTimeout(resizeTimer);
			resizeTimer = setTimeout(function () {
				initSwiper();
			}, 200);
		});

		// Share functionality
		function initShareButtons() {
			const isMobile = $(window).width() < 768;

			// Handle share icon click
			$(document).on('click', '.thunderbolt-card-share-icon', function (e) {
				e.preventDefault();
				e.stopPropagation();
				
				const $shareContainer = $(this).closest('.thunderbolt-card-share');
				const postUrl = $shareContainer.data('post-url');
				const postTitle = $shareContainer.data('post-title');

				if (isMobile && navigator.share) {
					// Use native share API on mobile
					navigator.share({
						title: postTitle,
						url: postUrl
					}).catch(function (error) {
						console.log('Error sharing:', error);
					});
				} else {
					// Toggle share popup on desktop
					$('.thunderbolt-card-share').removeClass('active');
					$shareContainer.toggleClass('active');
				}
			});

			// Close share popup when clicking outside
			$(document).on('click', function (e) {
				if (!$(e.target).closest('.thunderbolt-card-share').length) {
					$('.thunderbolt-card-share').removeClass('active');
				}
			});

			// Handle copy link functionality
			$(document).on('click', '.thunderbolt-share-link', function (e) {
				e.preventDefault();
				const url = $(this).data('url');
				
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(url).then(function () {
						// Show feedback (you can customize this)
						const $btn = $(e.target).closest('.thunderbolt-share-link');
						const originalText = $btn.find('span').text();
						$btn.find('span').text('Copied!');
						setTimeout(function () {
							$btn.find('span').text(originalText);
						}, 2000);
					}).catch(function (err) {
						console.error('Failed to copy:', err);
						// Fallback for older browsers
						fallbackCopyTextToClipboard(url);
					});
				} else {
					// Fallback for older browsers
					fallbackCopyTextToClipboard(url);
				}
				
				// Close popup after copying
				$('.thunderbolt-card-share').removeClass('active');
			});
		}

		// Fallback copy function for older browsers
		function fallbackCopyTextToClipboard(text) {
			const textArea = document.createElement("textarea");
			textArea.value = text;
			textArea.style.top = "0";
			textArea.style.left = "0";
			textArea.style.position = "fixed";
			document.body.appendChild(textArea);
			textArea.focus();
			textArea.select();
			
			try {
				const successful = document.execCommand('copy');
				if (successful) {
					const $linkBtn = $('.thunderbolt-share-link');
					const originalText = $linkBtn.find('span').text();
					$linkBtn.find('span').text('Copied!');
					setTimeout(function () {
						$linkBtn.find('span').text(originalText);
					}, 2000);
				}
			} catch (err) {
				console.error('Fallback: Oops, unable to copy', err);
			}
			
			document.body.removeChild(textArea);
		}

		// Initialize share buttons
		initShareButtons();
	});
})(jQuery);