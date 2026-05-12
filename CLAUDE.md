# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## О проекте

OpenCart e-commerce (дистрибутив ocStore), версия 3.0.3.7. Самодельный PHP MVC-фреймворк — не на Laravel/Symfony. Требуется PHP 8.5+, база MySQL с префиксом `oc_`.

## Точки входа

- **Веб-корень**: `public/` (настройте веб-сервер на эту директорию).
- **Фронтенд**: `public/index.php` → `start('catalog')`.
- **Админка**: `public/admin/index.php` → `start('admin')`.
- **Конфиги с путями и БД**: `public/config.php`, `public/admin/config.php` — оба подключают `public/system/config/database.php` для кред БД.
- **CLI**: `php public/framework <route>` (например, `php public/framework tool/cache/clear`).
- **CLI админки**: `php public/admin/adminframework <route>`.

`public/system/startup.php` определяет функцию `modification()` (обёртка OCMOD), Composer-автозагрузку из `storage/vendor/`, PSR-0-автозагрузчик `library()`, загружает engine и helper-файлы. Функция `start()` делегирует в `system/framework.php`.

## Запуск для разработки

```bash
php -S localhost:8080 -t public/
```

Тестов в проекте нет (ни PHPUnit, ни любого другого фреймворка).

## Цепочка загрузки (`system/framework.php`)

1. Создаёт `Registry` и загружает `Config` (default → application-специфичный конфиг).
2. Настраивает обработчик ошибок, `Event` и регистрирует хуки `action_event`.
3. Создаёт `Loader`, `Request`, `Response`, `DB` + `Medoo`, `Session`, `Cache`, `Url`, `Language`, `Document`.
4. Выполняет автозагрузки: `config_autoload`, `language_autoload`, `library_autoload`, `model_autoload`.
5. Выполняет pre-action контроллеры по порядку, затем диспетчеризует основной маршрут через `Router`.
6. `Response::output()` отправляет финальный вывод.

## Ключевая архитектура

### Registry (Service Locator)

Все核心 сервисы хранятся в центральном `Registry` (хранилище ключ-значение). Контроллеры и модели получают доступ к сервисам через магический `__get`, проксирующий запросы в registry — `$this->config`, `$this->db`, `$this->session`, `$this->load`, `$this->event`, `$this->medoo` и т.д. работают из любого контроллера или модели.

Константы директорий:
- `DIR_APPLICATION` — `catalog/` или `admin/`
- `DIR_SYSTEM` — `system/`
- `DIR_STORAGE` — `storage/` (на уровень выше `public/`)
- `DIR_TEMPLATE` — `DIR_APPLICATION . 'view/template/'`
- `DIR_MODIFICATION` — `DIR_STORAGE . 'modification/'`

### Loader — центральная фабрика

`$this->load` — это **основной DI-механизм** фреймворка. Через него загружается всё:

| Метод | Что делает |
|-------|------------|
| `$this->load->controller('route', $data)` | Выполняет контроллер, возвращает результат. События: `controller/route/before`, `controller/route/after` |
| `$this->load->model('route')` | Регистрирует модель как `$this->model_route_имя`. Модель загружается лениво (только при первом вызове метода). |
| `$this->load->view('route', $data)` | Рендерит Twig-шаблон. События: `view/route/before`, `view/route/after` |
| `$this->load->library('route')` | Загружает класс из `system/library/` и регистрирует в registry по basename |
| `$this->load->helper('route')` | Подключает файл из `system/helper/` (просто include) |
| `$this->load->config('route')` | Загружает конфиг-файл |
| `$this->load->language('route')` | Загружает языковые строки |

### Model Proxy и события

Модели не загружаются напрямую. При вызове `$this->load->model('catalog/product')`:

1. Создаётся экземпляр `Proxy`.
2. Для каждого метода класса модели через `Closure` создаётся обёртка, которая автоматически вызывает `model/catalog/product/method/before` и `model/catalog/product/method/after` до и после выполнения метода.
3. Модель сохраняется в registry как `model_catalog_product`.

Первый вызов метода создаёт реальный экземпляр класса модели и кеширует его в статической переменной замыкания. Это означает, что модели загружаются **лениво**.

### Роутинг и Action

URL маршрутизируются как `controller_folder/controller_file/method` (например, `product/product`, `common/home`). Класс `Action` преобразует строку маршрута в путь к файлу и имя класса: `common/home` → `controller/common/home.php` → `ControllerCommonHome`.

`Action` разбирает сегменты **справа налево**: последний сегмент — метод, предшествующие — путь к файлу контроллера. Пример для маршрута `account/wishlist/add`:
- Пробует `controller/account/wishlist/add.php` → нет
- Откусывает `add` как метод, пробует `controller/account/wishlist.php` → да
- Итог: файл `controller/account/wishlist.php`, класс `ControllerAccountWishlist`, метод `add`

Маршрут по умолчанию: каталог `common/home`, админка `common/dashboard`. Маршрут ошибки: `error/not_found`.

### Pre-Actions (стартовые контроллеры)

**Каталог** (`config/catalog.php`): `startup/session` → `startup/startup` → `startup/error` → `startup/event` → `startup/maintenance` → `startup/seo_url`

**Админка** (`config/admin.php`): `startup/startup` → `startup/error` → `startup/event` → (только web: `startup/login` → `startup/permission`)

Pre-action может перехватить диспетчеризацию, вернув `Action` из `execute()` — этот `Action` заменит основной маршрут.

CLI-запросы пропускают `startup/login` и `startup/permission` (условие `php_sapi_name() !== 'cli'`).

### Response API

Контроллеры управляют ответом через `$this->response`:
- `$this->response->addHeader('Header: value')` — добавить HTTP-заголовок
- `$this->response->redirect($url, 302)` — редирект и `exit()`
- `$this->response->setOutput($html)` — установить тело ответа
- `$this->response->addRewrite(new Action('route'))` — зарегистрировать реврайт (используется SEO URL)
- `$this->response->setCompression(9)` — включить gzip-сжатие

### Генерация URL

`$this->url->link('product/product', 'product_id=123', true)` генерирует URL. Параметры:
1. Маршрут
2. Query-строка
3. `$ssl` — `true` для HTTPS, `false` для HTTP

В Twig-шаблонах доступна функция `link()` с теми же параметрами.

### OCMMOD (система модификации файлов)

`system/modification.xml` и `storage/modification/` реализуют слой патчинга файлов. Функция `modification()` в `startup.php` перехватывает каждый `require`/`include` файлов из `system/` и приложения, перенаправляя на пропатченные копии в `storage/modification/`. Это позволяет расширениям менять ядро без правки исходников. При отладке помните: выполняться может версия из `storage/modification/`, а не оригинал.

### Событийная система

`engine/event.php` — pub/sub. События регистрируются с ключами-паттернами (например, `controller/*/before`) и приоритетами. Определяются в конфигах в массиве `action_event`.

### Два слоя БД

1. Нативный `DB` (`system/library/db.php`) — raw SQL через драйверы (`mysqli` — используется, `mpdo`, `pgsql`). Зарегистрирован как `$this->db`.
2. Medoo (`catfan/medoo`) — построитель запросов. Зарегистрирован как `$this->medoo`. **Для простых запросов предпочитайте Medoo.**

`DB_PREFIX` определён в `system/config/database.php` (обычно `oc_`). Medoo применяет его автоматически; в raw SQL используйте `DB_PREFIX` явно.

### Сессии

Движок: `db` — сессии хранятся в таблице `oc_session`. Имя cookie: `OCSESSID`.

### Шаблонизатор

Twig 3, шаблоны в `catalog/view/template/` и `admin/view/template/`. Класс `Template` делегирует адаптеру `Template\Twig` (`system/library/template/twig.php`). Рендеринг: `$this->load->view('path/template', $data)` возвращает готовый HTML. Twig использует `ChainLoader` с кешированием в `storage/cache/template/`.

### SEO URL

Обрабатывается `catalog/controller/startup/seo_url.php` (без сторонних библиотек). Контроллер:
- Использует `Url::addRewrite()` для генерации ЧПУ для товаров, категорий, производителей, информационных страниц, dynamic pages/categories и legacy-маршрутов blog/service.
- Декодирует входящие SEO URL обратно в query-параметры.
- Поддерживает URL вариантов товаров (формат `keyword-variant_key`).
- Перенаправляет на канонические URL (301), если текущий URL не совпадает.
- Хранит соответствия keyword→query в таблице `oc_seo_url`.

### Admin UI

Bootstrap 5 + Bootstrap Icons. Используется кастомный класс `text-right` вместо стандартного `text-end`. Кнопки действий в колонках таблиц всегда должны иметь `text-nowrap`.

### Конфигурация в БД

Рантайм-настройки (store config, настройки модулей, тема) хранятся в таблице `oc_setting` (ключ-значение, группировка по `store_id` и `code`), а не в PHP-файлах. Для изменения `config_seo_url`, `config_language_id`, выбора шаблона и т.д. используйте админку или прямой SQL — системы миграций нет.

### API

REST API в `catalog/controller/api/`: cart, coupon, currency, customer, login, order, payment, reward, shipping, voucher. Доступ: `/index.php?route=api/<endpoint>`.

## Система вариантов товаров

Варианты позволяют различать SKU внутри товара. Архитектура:

- **Variant Groups** (`oc_variant_group`): именованные группы типа «Цвет», «Размер» с сортировкой.
- **Variants** (`oc_variant`): значения внутри групп, у каждого уникальное SEO-ключевое слово.
- **Product-Variant mapping** (`oc_product_variant`): связь товаров с вариантами — `product_variant_id`, SKU, дельта цены, количество, изображение.
- Выбор варианта происходит в `catalog/controller/product/product.php` через query-параметр `variant_key`. Блок выбора варианта подгружается через `$this->load->controller('product/variant')`.
- **SEO URL**: `seo_url.php` разрешает URL в формате `keyword-variant_key` через `getProductVariantRoute()` и добавляет variant_key к ключевым словам товара при генерации URL.
- **Админка**: `admin/model/catalog/variant.php` (CRUD), `admin/controller/catalog/variant.php` (autocomplete, list).

## Dynamic Sections (единая контентная система)

Заменяет старые раздельные модули blog и service. Две предварительно созданные секции:

- **Section 1** (code: `services`) — мигрирована из старых таблиц `service`
- **Section 2** (code: `blog`) — мигрирована из старых таблиц `article`/`blog_category`

### Таблицы

- **Sections** (`oc_dynamic_section`): корневая группировка с JSON-колонкой `settings` (выбор шаблона и т.д.).
- **Pages** (`oc_dynamic_page`): страницы внутри секции. Поля: `image`, `status`, `noindex`, `date_available`, `viewed`.
- **Page Descriptions** (`oc_dynamic_page_description`): мультиязычные поля страниц — `name`, `description`, `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`, `tag`.
- **Categories** (`oc_dynamic_category`): иерархические категории (`parent_id`, `path` для обхода дерева). Поле `noindex` на уровне категории.
- **Fields** (`oc_dynamic_field`): настраиваемые поля секции (name, code, type).
- **Field Values** (`oc_dynamic_field_value`): значения полей для страниц.
- **Reviews** (`oc_dynamic_review`): отзывы к страницам.

### Legacy Blog/Service

Старые директории `controller/blog/`, `controller/service/`, `model/blog/` и т.д. **удалены**. SEO URL контроллер всё ещё обрабатывает legacy-маршруты `blog/article`, `blog/category`, `service/service`, `service/category` для обратной совместимости, но перенаправляет их на dynamic-маршруты (`dynamic/page`, `dynamic/category`).

## Composer-зависимости

`composer install` запускать из директории `storage/`. Vendor: `storage/vendor/`.

| Пакет | Версия | Назначение |
|-------|--------|------------|
| `twig/twig` | ^3.0 | Шаблонизатор |
| `catfan/medoo` | ^2.2 | SQL query builder |
| `intervention/image` | ^3.0 | Ресайз изображений (`resize_image()`) |
| `ezyang/htmlpurifier` | ^4.17 | Санитайзинг HTML |
| `phpmailer/phpmailer` | ^6.10 | Отправка почты |

## ocStore-специфичные возможности

- **Экспорт в маркетплейсы**: `extension/feed/yandex_market.php`, `yandex_turbo.php`, `google_base.php`, `unisender.php` — генерация XML-фидов.
- **translit()**: транслитерация кириллицы в латиницу для SEO-slug (`system/helper/general.php`).
- **resize_image()**: ресайз через Intervention/Image v3, всегда выдаёт **WebP** в `image/cache/`. Ключ кеша: `{path}-{width}x{height}.webp`. Поддерживает PNG, JPEG, GIF, WebP.
- **writelog()**: логирование в консоль/файл с 14 цветами ANSI. Второй параметр: имя файла (пишет в `storage/logs/{name}.log`) или имя цвета. Подавляет вывод в консоль для AJAX-запросов. Требует `posix_isatty()` для цветов.
- **Язык**: только `ru-ru` языковые файлы.

## Как добавлять код

### Контроллер

Создать `controller/<path>/<name>.php` с классом `Controller<Path><Name>`, наследующим `Controller`. Последний сегмент маршрута — имя метода.

### Модель

Создать `model/<path>/<name>.php` с классом `Model<Path><Name>`, наследующим `Model`. Загрузить: `$this->load->model('<path>/<name>')`. Доступ: `$this->model_<path>_<name>->method(...)` — методы автоматически проксируются с before/after событиями.

### Языковой файл

Создать `language/<lang>/<path>/<name>.php`, заполнив `$_['key'] = 'value'`. Загрузить: `$this->load->language('<path>/<name>')`. Доступ к строкам: `$this->language->get('key')`.

### Данные шаблона

Контроллеры передают данные в шаблон через `$this->load->view('path/template', $data)`. Второй параметр — ассоциативный массив, доступный как переменные Twig.

## Отладка

- **Очистить кеш шаблонов**: удалить содержимое `storage/cache/template/`.
- **Очистить кеш модификаций**: удалить содержимое `storage/modification/` (пересоберётся из `system/modification.xml` при следующем запросе).
- **Очистить весь кеш**: `php public/framework tool/cache/clear`.
- **writelog($data, $filename_or_color)**: быстрый вывод в `storage/logs/{name}.log` или в консоль с цветом.
- При правке `system/`-файлов помните про `modification()` — выполняемая версия может быть закеширована в `storage/modification/`.

## Правила

- **Доступ к БД**: всегда в `public/system/config/database.php`.
- **Без миграций**: изменения в таблицы БД вносить напрямую, без создания migration-файлов.

## Известные проблемы

- **Дублирующийся ключ admin config** (`system/config/admin.php` строки 45-51): `view/*/before` определён дважды. Второй массив перезаписывает первый, поэтому обработчик `event/theme` (priority 1000) теряется. Выполняется только `event/language` для `view/*/before`.
