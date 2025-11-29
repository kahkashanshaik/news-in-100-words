# AI Blog Summary Generator

WordPress plugin for automatically generating AI-powered summaries for blog posts.

## Setup

### Prerequisites

- Node.js 18+ and npm
- PHP 7.4+
- Composer
- WordPress 5.9+

### Installation

1. Install PHP dependencies:
```bash
composer install
```

2. Install Node.js dependencies:
```bash
npm install
```

3. Build assets for production:
```bash
npm run build
```

4. For development with hot module replacement:
```bash
npm run dev
```

### Building for Distribution

To prepare the plugin for distribution (removes dev dependencies, optimizes autoloader):

```bash
./build.sh
```

This will:
- Install only production dependencies (removes PHPUnit, WPCS, etc.)
- Optimize the Composer autoloader
- Build production assets
- Reduce vendor folder from ~27MB to ~84KB

**Note:** The plugin uses PSR-4 autoloading via Composer. The optimized `vendor/` folder (~84KB) is included in distribution and contains only the autoloader files needed for the plugin to function.

## Development

### Available Scripts

- `npm run dev` - Start development server with HMR on localhost:3000
- `npm run build` - Build production assets
- `npm run watch` - Build and watch for changes
- `npm run preview` - Preview production build

### Project Structure

```
ai-blog-summary/
├── assets/
│   ├── admin/
│   │   ├── css/
│   │   └── js/
│   └── frontend/
│       ├── css/
│       └── js/
├── includes/          # PHP classes (PSR-4 autoloaded)
├── tests/             # PHPUnit tests
├── ai-blog-summary.php # Main plugin file
├── composer.json      # PHP dependencies
├── package.json       # Node.js dependencies
└── vite.config.js     # Vite configuration
```

### Building Assets

Assets are compiled using Vite:
- Entry points: `assets/admin/js/index.js` and `assets/frontend/js/index.js`
- Output: `dist/` directory
- CSS is automatically extracted and optimized
- JavaScript is bundled and minified for production

## Testing

### PHP Tests

Run PHPUnit tests:
```bash
composer test
```

### JavaScript Tests

Run Vitest tests:
```bash
npm test
```

## License

GPL-2.0-or-later

