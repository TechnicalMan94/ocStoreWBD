# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenCart e-commerce platform (ocStore distribution), version 3.0.3.7. Custom PHP MVC framework — not based on Laravel/Symfony. PHP 8.5+ required, MySQL database with prefix `oc_`.

## Web Root and Entry Points

- `public/` is the web root (configure web server to serve from here).
- Frontend entry: `public/index.php` → calls `start('catalog')`.
- Admin entry: `public/admin/index.php` → calls `start('admin')`.
- Config files with DB credentials and paths: `public/config.php`, `public/admin/config.php`.
- CLI entry: `php public/framework <route>` (e.g., `php public/framework tool/cache/clear`).
- Admin CLI: `php public/admin/adminframework <route>`.

## Key Architecture

### Registry Pattern (Service Locator)

All core services are stored in a central `Registry` (key-value store). Controllers and models access services via magic `__get` proxying to the registry — `$this->config`, `$this->db`, `$this->session`, `$this->load`, `$this->event`, `$this->medoo`, etc. all work from within any controller or model.

### OCMOD Modification System

`system/modification.xml` and `storage/modification/` implement a file modification layer. The `modification()` function in `startup.php` intercepts every `require`/`include` of `system/` and application files, redirecting to patched copies in `storage/modification/`. This is how extensions hook into core without editing core files. When debugging, remember that `modification()` wraps file paths — the actual executed file may be in `storage/modification/`.

### Event/Hook System

`engine/event.php` provides a publish-subscribe system. Events are registered with key patterns (e.g., `controller/*/before`) and priorities. Model methods are automatically wrapped by the `Proxy` class so that `model/*/before` and `model/*/after` events fire around every model call. Event registrations are defined in config arrays like `action_event`.

### Routing and Action Resolution

URLs route as `controller_folder/controller_file/method` (e.g., `product/product`, `common/home`). The `Action` class converts a route string to a file path and class name: `common/home` → `controller/common/home.php` → `ControllerCommonHome`. The default route for catalog is `common/home`, for admin is `common/dashboard`. Pre-actions (startup controllers) run before the main route — these handle session, SEO URL resolution, error handling, etc.

### Two Parallel Database Layers

1. Native `DB` class (`system/library/db.php`) — raw SQL via driver adaptors (`mysqli`, `mpdo`, `pgsql`), registered as `$this->db`.
2. Medoo (`catfan/medoo`) — query builder, registered as `$this->medoo`. Prefer Medoo for simpler queries.

Table prefix `oc_` is applied automatically by Medoo but must be explicit in raw SQL.

### Template Engine

Twig 3 templates in `catalog/view/template/` and `admin/view/template/`. Rendering: `$this->load->view('path/template', $data)` returns rendered HTML. The Twig environment uses a `ChainLoader` with caching to `storage/cache/template/`.

### SeoPro

`system/library/seopro.php` is an advanced SEO URL manager (ocStore-specific). It handles SEO URLs for category, product, information, and manufacturer pages with automatic redirects, canonical URLs, and microdata.

## How to Add or Modify

### New Controller

Create `controller/<path>/<name>.php` with class `Controller<Path><Name>` extending `Controller`. The last segment of the route is the method name. Access all services via `$this-><service>`.

### New Model

Create `model/<path>/<name>.php` with class `Model<Path><Name>` extending `Model`. Load it from a controller via `$this->load->model('<path>/<name>')`. Access it as `$this->model_<path>_<name>` — model methods are automatically proxied with before/after events.

### New Language File

Create `language/<lang>/<path>/<name>.php` populating `$_['key'] = 'value'`. Load via `$this->load->language('<path>/<name>')`. Access strings via `$this->language->get('key')`.

### Template Data

Controllers pass data to templates via `$this->load->view('path/template', $data)`. The second parameter is an associative array available as Twig variables.

## ocStore-Specific Features

- **Blog module**: `catalog/controller/blog/` and `admin/controller/blog/` — not present in vanilla OpenCart.
- **translit()**: Cyrillic-to-Latin transliteration helper for SEO slugs (`system/helper/general.php`).
- **writelog()**: Console/file logging with ANSI color support.
- **resize_image()**: Image resizing, outputs WebP to `image/cache/`.
- **Language**: Only `ru-ru` language files are present.
- **Composer vendor**: Lives in `storage/vendor/` (outside web root). Run `composer install` from `storage/` directory.
