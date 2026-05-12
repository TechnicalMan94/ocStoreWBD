<?php
class ModelDynamicPage extends Model {
	public function updateViewed($page_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "dynamic_page SET viewed = (viewed + 1) WHERE page_id = '" . (int)$page_id . "'");
	}

	public function getPage($page_id) {
		$query = $this->db->query("SELECT DISTINCT *, pd.name AS name, p.image, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "dynamic_review r1 WHERE r1.page_id = p.page_id AND r1.status = '1' GROUP BY r1.page_id) AS rating, (SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_review r2 WHERE r2.page_id = p.page_id AND r2.status = '1' GROUP BY r2.page_id) AS reviews FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_to_store p2s ON (p.page_id = p2s.page_id) WHERE p.page_id = '" . (int)$page_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return array(
				'page_id'          => $query->row['page_id'],
				'section_id'       => $query->row['section_id'],
				'name'             => $query->row['name'],
				'description'      => $query->row['description'],
				'meta_title'       => $query->row['meta_title'],
				'meta_h1'          => $query->row['meta_h1'],
				'meta_description' => $query->row['meta_description'],
				'meta_keyword'     => $query->row['meta_keyword'],
				'tag'              => $query->row['tag'],
				'image'            => $query->row['image'],
				'noindex'          => $query->row['noindex'],
				'rating'           => round($query->row['rating'] ? $query->row['rating'] : 0),
				'reviews'          => $query->row['reviews'],
				'sort_order'       => $query->row['sort_order'],
				'status'           => $query->row['status'],
				'date_added'       => $query->row['date_added'],
				'date_modified'    => $query->row['date_modified'],
				'viewed'           => $query->row['viewed']
			);
		} else {
			return false;
		}
	}

	public function getPages($data = array()) {
		$cache_key = 'dynamic.page.' . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . md5(http_build_query($data));
		$page_data = $this->cache->get($cache_key);

		if (!$page_data) {
			$sql = "SELECT p.page_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "dynamic_review r1 WHERE r1.page_id = p.page_id AND r1.status = '1' GROUP BY r1.page_id) AS rating FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_to_store p2s ON (p.page_id = p2s.page_id)";

			if (!empty($data['filter_category_id'])) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "dynamic_page_to_category p2c ON (p.page_id = p2c.page_id)";
			}

			$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

			if (!empty($data['filter_section_id'])) {
				$sql .= " AND p.section_id = '" . (int)$data['filter_section_id'] . "'";
			}

			if (!empty($data['filter_category_id'])) {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

			if (!empty($data['filter_name']) || !empty($data['filter_tag'])) {
				$sql .= " AND (";
				if (!empty($data['filter_name'])) {
					$sql .= "LCASE(pd.name) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
				}
				if (!empty($data['filter_name']) && !empty($data['filter_tag'])) {
					$sql .= " OR ";
				}
				if (!empty($data['filter_tag'])) {
					$sql .= "LCASE(pd.tag) LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_tag'])) . "%'";
				}
				$sql .= ")";
			}

			$sql .= " GROUP BY p.page_id";

			$sort_data = array('pd.name', 'p.sort_order', 'p.date_added', 'p.viewed');
			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];
			} else {
				$sql .= " ORDER BY p.sort_order";
			}
			$sql .= (isset($data['order']) && $data['order'] == 'DESC') ? " DESC" : " ASC";

			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) $data['start'] = 0;
				if ($data['limit'] < 1) $data['limit'] = 20;
				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}

			$query = $this->db->query($sql);
			$page_data = $query->rows;
			$this->cache->set($cache_key, $page_data);
		}

		return $page_data;
	}

	public function getPageImages($page_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_image WHERE page_id = '" . (int)$page_id . "' ORDER BY sort_order ASC");
		return $query->rows;
	}

	public function getPageRelated($page_id) {
		$page_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_related pr LEFT JOIN " . DB_PREFIX . "dynamic_page p ON (pr.related_id = p.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_to_store p2s ON (p.page_id = p2s.page_id) WHERE pr.page_id = '" . (int)$page_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		foreach ($query->rows as $result) {
			$page_data[$result['related_id']] = $this->getPage($result['related_id']);
		}
		return $page_data;
	}

	public function getPageRelatedProduct($page_id) {
		$product_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_related_product WHERE page_id = '" . (int)$page_id . "'");
		$this->load->model('catalog/product');
		foreach ($query->rows as $result) {
			$product_info = $this->model_catalog_product->getProduct($result['product_id']);
			if ($product_info) {
				$product_data[] = $product_info;
			}
		}
		return $product_data;
	}

	public function getPageFields($page_id) {
		$field_data = array();
		$query = $this->db->query("SELECT f.code, fv.value FROM " . DB_PREFIX . "dynamic_field_value fv LEFT JOIN " . DB_PREFIX . "dynamic_field f ON (fv.field_id = f.field_id) WHERE fv.page_id = '" . (int)$page_id . "' AND f.status = '1'");
		foreach ($query->rows as $result) {
			$field_data[$result['code']] = $result['value'];
		}
		return $field_data;
	}

	public function getCategories($page_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_category WHERE page_id = '" . (int)$page_id . "'");
		return $query->rows;
	}

	public function getDownloads($page_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_download WHERE page_id = '" . (int)$page_id . "'");
		return $query->rows;
	}

	public function getDownload($download_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "download d LEFT JOIN " . DB_PREFIX . "download_description dd ON (d.download_id = dd.download_id) WHERE d.download_id = '" . (int)$download_id . "' AND dd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getPageLayoutId($page_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_page_to_layout WHERE page_id = '" . (int)$page_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");
		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return 0;
		}
	}

	public function getTotalPages($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.page_id) AS total FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_description pd ON (p.page_id = pd.page_id) LEFT JOIN " . DB_PREFIX . "dynamic_page_to_store p2s ON (p.page_id = p2s.page_id)";

		if (!empty($data['filter_category_id'])) {
			$sql .= " LEFT JOIN " . DB_PREFIX . "dynamic_page_to_category p2c ON (p.page_id = p2c.page_id)";
		}

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_section_id'])) {
			$sql .= " AND p.section_id = '" . (int)$data['filter_section_id'] . "'";
		}

		if (!empty($data['filter_category_id'])) {
			$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
		}

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '%" . $this->db->escape(utf8_strtolower($data['filter_name'])) . "%'";
		}

		$query = $this->db->query($sql);
		return $query->row['total'];
	}

	public function addReview($page_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "dynamic_review SET author = '" . $this->db->escape($data['name']) . "', customer_id = '" . (int)$this->customer->getId() . "', page_id = '" . (int)$page_id . "', text = '" . $this->db->escape($data['text']) . "', rating = '" . (int)$data['rating'] . "', date_added = NOW(), date_modified = NOW()");
	}

	public function getReviewsByPageId($page_id, $start = 0, $limit = 20) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "dynamic_review r WHERE r.page_id = '" . (int)$page_id . "' AND r.status = '1' ORDER BY r.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);
		return $query->rows;
	}

	public function getTotalReviewsByPageId($page_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "dynamic_review r WHERE r.page_id = '" . (int)$page_id . "' AND r.status = '1'");
		return $query->row['total'];
	}
}
