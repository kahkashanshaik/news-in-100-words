=== News in 100 Words ===
Contributors: kahkashan
Tags: hundred-word-news, ai, gutenberg, classic-editor, openai
Requires at least: 5.9
Tested up to: 6.5 < 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://kahkashan.live

Automatically generates AI-powered 100-word news summaries for WordPress posts with universal editor support, beautiful front-end display, and Thunderbolt news carousel feature.

== Description ==

News in 100 Words is a powerful WordPress plugin that automatically generates concise AI-powered summaries for your blog posts and news articles. Perfect for news websites, blogs, and content publishers who want to provide quick, engaging summaries to their readers.

**Key Features:**

* **AI-Powered Summary Generation**
  * Uses OpenAI's advanced language models (GPT-3.5 Turbo, GPT-4, GPT-4 Turbo)
  * Generates concise, accurate summaries automatically
  * Supports multiple summary lengths (1, 2, or 3 paragraphs)
  * Intelligent content extraction and summarization

* **Lightning Bolt Icon Display**
  * Eye-catching lightning bolt icon (⚡) appears next to post titles
  * Fully customizable size (Small, Medium, Large)
  * Customizable color to match your theme
  * Per-post control to show/hide icon
  * Responsive design that works on all devices

* **Beautiful Popup Modal**
  * Elegant popup displays summaries with smooth animations
  * Shows featured image, post title, date, and category
  * Light/Dark/Auto theme support
  * Fully responsive and mobile-friendly
  * Accessible with ARIA labels and keyboard navigation
  * Customizable colors for buttons and bullet points

* **Thunderbolt News Feature**
  * Full-page news carousel for showcasing featured articles
  * Swipeable card-based design with Swiper.js
  * Beautiful animations and transitions
  * Customizable logo, colors, and typography
  * Navigation arrows with customizable position
  * Share buttons for social media (Facebook, Twitter, WhatsApp, Reddit, Email)
  * Responsive design for all screen sizes

* **Universal Editor Support**
  * Works seamlessly with Gutenberg Block Editor
  * Full support for Classic Editor (TinyMCE)
  * Meta box appears automatically for all supported post types

* **Multi-Language Support**
  * Generate summaries in multiple languages:
    * English
    * Kannada (ಕನ್ನಡ)
  * send a request or comment to add new language

* **Future Enhancements: Interaction Tracking**
  * Track clicks on summary icons
  * Per-post engagement metrics
  * Global statistics dashboard
  * REST API endpoints for custom analytics

* **Comprehensive Settings**
  * API configuration and model selection
  * Summary generation preferences
  * Icon customization (size, color)
  * Popup styling (theme, colors, fonts)
  * Thunderbolt page customization:
    * Logo and branding
    * Background colors
    * Typography settings
    * Navigation controls
    * Card styling
  * Auto-generation toggle
  * Rate limiting and timeout settings

* **Auto-Generation**
  * Automatically generate summaries when posts are published
  * Only generates if no summary exists
  * Respects API rate limits with built-in delays
  * Can be disabled for manual generation only

* **Customizable Summary Length**
  * Short (1 paragraph) - Quick, concise summaries
  * Medium (2 paragraphs) - Balanced detail and brevity
  * Large (3 paragraphs) - Comprehensive summaries
  * Formatted as bullet points for easy reading

* **Security & Performance**
  * Secure API key storage
  * Nonce verification for all requests
  * Proper data sanitization and escaping
  * Optimized database queries
  * Minimal front-end overhead
  * Cached summaries for fast loading

* **Accessibility**
  * ARIA labels and roles
  * Keyboard navigation support
  * Screen reader friendly
  * High contrast support
  * Semantic HTML structure

**Perfect For:**
* News websites and online magazines
* Blog publishers who want quick summaries
* Content creators looking to improve engagement
* Websites wanting to showcase featured news

== Installation ==

= Step 1: Install the Plugin =

**Automatic Installation (Recommended):**

1. Log in to your WordPress admin dashboard
2. Navigate to **Plugins** → **Add New**
3. In the search box, type "News in 100 Words"
4. Click **Install Now** when you see the plugin
5. After installation, click **Activate Plugin**

**Manual Installation:**

1. Download the plugin ZIP file from WordPress.org
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins** → **Add New**
4. Click **Upload Plugin** button at the top
5. Click **Choose File** and select the downloaded ZIP file
6. Click **Install Now**
7. After installation completes, click **Activate Plugin**

= Step 2: Configure API Settings =

1. After activation, you'll see a new menu item **"Hundred Words News"** in your WordPress admin sidebar
2. Click on **Hundred Words News** to open the settings page
3. Navigate to the **API & Model** tab
4. Get your OpenAI API key:
   * Visit https://platform.openai.com/api-keys
   * Sign up or log in to your OpenAI account
   * Click **"Create new secret key"**
   * Copy the API key (you won't be able to see it again)
5. Paste your API key into the **API Key** field in the plugin settings
6. Select your preferred AI model:
   * **GPT-3.5 Turbo** - Fast and cost-effective (recommended for most users)
   * **GPT-4** - More accurate but slower and more expensive
   * **GPT-4 Turbo** - Best balance of speed and accuracy
7. Configure timeout settings (default: 30 seconds)
8. Set API delay to avoid rate limits (default: 500ms)
9. Click **Save Changes**

= Step 3: Configure Summary Settings =

1. Click on the **Summary Generation** tab
2. Set your **Default Length**:
   * **1 paragraph** - Short, concise summaries
   * **2 paragraphs** - Medium-length summaries (recommended)
   * **3 paragraphs** - Longer, detailed summaries
3. Select your **Default Language**:
   * English, Arabic, Hindi, or Kannada
4. Enable **Auto-Generate** if you want summaries created automatically when posts are published
5. Click **Save Changes**

= Step 4: Customize Display Settings =

1. **Icon Settings Tab:**
   * Choose icon size (Small, Medium, Large)
   * Select icon color using the color picker
   
2. **Popup Settings Tab:**
   * Choose popup theme (Light, Dark, or Auto)
   * Customize "Read More" button color
   * Set list bullet point color
   
3. **Thunderbolt Page Tab (Optional):**
   * Upload logo image URL
   * Choose theme (Dark, Light, or Auto)
   * Customize background colors
   * Configure typography settings
   * Set navigation arrow position and colors
   
4. Click **Save Changes** after each customization

= Step 5: Generate Your First Summary =

1. Edit any existing post or create a new post
2. In the post editor (Gutenberg or Classic), scroll down to find the **"Hundred Words News"** meta box
3. Click the **"Generate News"** button
4. Wait a few seconds for the AI to generate the summary
5. Review the generated summary in the editor
6. Edit the summary if needed (it's fully editable)
7. Check the box **"Show news icon on front-end"** to display the lightning bolt icon
8. Optionally check **"Add news to thunderbolt"** to include this post in the Thunderbolt carousel
9. Publish or update your post
10. Visit your website's front-end to see the lightning bolt icon (⚡) next to the post title
11. Click the icon to see the beautiful popup with your summary

= Step 6: Create Thunderbolt News Page (Optional) =

1. Create a new page in WordPress
2. Add the shortcode `[thunderbolt_news]` to the page content
3. Optionally customize the shortcode:
   * `[thunderbolt_news posts="10"]` - Show 10 posts
   * `[thunderbolt_news posts="5" orderby="date" order="DESC"]` - Custom query
4. Publish the page
5. Visit the page to see your Thunderbolt news carousel
6. Only posts with "Add news to thunderbolt" checked will appear

== Frequently Asked Questions ==

= Do I need an OpenAI API key? =

Yes, you need an OpenAI API key to generate summaries. You can get one for free (with credits) from https://platform.openai.com/api-keys

= Does this work with all WordPress editors? =

Yes! The plugin works with Gutenberg Block Editor, Classic Editor, and all major page builders including Elementor, Divi, WPBakery, and more.

= Can I customize the appearance? =

Absolutely! You can customize icon size, color, popup theme, button colors, and Thunderbolt page styling through the settings page.

= What languages are supported? =

Currently supports English, Arabic, Hindi, and Kannada. More languages can be added based on demand.

= Can I edit the generated summaries? =

Yes! Generated summaries are fully editable in the WordPress editor before publishing.

= What is Thunderbolt News? =

Thunderbolt News is a full-page news carousel feature that displays multiple news articles in a beautiful, swipeable format. Perfect for showcasing featured news on a dedicated page.

= Does the plugin slow down my site? =

No, the plugin is optimized for performance. Summary generation happens asynchronously and summaries are cached. The front-end display adds minimal overhead.

= Can I use this with custom post types? =

Yes, the plugin automatically supports all public post types that have an editor.

= Is there a limit on how many summaries I can generate? =

The limit depends on your OpenAI API plan. The plugin includes rate limiting and retry logic to handle API limits gracefully.

= Can I disable auto-generation? =

Yes, you can disable auto-generation in the settings and only generate summaries manually when needed.

== Screenshots ==

1. Admin settings page with API configuration
2. Gutenberg editor meta box with summary generation
3. Classic editor meta box
4. Front-end lightning bolt icon display
5. Summary popup modal
6. Thunderbolt news carousel page
7. Settings - Icon customization
8. Settings - Popup customization
9. Settings - Thunderbolt page customization

== Changelog ==

= 1.0.0 =
* Initial release
* AI-powered summary generation with OpenAI integration
* Universal editor support (Gutenberg and Classic)
* Lightning bolt icon display on front-end
* Beautiful popup modal with animations
* Thunderbolt news carousel feature
* Multi-language support (English, Arabic, Hindi, Kannada)
* Comprehensive admin settings page
* Auto-generation on post publish
* Interaction tracking
* Customizable styling and colors
* Responsive design
* Accessibility features

== Upgrade Notice ==

= 1.0.0 =
Initial release of News in 100 Words. Install and configure your OpenAI API key to start generating summaries.

== Developer Notes ==

This plugin uses:
* PSR-4 autoloading via Composer
* WordPress REST API for AJAX operations
* Modern JavaScript (ES6+) compiled with Vite
* Tailwind CSS for styling
* WordPress Coding Standards

For developers: The plugin follows WordPress best practices and is fully extensible through hooks and filters.

