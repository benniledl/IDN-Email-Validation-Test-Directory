# IDN Email Validation Test Directory

Starter implementation for a maintainable **PHP + JavaScript + CSS** project with an accessible UI and structured routing/controller/view separation.

## Project definition

The full project definition was moved to:
- `docs/PROJECT_DEFINITION.md`

This keeps the README focused on setup while preserving the full specification.

## Stack

- PHP 8+
- Bootstrap 5 (UI and accessibility defaults)
- jQuery 3 (simple dynamic behavior)
- SQLite + PDO (easy local setup)

## Architecture

- Front controller + route map: `public/index.php`
- Controllers: `src/Controller/*`
- Repositories/services: `src/Repository/*`, `src/Service/*`
- View renderer: `src/Support/View.php`
- Templates: `views/*`

## Quick start

1. Build database schema and seed templates:
   ```bash
   sqlite3 database/app.sqlite < database/schema.sql
   ```
2. Start server:
   ```bash
   php -S 0.0.0.0:8000 -t public
   ```
3. Open `http://localhost:8000`

## Severity levels

- **High**: simple/common IDN addresses fail (`max@müller.de`, `info@büro.at`, `max@info.versicherung`)
- **Medium**: subdomain cases fail (`max@newsletter.müller.de`, `max@news.info.versicherung`)
- **Low**: complex-script IDN cases fail (`用户@例子.广告`)

The final submission severity is the highest failed template severity.
