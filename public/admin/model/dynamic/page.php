<?php
class ModelDynamicPage extends Model {
	public function addPage($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page SET section_id = '" . (int)$data['section_id'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_available = CURDATE(), date_added = NOW()");

		$page_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page SET image = '" . $this->db->escape($data['image']) . "' WHERE page_id = '" . (int)$page_id . "'");
		}

		foreach ($data['page_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_description SET page_id = '" . (int)$page_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', tag = '" . $this->db->escape(isset($value['tag']) ? $value['tag'] : '') . "'");
		}

		if (isset($data['page_store'])) {
			foreach ($data['page_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_store SET page_id = '" . (int)$page_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['page_image'])) {
			foreach ($data['page_image'] as $page_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_image SET page_id = '" . (int)$page_id . "', image = '" . $this->db->escape($page_image['image']) . "', sort_order = '" . (int)$page_image['sort_order'] . "'");
			}
		}

		if (isset($data['page_download'])) {
			foreach ($data['page_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_download SET page_id = '" . (int)$page_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		if (isset($data['page_category'])) {
			foreach ($data['page_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_category SET page_id = '" . (int)$page_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if (isset($data['main_category_id']) && $data['main_category_id'] > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_category SET page_id = '" . (int)$page_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
		} elseif (isset($data['page_category'][0])) {
			$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page_to_category SET main_category = 1 WHERE page_id = '" . (int)$page_id . "' AND category_id = '" . (int)$data['page_category'][0] . "'");
		}

		if (isset($data['page_related'])) {
			foreach ($data['page_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$page_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_related SET page_id = '" . (int)$page_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$related_id . "' AND related_id = '" . (int)$page_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_related SET page_id = '" . (int)$related_id . "', related_id = '" . (int)$page_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related_product WHERE page_id = '" . (int)$page_id . "' AND product_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_related_product SET page_id = '" . (int)$page_id . "', product_id = '" . (int)$related_id . "'");
			}
		}

		// SEO URL
		if (isset($data['page_seo_url'])) {
			foreach ($data['page_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'dpage_id=" . (int)$page_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		if (isset($data['page_layout'])) {
			foreach ($data['page_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_layout SET page_id = '" . (int)$page_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->savePageFieldValues($page_id, $data);

		$this->cache->delete('dynamic_section');

		return $page_id;
	}

	public function editPage($page_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page SET section_id = '" . (int)$data['section_id'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page SET image = '" . $this->db->escape($data['image']) . "' WHERE page_id = '" . (int)$page_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_description WHERE page_id = '" . (int)$page_id . "'");

		foreach ($data['page_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_description SET page_id = '" . (int)$page_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "', tag = '" . $this->db->escape(isset($value['tag']) ? $value['tag'] : '') . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_store WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['page_store'])) {
			foreach ($data['page_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_store SET page_id = '" . (int)$page_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_image WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['page_image'])) {
			foreach ($data['page_image'] as $page_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_image SET page_id = '" . (int)$page_id . "', image = '" . $this->db->escape($page_image['image']) . "', sort_order = '" . (int)$page_image['sort_order'] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_download WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['page_download'])) {
			foreach ($data['page_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_download SET page_id = '" . (int)$page_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['page_category'])) {
			foreach ($data['page_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_category SET page_id = '" . (int)$page_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if (isset($data['main_category_id']) && $data['main_category_id'] > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_category SET page_id = '" . (int)$page_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
		} elseif (isset($data['page_category'][0])) {
			$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page_to_category SET main_category = 1 WHERE page_id = '" . (int)$page_id . "' AND category_id = '" . (int)$data['page_category'][0] . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE related_id = '" . (int)$page_id . "'");

		if (isset($data['page_related'])) {
			foreach ($data['page_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$page_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_related SET page_id = '" . (int)$page_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$related_id . "' AND related_id = '" . (int)$page_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_related SET page_id = '" . (int)$related_id . "', related_id = '" . (int)$page_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related_product WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related_product WHERE page_id = '" . (int)$page_id . "' AND product_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_related_product SET page_id = '" . (int)$page_id . "', product_id = '" . (int)$related_id . "'");
			}
		}

		// SEO URL
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'dpage_id=" . (int)$page_id . "'");

		if (isset($data['page_seo_url'])) {
			foreach ($data['page_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'dpage_id=" . (int)$page_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_layout WHERE page_id = '" . (int)$page_id . "'");

		if (isset($data['page_layout'])) {
			foreach ($data['page_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_page_to_layout SET page_id = '" . (int)$page_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->savePageFieldValues($page_id, $data);

		$this->cache->delete('dynamic_section');
	}

	public function editPageStatus($page_id, $status) {
		$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page SET status = '" . (int)$status . "', date_modified = NOW() WHERE page_id = '" . (int)$page_id . "'");
		$this->cache->delete('dynamic_section');
		return $page_id;
	}

	public function copyPage($page_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) WHERE p.page_id = '" . (int)$page_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		if ($query->num_rows) {
			$data = $query->row;
			$data['viewed'] = '0';
			$data['status'] = '0';
			$data['noindex'] = '0';
			$data['page_description'] = $this->getPageDescriptions($page_id);
			$data['page_image'] = $this->getPageImages($page_id);
			$data['page_related'] = $this->getPageRelated($page_id);
			$data['product_related'] = $this->getProductRelated($page_id);
			$data['page_category'] = $this->getPageCategories($page_id);
			$data['page_download'] = $this->getPageDownloads($page_id);
			$data['page_layout'] = $this->getPageLayouts($page_id);
			$data['page_store'] = $this->getPageStores($page_id);
			$data['page_field'] = $this->getPageFieldValues($page_id);
			$data['section_id'] = $query->row['section_id'];

			$this->addPage($data);
		}
	}

	public function deletePage($page_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_description WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_image WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related WHERE related_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_related_product WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_download WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_layout WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_page_to_store WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_review WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_field_value WHERE page_id = '" . (int)$page_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'dpage_id=" . (int)$page_id . "'");

		$this->cache->delete('dynamic_section');
	}

	public function getPage($page_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) WHERE p.page_id = '" . (int)$page_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		return $query->row;
	}

	public function getPages($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_section_id'])) {
			$sql .= " AND p.section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		if (isset($data['filter_noindex']) && !is_null($data['filter_noindex'])) {
			$sql .= " AND p.noindex = '" . (int)$data['filter_noindex'] . "'";
		}

		$sql .= " GROUP BY p.page_id";

		$sort_data = array('pd.name', 'p.status', 'p.noindex', 'p.sort_order');

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
		}

		$sql .= (isset($data['order']) && $data['order'] == 'DESC') ? " DESC" : " ASC";

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) $data['start'] = 0;
			if ($data['limit'] < 1) $data['limit'] = 20;
			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function getPagesByCategoryId($category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_to_category p2c ON (p.page_id = p2c.page_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.category_id = '" . (int)$category_id . "' ORDER BY pd.name ASC");
		return $query->rows;
	}

	public function getPageDescriptions($page_id) {
		$page_description_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_description WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_h1'          => $result['meta_h1'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword'],
				'tag'              => $result['tag']
			);
		}
		return $page_description_data;
	}

	public function getPageCategories($page_id) {
		$page_category_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_category_data[] = $result['category_id'];
		}
		return $page_category_data;
	}

	public function getPageMainCategoryId($page_id) {
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "' AND main_category = '1' LIMIT 1");
		return ($query->num_rows ? (int)$query->row['category_id'] : 0);
	}

	public function getPageImages($page_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_image WHERE page_id = '" . (int)$page_id . "' ORDER BY sort_order ASC");
		return $query->rows;
	}

	public function getPageDownloads($page_id) {
		$page_download_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_download WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_download_data[] = $result['download_id'];
		}
		return $page_download_data;
	}

	public function getPageStores($page_id) {
		$page_store_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_store WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_store_data[] = $result['store_id'];
		}
		return $page_store_data;
	}

	public function getPageSeoUrls($page_id) {
		$page_seo_url_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'dpage_id=" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}
		return $page_seo_url_data;
	}

	public function getPageLayouts($page_id) {
		$page_layout_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_layout WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_layout_data[$result['store_id']] = $result['layout_id'];
		}
		return $page_layout_data;
	}

	public function getPageRelated($page_id) {
		$page_related_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_related WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$page_related_data[] = $result['related_id'];
		}
		return $page_related_data;
	}

	public function getProductRelated($page_id) {
		$product_related_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_related_product WHERE page_id = '" . (int)$page_id . "'");
		foreach ($query->rows as $result) {
			$product_related_data[] = $result['product_id'];
		}
		return $product_related_data;
	}

	public function getTotalPages($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.page_id) AS total FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id)";
		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_section_id'])) {
			$sql .= " AND p.section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}

		$query = $this->db->query($sql);
		return $query->row['total'];
	}

	public function getTotalPagesByDownloadId($download_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_page_to_download WHERE download_id = '" . (int)$download_id . "'");
		return $query->row['total'];
	}

	public function getTotalPagesByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_page_to_layout WHERE layout_id = '" . (int)$layout_id . "'");
		return $query->row['total'];
	}

	public function getPageFieldValues($page_id) {
		$field_data = array();
		$query = $this->db->query("SELECT df.code, df.type, dfv.value FROM " . DB_PREFIX . "dynamic_field df LEFT JOIN " . DB_PREFIX . "dynamic_field_value dfv ON (df.field_id = dfv.field_id AND dfv.page_id = '" . (int)$page_id . "') ORDER BY df.sort_order, df.name");
		foreach ($query->rows as $result) {
			$field_data[$result['code']] = $this->normalizeFieldValue($result['type'], $result['value']);
		}
		return $field_data;
	}

	public function getPageFields($section_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_field WHERE section_id = '" . (int)$section_id . "' AND status = '1' ORDER BY sort_order, name");
		return $query->rows;
	}

	private function savePageFieldValues($page_id, $data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "dynamic_field_value WHERE page_id = '" . (int)$page_id . "'");

		if (empty($data['page_field']) || !is_array($data['page_field'])) {
			return;
		}

		$section_id = isset($data['section_id']) ? (int)$data['section_id'] : 0;
		$query = $this->db->query("SELECT field_id, code, type FROM " . DB_PREFIX . "dynamic_field WHERE section_id = '" . $section_id . "'");

		foreach ($query->rows as $field) {
			if (!array_key_exists($field['code'], $data['page_field'])) {
				continue;
			}
			$value = $this->normalizeFieldValue($field['type'], $data['page_field'][$field['code']]);
			if ($value !== '') {
				$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_field_value SET page_id = '" . (int)$page_id . "', field_id = '" . (int)$field['field_id'] . "', value = '" . $this->db->escape($value) . "'");
			}
		}
	}

	private function normalizeFieldValue($type, $value) {
		$value = is_array($value) ? '' : trim((string)$value);
		if ($value === '') return '';

		if ($type == 'number') return (string)(float)str_replace(',', '.', $value);
		if ($type == 'date') return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
		if ($type == 'time') return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? substr($value, 0, 5) : '';

		return $value;
	}
}
