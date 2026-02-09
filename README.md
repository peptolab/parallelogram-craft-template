# Craft CMS 5 Skeleton

A starter project for Craft CMS 5 with a webpack build pipeline, SCSS, and [@parallelogram-js/core](https://www.npmjs.com/package/@parallelogram-js/core).

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+
- MySQL 8.0+ or PostgreSQL 13+

## Quick Start

```bash
git clone <repo-url> my-site
cd my-site
make setup
```

This will:
1. Install Composer and npm dependencies
2. Copy `.env.example.dev` to `.env`
3. Run the Craft setup wizard (database, admin account)

## Commands

| Command | Description |
|---------|-------------|
| `make install` | Install Composer and npm dependencies |
| `make setup` | Full setup (install + env + Craft wizard) |
| `make dev` | Watch for changes with webpack (development mode) |
| `make build` | Production build |

Or use npm directly:

```bash
npm run dev      # Watch mode (development)
npm run build    # Production build
```

## Project Structure

```
├── asset/                  # Source assets (JS/SCSS)
│   ├── js/
│   │   ├── core/
│   │   │   └── App.js      # Core app bootstrap
│   │   └── app.js           # Main entry point
│   └── css/
│       ├── app.scss          # Main SCSS entry
│       ├── _variables.scss   # SCSS variables
│       ├── _mixins.scss      # SCSS mixins
│       ├── _base.scss        # Base/reset styles
│       └── components/       # Component styles
├── config/                 # Craft configuration
│   ├── general.php
│   ├── project/             # Project config (version controlled)
│   └── ...
├── templates/              # Twig templates
│   ├── _layout.twig         # Base layout
│   ├── _meta.twig           # Head meta/assets
│   ├── _header.twig         # Header/nav
│   ├── _footer.twig         # Footer
│   └── index.twig           # Homepage
├── web/                    # Document root
│   ├── cms/                 # Compiled assets (gitignored)
│   │   ├── js/app.js
│   │   └── css/app.css
│   └── index.php
├── webpack.config.js
├── postcss.config.js
└── Makefile
```

## Build Pipeline

- **Webpack 5** with Babel (ES2020+ transpilation)
- **SCSS** compiled with modern Sass API + PostCSS autoprefixer
- **MiniCssExtractPlugin** outputs CSS to `web/cms/css/`
- **TerserPlugin** minifies JS in production
- **@parallelogram-js/core** included with development condition exports

## New Project Checklist

When using this skeleton for a new project:

1. Update `package.json` — change `name`
2. Update `config/project/project.yaml` — change site `name`, `email`, `timeZone`
3. Update `config/project/sites/` and `siteGroups/` — change site names
4. Replace `web/favicon.ico` with your actual favicon
5. Run `make setup` to configure database and admin account
