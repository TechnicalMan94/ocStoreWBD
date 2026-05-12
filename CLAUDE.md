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

`startup.php` defines the `modification()` wrapper, Composer autoload from `storage/vendor/`, a PSR-0-style `library()` autoloader, and loads engine + helper files. The `start()` function delegates to `system/framework.php`.

## Bootstrap Flow (`system/framework.php`)

1. Creates `Registry` and loads `Config` (default → application-specific config).
2. Sets up error handler, `Event` system, and registers `action_event` hooks.
3. Creates `Loader`, `Request`, `Response`, `DB` + `Medoo`, `Session`, `Cache`, `Url`, `Language`, `Document`.
4. Runs autoloads: `config_autoload`, `language_autoload`, `library_autoload`, `model_autoload`.
5. Executes `action_pre_action` controllers in order, then dispatches the main route via `Router`.
6. `Response::output()` sends the final output.

## Key Architecture

### Registry Pattern (Service Locator)

All core services are stored in a central `Registry` (key-value store). Controllers and models access services via magic `__get` proxying to the registry — `$this->config`, `$this->db`, `$this->session`, `$this->load`, `$this->event`, `$this->medoo`, etc. all work from within any controller or model.

### Routing and Action Resolution

URLs route as `controller_folder/controller_file/method` (e.g., `product/product`, `common/home`). The `Action` class converts a route string to a file path and class name: `common/home` → `controller/common/home.php` → `ControllerCommonHome`. The `Action` class peels segments from right to left — the last segment is the method, preceding segments form the path.

Default route: catalog `common/home`, admin `common/dashboard`. Error route: `error/not_found`.

### Pre-Actions (Startup Controllers)

**Catalog** (`config/catalog.php`): `startup/session` → `startup/startup` → `startup/error` → `startup/event` → `startup/maintenance` → `startup/seo_url`

**Admin** (`config/admin.php`): `startup/startup` → `startup/error` → `startup/event` → (web only: `startup/login` → `startup/permission`)

Pre-actions can intercept dispatch by returning an `Action` from `execute()`, replacing the main route.

### OCMOD Modification System

`system/modification.xml` and `storage/modification/` implement a file modification layer. The `modification()` function in `startup.php` intercepts every `require`/`include` of `system/` and application files, redirecting to patched copies in `storage/modification/`. This is how extensions hook into core without editing core files. When debugging, remember that `modification()` wraps file paths — the actual executed file may be in `storage/modification/`.

### Event/Hook System

`engine/event.php` provides a publish-subscribe system. Events are registered with key patterns (e.g., `controller/*/before`) and priorities. Model methods are automatically wrapped by the `Proxy` class so that `model/*/before` and `model/*/after` events fire around every model call. Event registrations are defined in config arrays like `action_event`.

### Two Parallel Database Layers

1. Native `DB` class (`system/library/db.php`) — raw SQL via driver adaptors (`mysqli`, `mpdo`, `pgsql`), registered as `$this->db`.
2. Medoo (`catfan/medoo`) — query builder, registered as `$this->medoo`. Prefer Medoo for simpler queries.

`DB_PREFIX` is defined in `system/config/database.php` (typically `oc_`). Medoo applies it automatically; raw SQL must use `DB_PREFIX` explicitly.

### Template Engine

Twig 3 templates in `catalog/view/template/` and `admin/view/template/`. The `Template` class delegates to `Template\Twig` adaptor (`system/library/template/twig.php`). Rendering: `$this->load->view('path/template', $data)` returns rendered HTML. The Twig environment uses a `ChainLoader` with caching to `storage/cache/template/`.

### SEO URL System

Handled by `catalog/controller/startup/seo_url.php` (no SeoPro library). This controller:
- Implements `Url::addRewrite()` to generate SEO-friendly URLs for products, categories, manufacturers, information pages, services, blog articles/categories.
- Decodes incoming SEO URLs back to query parameters.
- Handles product variant URLs (keyword-variant_key format).
- Redirects to canonical URLs (301) when the current URL doesn't match.
- Stores keyword-to-query mappings in the `oc_seo_url` table.

### Admin UI

Bootstrap 5 + Bootstrap Icons. Custom class `text-right` is used instead of Bootstrap's `text-end` for right alignment (defined in admin stylesheet). Action buttons in table columns should always have `text-nowrap` to prevent wrapping.

## How to Add or Modify

### New Controller

Create `controller/<path>/<name>.php` with class `Controller<Path><Name>` extending `Controller`. The last segment of the route is the method name.

### New Model

Create `model/<path>/<name>.php` with class `Model<Path><Name>` extending `Model`. Load via `$this->load->model('<path>/<name>')`. Access as `$this->model_<path>_<name>` — model methods are automatically proxied with before/after events.

### New Language File

Create `language/<lang>/<path>/<name>.php` populating `$_['key'] = 'value'`. Load via `$this->load->language('<path>/<name>')`. Access strings via `$this->language->get('key')`.

### Template Data

Controllers pass data to templates via `$this->load->view('path/template', $data)`. The second parameter is an associative array available as Twig variables.

## ocStore-Specific Features

- **Blog module**: `catalog/controller/blog/` and `admin/controller/blog/` — articles with categories, not present in vanilla OpenCart.
- **Service module**: `catalog/controller/service/` and `admin/controller/service/` — services with categories, custom fields, similar to products.
- **Product variants**: `catalog/controller/product/variant.php` and admin variant management — variant groups/values with keywords, auto-generated SEO URLs.
- **translit()**: Cyrillic-to-Latin transliteration helper for SEO slugs (`system/helper/general.php`).
- **writelog()**: Console/file logging with ANSI color support (`system/helper/general.php`).
- **resize_image()**: Image resizing, outputs WebP to `image/cache/` (`system/helper/general.php`).
- **Language**: Only `ru-ru` language files are present.
- **Composer vendor**: Lives in `storage/vendor/` (outside web root). Run `composer install` from `storage/` directory.
- No test suite exists in this repository.


# Доступ к БД всегда в public/system/config/database.php
Не пиши никаких миграций, просто вноси необходимые изменения в таблицы БД
