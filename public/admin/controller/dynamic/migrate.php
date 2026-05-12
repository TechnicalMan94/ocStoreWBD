<?php
class ControllerDynamicMigrate extends Controller {
	private $errors = array();
	private $messages = array();

	public function index() {
		$this->load->language('dynamic/migrate');

		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->runMigration();
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['button_migrate'] = $this->language->get('button_migrate');
		$data['button_delete_old'] = $this->language->get('button_delete_old');

		$data['errors'] = $this->errors;
		$data['messages'] = $this->messages;

		$data['action'] = $this->url->link('dynamic/migrate', 'user_token=' . $this->session->data['user_token'], true);
		$data['delete_action'] = $this->url->link('dynamic/migrate/deleteOld', 'user_token=' . $this->session->data['user_token'], true);

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('dynamic/migrate', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'dynamic/section')) {
			$this->errors[] = $this->language->get('error_permission');
		}

		return !$this->errors;
	}

	private function runMigration() {
		// Create tables
		$this->createTables();

		// Migrate services (section_id=1)
		if ($this->tableExists('service')) {
			$this->migrateServices();
		} else {
			$this->messages[] = 'Service tables not found, skipping service migration.';
		}

		// Migrate blog (section_id=2)
		if ($this->tableExists('article')) {
			$this->migrateBlog();
		} else {
			$this->messages[] = 'Blog tables not found, skipping blog migration.';
		}

		$this->messages[] = 'Migration completed. Review the messages above and then delete old files and tables.';
	}

	private function createTables() {
		$sql_file = DIR_APPLICATION . '../install_dynamic_sections.sql';

		if (!file_exists($sql_file)) {
			$this->errors[] = 'install_dynamic_sections.sql not found at ' . $sql_file;
			return;
		}

		$sql = file_get_contents($sql_file);
		$queries = $this->parseSql($sql);

		foreach ($queries as $query) {
			try {
				$this->db->query($query);
			} catch (Exception $e) {
				$this->messages[] = 'Notice: ' . $e->getMessage();
			}
		}

		// Ensure settings column exists (for tables created before it was added)
		try {
			$this->db->query("ALTER TABLE `" . DB_PREFIX . "dynamic_section` ADD COLUMN `settings` text DEFAULT NULL");
			$this->messages[] = 'Added settings column to dynamic_section table.';
		} catch (Exception $e) {
			// Column already exists
		}

		$this->messages[] = 'Dynamic section tables created (or already exist).';
	}

	private function migrateServices() {
		$section_id = 1;

		// Create section record
		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_section SET section_id = '1', code = 'services', name = 'Услуги', status = '1', sort_order = '0', settings = '" . $this->db->escape(json_encode(array('category_template' => 'category_default', 'page_template' => 'page_default'))) . "'");

		// Migrate categories
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category");
		foreach ($query->rows as $row) {
			$cat_id = (int)$row['service_category_id'];
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category SET category_id = '" . $cat_id . "', section_id = '1', image = '" . $this->db->escape($row['image']) . "', parent_id = '" . (int)$row['parent_id'] . "', top = '" . (int)$row['top'] . "', `column` = '" . (int)$row['column'] . "', sort_order = '" . (int)$row['sort_order'] . "', status = '" . (int)$row['status'] . "', noindex = '" . (int)$row['noindex'] . "', date_added = '" . $this->db->escape($row['date_added']) . "', date_modified = '" . $this->db->escape($row['date_modified']) . "'");
		}

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_description (category_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword) SELECT service_category_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword FROM " . DB_PREFIX . "service_category_description");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_to_store (category_id, store_id) SELECT service_category_id, store_id FROM " . DB_PREFIX . "service_category_to_store");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_to_layout (category_id, store_id, layout_id) SELECT service_category_id, store_id, layout_id FROM " . DB_PREFIX . "service_category_to_layout");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_path (category_id, path_id, level) SELECT service_category_id, path_id, level FROM " . DB_PREFIX . "service_category_path");

		$this->messages[] = 'Migrated service categories.';

		// Migrate pages (services → pages)
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service");
		foreach ($query->rows as $row) {
			$page_id = (int)$row['service_id'];
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page SET page_id = '" . $page_id . "', section_id = '1', image = '" . $this->db->escape($row['image']) . "', date_available = '" . $this->db->escape($row['date_available']) . "', sort_order = '" . (int)$row['sort_order'] . "', status = '" . (int)$row['status'] . "', noindex = '" . (int)$row['noindex'] . "', date_added = '" . $this->db->escape($row['date_added']) . "', date_modified = '" . $this->db->escape($row['date_modified']) . "', viewed = '" . (int)$row['viewed'] . "'");
		}

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_description (page_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword, tag) SELECT service_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword, tag FROM " . DB_PREFIX . "service_description");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_image (page_id, image, sort_order) SELECT service_id, image, sort_order FROM " . DB_PREFIX . "service_image");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_category (page_id, category_id, main_category) SELECT service_id, service_category_id, main_service_category FROM " . DB_PREFIX . "service_to_service_category");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_related (page_id, related_id) SELECT service_id, related_id FROM " . DB_PREFIX . "service_related");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_related_product (page_id, product_id) SELECT service_id, product_id FROM " . DB_PREFIX . "service_related_product");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_download (page_id, download_id) SELECT service_id, download_id FROM " . DB_PREFIX . "service_to_download");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_layout (page_id, store_id, layout_id) SELECT service_id, store_id, layout_id FROM " . DB_PREFIX . "service_to_layout");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_store (page_id, store_id) SELECT service_id, store_id FROM " . DB_PREFIX . "service_to_store");

		// Migrate reviews
		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_review (review_id, page_id, customer_id, author, text, rating, status, date_added, date_modified) SELECT review_service_id, service_id, customer_id, author, text, rating, status, date_added, date_modified FROM " . DB_PREFIX . "review_service");

		// Migrate fields
		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_field (field_id, section_id, code, name, type, sort_order, status) SELECT service_field_id, '1', code, name, type, sort_order, status FROM " . DB_PREFIX . "service_field");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_field_value (page_id, field_id, value) SELECT service_id, service_field_id, value FROM " . DB_PREFIX . "service_field_value");

		// Migrate SEO URLs
		$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET `query` = REPLACE(`query`, 'service_category_id=', 'dcategory_id=') WHERE `query` LIKE 'service_category_id=%'");
		$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET `query` = REPLACE(`query`, 'service_id=', 'dpage_id=') WHERE `query` LIKE 'service_id=%'");

		$this->messages[] = 'Migrated service pages, reviews, fields, and SEO URLs.';
	}

	private function migrateBlog() {
		$section_id = 2;

		// Create section record
		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_section SET section_id = '2', code = 'blog', name = 'Блог', status = '1', sort_order = '0', settings = '" . $this->db->escape(json_encode(array('category_template' => 'category_default', 'page_template' => 'page_default'))) . "'");

		// Migrate blog categories
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_category");
		foreach ($query->rows as $row) {
			$cat_id = (int)$row['blog_category_id'];
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category SET category_id = '" . $cat_id . "', section_id = '2', image = '" . $this->db->escape($row['image']) . "', parent_id = '" . (int)$row['parent_id'] . "', top = '" . (int)$row['top'] . "', `column` = '" . (int)$row['column'] . "', sort_order = '" . (int)$row['sort_order'] . "', status = '" . (int)$row['status'] . "', noindex = '" . (int)$row['noindex'] . "', date_added = '" . $this->db->escape($row['date_added']) . "', date_modified = '" . $this->db->escape($row['date_modified']) . "'");
		}

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_description (category_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword) SELECT blog_category_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword FROM " . DB_PREFIX . "blog_category_description");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_to_store (category_id, store_id) SELECT blog_category_id, store_id FROM " . DB_PREFIX . "blog_category_to_store");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_to_layout (category_id, store_id, layout_id) SELECT blog_category_id, store_id, layout_id FROM " . DB_PREFIX . "blog_category_to_layout");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_category_path (category_id, path_id, level) SELECT blog_category_id, path_id, level FROM " . DB_PREFIX . "blog_category_path");

		$this->messages[] = 'Migrated blog categories.';

		// Migrate pages (articles → pages)
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "article");
		foreach ($query->rows as $row) {
			$page_id = (int)$row['article_id'];
			$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page SET page_id = '" . $page_id . "', section_id = '2', image = '" . $this->db->escape($row['image']) . "', date_available = '" . $this->db->escape($row['date_available']) . "', sort_order = '" . (int)$row['sort_order'] . "', status = '" . (int)$row['status'] . "', noindex = '" . (int)$row['noindex'] . "', date_added = '" . $this->db->escape($row['date_added']) . "', date_modified = '" . $this->db->escape($row['date_modified']) . "', viewed = '" . (int)$row['viewed'] . "'");
		}

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_description (page_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword, tag) SELECT article_id, language_id, name, description, meta_title, meta_h1, meta_description, meta_keyword, tag FROM " . DB_PREFIX . "article_description");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_image (page_id, image, sort_order) SELECT article_id, image, sort_order FROM " . DB_PREFIX . "article_image");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_category (page_id, category_id, main_category) SELECT article_id, blog_category_id, main_blog_category FROM " . DB_PREFIX . "article_to_blog_category");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_related (page_id, related_id) SELECT article_id, related_id FROM " . DB_PREFIX . "article_related");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_related_product (page_id, product_id) SELECT article_id, product_id FROM " . DB_PREFIX . "article_related_product");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_download (page_id, download_id) SELECT article_id, download_id FROM " . DB_PREFIX . "article_to_download");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_layout (page_id, store_id, layout_id) SELECT article_id, store_id, layout_id FROM " . DB_PREFIX . "article_to_layout");

		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_page_to_store (page_id, store_id) SELECT article_id, store_id FROM " . DB_PREFIX . "article_to_store");

		// Migrate reviews
		$this->db->query("INSERT IGNORE INTO " . DB_PREFIX . "dynamic_review (review_id, page_id, customer_id, author, text, rating, status, date_added, date_modified) SELECT review_article_id, article_id, customer_id, author, text, rating, status, date_added, date_modified FROM " . DB_PREFIX . "review_article");

		// Migrate SEO URLs
		$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET `query` = REPLACE(`query`, 'blog_category_id=', 'dcategory_id=') WHERE `query` LIKE 'blog_category_id=%'");
		$this->db->query("UPDATE " . DB_PREFIX . "seo_url SET `query` = REPLACE(`query`, 'article_id=', 'dpage_id=') WHERE `query` LIKE 'article_id=%'");

		$this->messages[] = 'Migrated blog articles, reviews, and SEO URLs.';

		// Update user permissions
		$this->updatePermissions();
	}

	private function updatePermissions() {
		$new_perms = array('dynamic/page_services', 'dynamic/category_services', 'dynamic/field_services', 'dynamic/page_blog', 'dynamic/category_blog', 'dynamic/section');

		$query = $this->db->query("SELECT user_group_id, permission FROM " . DB_PREFIX . "user_group");

		foreach ($query->rows as $row) {
			$permission = json_decode($row['permission'], true);

			if (!$permission) {
				$permission = array('access' => array(), 'modify' => array());
			}

			foreach ($new_perms as $perm) {
				if (!in_array($perm, $permission['access'])) {
					$permission['access'][] = $perm;
				}
				if (!in_array($perm, $permission['modify'])) {
					$permission['modify'][] = $perm;
				}
			}

			$this->db->query("UPDATE " . DB_PREFIX . "user_group SET permission = '" . $this->db->escape(json_encode($permission)) . "' WHERE user_group_id = '" . (int)$row['user_group_id'] . "'");
		}

		$this->messages[] = 'Updated user group permissions with dynamic section access.';
	}

	public function deleteOld() {
		if (!$this->user->hasPermission('modify', 'dynamic/section')) {
			$this->session->data['error'] = $this->language->get('error_permission');
			$this->response->redirect($this->url->link('dynamic/migrate', 'user_token=' . $this->session->data['user_token'], true));
		}

		$old_files = array(
			// Admin controllers
			DIR_APPLICATION . 'controller/extension/module/blog_category.php',
			DIR_APPLICATION . 'controller/extension/module/blog_featured.php',
			DIR_APPLICATION . 'controller/extension/module/blog_latest.php',
			// Catalog controllers
			DIR_CATALOG . 'controller/extension/module/blog_category.php',
			DIR_CATALOG . 'controller/extension/module/blog_featured.php',
			DIR_CATALOG . 'controller/extension/module/blog_latest.php',
			// Admin view templates
			DIR_APPLICATION . 'view/template/extension/module/blog_category.twig',
			DIR_APPLICATION . 'view/template/extension/module/blog_featured.twig',
			DIR_APPLICATION . 'view/template/extension/module/blog_latest.twig',
			// Catalog view templates
			DIR_CATALOG . 'view/template/extension/module/blog_category.twig',
			DIR_CATALOG . 'view/template/extension/module/blog_featured.twig',
			DIR_CATALOG . 'view/template/extension/module/blog_latest.twig',
			// Admin language
			DIR_APPLICATION . 'language/ru-ru/extension/module/blog_category.php',
			DIR_APPLICATION . 'language/ru-ru/extension/module/blog_featured.php',
			DIR_APPLICATION . 'language/ru-ru/extension/module/blog_latest.php',
			// Catalog language
			DIR_CATALOG . 'language/ru-ru/extension/module/blog_category.php',
			DIR_CATALOG . 'language/ru-ru/extension/module/blog_featured.php',
			DIR_CATALOG . 'language/ru-ru/extension/module/blog_latest.php',
		);

		$deleted = 0;

		foreach ($old_files as $file) {
			if (file_exists($file)) {
				unlink($file);
				$deleted++;
			}
		}

		// Drop old tables
		$old_tables = array(
			'service', 'service_description', 'service_image', 'service_related', 'service_related_product',
			'service_related_wb', 'service_related_mn', 'service_to_download', 'service_to_layout', 'service_to_store',
			'service_to_service_category', 'product_related_service',
			'service_category', 'service_category_description', 'service_category_to_layout', 'service_category_to_store',
			'service_category_path', 'review_service', 'service_field', 'service_field_value',
			'article', 'article_description', 'article_image', 'article_related', 'article_related_product',
			'article_related_wb', 'article_related_mn', 'article_to_download', 'article_to_layout', 'article_to_store',
			'article_to_blog_category', 'product_related_article',
			'blog_category', 'blog_category_description', 'blog_category_to_layout', 'blog_category_to_store',
			'blog_category_path', 'review_article',
		);

		$dropped = 0;

		foreach ($old_tables as $table) {
			try {
				$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . $table . "`");
				$dropped++;
			} catch (Exception $e) {
				// ignore
			}
		}

		$this->session->data['success'] = sprintf('Deleted %d old files and dropped %d old tables.', $deleted, $dropped);

		$this->response->redirect($this->url->link('dynamic/migrate', 'user_token=' . $this->session->data['user_token'], true));
	}

	private function tableExists($table) {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . $table . "'");
		return $query->num_rows > 0;
	}

	private function parseSql($sql) {
		$queries = array();
		$current = '';

		$lines = explode("\n", $sql);

		foreach ($lines as $line) {
			$line = trim($line);

			// Skip comments and empty lines
			if (empty($line) || strpos($line, '--') === 0 || strpos($line, '/*') === 0) {
				continue;
			}

			$current .= ' ' . $line;

			if (substr($line, -1) == ';') {
				$current = trim($current);
				if (!empty($current)) {
					$queries[] = rtrim($current, ';');
				}
				$current = '';
			}
		}

		// Add remaining if no trailing semicolon
		$current = trim($current);
		if (!empty($current)) {
			$queries[] = $current;
		}

		return $queries;
	}
}
