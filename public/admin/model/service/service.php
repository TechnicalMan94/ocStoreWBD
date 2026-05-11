<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelServiceService extends Model {
	public function addService($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "service SET status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_available = CURDATE(), date_added = NOW()");

		$service_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "service SET image = '" . $this->db->escape($data['image']) . "' WHERE service_id = '" . (int)$service_id . "'");
		}

		foreach ($data['service_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "service_description SET service_id = '" . (int)$service_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if (isset($data['service_store'])) {
			foreach ($data['service_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_store SET service_id = '" . (int)$service_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['service_image'])) {
			foreach ($data['service_image'] as $service_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_image SET service_id = '" . (int)$service_id . "', image = '" . $this->db->escape($service_image['image']) . "', sort_order = '" . (int)$service_image['sort_order'] . "'");
			}
		}

		if (isset($data['service_download'])) {
			foreach ($data['service_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_download SET service_id = '" . (int)$service_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		if (isset($data['service_category'])) {
			foreach ($data['service_category'] as $service_category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_service_category SET service_id = '" . (int)$service_id . "', service_category_id = '" . (int)$service_category_id . "'");
			}
		}
		
		if (isset($data['main_service_category_id']) && $data['main_service_category_id'] > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "' AND service_category_id = '" . (int)$data['main_service_category_id'] . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_service_category SET service_id = '" . (int)$service_id . "', service_category_id = '" . (int)$data['main_service_category_id'] . "', main_service_category = 1");
		} elseif (isset($data['service_category'][0])) {
			$this->db->query("UPDATE " . DB_PREFIX . "service_to_service_category SET main_service_category = 1 WHERE service_id = '" . (int)$service_id . "' AND service_category_id = '" . (int)$data['service_category'][0] . "'");
		}

		if (isset($data['service_related'])) {
			foreach ($data['service_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$service_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_related SET service_id = '" . (int)$service_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$related_id . "' AND related_id = '" . (int)$service_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_related SET service_id = '" . (int)$related_id . "', related_id = '" . (int)$service_id . "'");
			}
		}
		
		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "service_related_product WHERE service_id = '" . (int)$service_id . "' AND product_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_related_product SET service_id = '" . (int)$service_id . "', product_id = '" . (int)$related_id . "'");
			}
		}
		
		// SEO URL
		if (isset($data['service_seo_url'])) {
			foreach ($data['service_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'service_id=" . (int)$service_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		if (isset($data['service_layout'])) {
			foreach ($data['service_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_layout SET service_id = '" . (int)$service_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->saveServiceFieldValues($service_id, $data);
		

		$this->cache->delete('service');

		return $service_id;
	}

	public function editService($service_id, $data) {

		$this->db->query("UPDATE " . DB_PREFIX . "service SET status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', sort_order = '" . (int)$data['sort_order'] . "', date_modified = NOW() WHERE service_id = '" . (int)$service_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "service SET image = '" . $this->db->escape($data['image']) . "' WHERE service_id = '" . (int)$service_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_description WHERE service_id = '" . (int)$service_id . "'");

		foreach ($data['service_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "service_description SET service_id = '" . (int)$service_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_store WHERE service_id = '" . (int)$service_id . "'");

		if (isset($data['service_store'])) {
			foreach ($data['service_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_store SET service_id = '" . (int)$service_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_image WHERE service_id = '" . (int)$service_id . "'");

		if (isset($data['service_image'])) {
			foreach ($data['service_image'] as $service_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_image SET service_id = '" . (int)$service_id . "', image = '" . $this->db->escape($service_image['image']) . "', sort_order = '" . (int)$service_image['sort_order'] . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_download WHERE service_id = '" . (int)$service_id . "'");

		if (isset($data['service_download'])) {
			foreach ($data['service_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_download SET service_id = '" . (int)$service_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "'");

		if (isset($data['service_category'])) {
			foreach ($data['service_category'] as $service_category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_service_category SET service_id = '" . (int)$service_id . "', service_category_id = '" . (int)$service_category_id . "'");
			}
		}
		
		if (isset($data['main_service_category_id']) && $data['main_service_category_id'] > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "' AND service_category_id = '" . (int)$data['main_service_category_id'] . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_service_category SET service_id = '" . (int)$service_id . "', service_category_id = '" . (int)$data['main_service_category_id'] . "', main_service_category = 1");
		} elseif (isset($data['service_category'][0])) {
			$this->db->query("UPDATE " . DB_PREFIX . "service_to_service_category SET main_service_category = 1 WHERE service_id = '" . (int)$service_id . "' AND service_category_id = '" . (int)$data['service_category'][0] . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE related_id = '" . (int)$service_id . "'");

		if (isset($data['service_related'])) {
			foreach ($data['service_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$service_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_related SET service_id = '" . (int)$service_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$related_id . "' AND related_id = '" . (int)$service_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_related SET service_id = '" . (int)$related_id . "', related_id = '" . (int)$service_id . "'");
			}
		}
		
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_related_product WHERE service_id = '" . (int)$service_id . "'");
		
		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "service_related_product WHERE service_id = '" . (int)$service_id . "' AND product_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_related_product SET service_id = '" . (int)$service_id . "', product_id = '" . (int)$related_id . "'");
			}
		}
		
		// SEO URL
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'service_id=" . (int)$service_id . "'");
		
		if (isset($data['service_seo_url'])) {
			foreach ($data['service_seo_url']as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'service_id=" . (int)$service_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_layout WHERE service_id = '" . (int)$service_id . "'");

		if (isset($data['service_layout'])) {
			foreach ($data['service_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_to_layout SET service_id = '" . (int)$service_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->saveServiceFieldValues($service_id, $data);
		
		$this->cache->delete('service');


	}
	
	public function editServiceStatus($service_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "service SET status = '" . (int)$status . "', date_modified = NOW() WHERE service_id = '" . (int)$service_id . "'");
        
		$this->cache->delete('service');
		
		return $service_id;
    }

	public function copyService($service_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) WHERE p.service_id = '" . (int)$service_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		if ($query->num_rows) {
			$data = $query->row;

			$data['viewed'] = '0';
			$data['keyword'] = '';
			$data['status'] = '0';
			$data['noindex'] = '0';

			$data['service_description'] = $this->getServiceDescriptions($service_id);
			$data['service_image'] = $this->getServiceImages($service_id);
			$data['service_related'] = $this->getServiceRelated($service_id);
			$data['product_related'] = $this->getProductRelated($service_id);
			$data['service_category'] = $this->getServiceCategories($service_id);
			$data['service_download'] = $this->getServiceDownloads($service_id);
		$data['service_layout'] = $this->getServiceLayouts($service_id);
		$data['service_store'] = $this->getServiceStores($service_id);
		$data['service_field'] = $this->getServiceFieldValues($service_id);

		$this->addService($data);
		}
	}

	public function deleteService($service_id) {

		$this->db->query("DELETE FROM " . DB_PREFIX . "service WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_description WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_image WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_related WHERE related_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_related_product WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_download WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_layout WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_store WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "review_service WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_field_value WHERE service_id = '" . (int)$service_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'service_id=" . (int)$service_id . "'");

		$this->cache->delete('service');

	}

	public function getService($service_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) WHERE p.service_id = '" . (int)$service_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row;
	}

	public function getServices($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
		if (isset($data['filter_noindex']) && !is_null($data['filter_noindex'])) {
			$sql .= " AND p.noindex = '" . (int)$data['filter_noindex'] . "'";
		}

		$sql .= " GROUP BY p.service_id";

		$sort_data = array(
			'pd.name',
			'p.status',
			'p.noindex',
			'p.sort_order'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY pd.name";
		}

		if (isset($data['order']) && ($data['order'] == 'DESC')) {
			$sql .= " DESC";
		} else {
			$sql .= " ASC";
		}

		if (isset($data['start']) || isset($data['limit'])) {
			if ($data['start'] < 0) {
				$data['start'] = 0;
			}

			if ($data['limit'] < 1) {
				$data['limit'] = 20;
			}

			$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getServicesByCategoryId($service_category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id) LEFT JOIN " . DB_PREFIX . "service_to_service_category p2c ON (p.service_id = p2c.service_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p2c.service_category_id = '" . (int)$service_category_id . "' ORDER BY pd.name ASC");

		return $query->rows;
	}

	public function getServiceDescriptions($service_id) {
		$service_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_description WHERE service_id = '" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_h1'	       => $result['meta_h1'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword']
			);
		}

		return $service_description_data;
	}

	public function getServiceCategories($service_id) {
		$service_category_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_category_data[] = $result['service_category_id'];
		}

		return $service_category_data;
	}
	
	public function getServiceMainCategoryId($service_id) {
		$query = $this->db->query("SELECT service_category_id FROM " . DB_PREFIX . "service_to_service_category WHERE service_id = '" . (int)$service_id . "' AND main_service_category = '1' LIMIT 1");
		
		return ($query->num_rows ? (int)$query->row['service_category_id'] : 0);
	}

	public function getServiceImages($service_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_image WHERE service_id = '" . (int)$service_id . "' ORDER BY sort_order ASC");

		return $query->rows;
	}

	public function getServiceDownloads($service_id) {
		$service_download_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_download WHERE service_id = '" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_download_data[] = $result['download_id'];
		}

		return $service_download_data;
	}

	public function getServiceStores($service_id) {
		$service_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_store WHERE service_id = '" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_store_data[] = $result['store_id'];
		}

		return $service_store_data;
	}
	
	public function getServiceSeoUrls($service_id) {
		$service_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'service_id=" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $service_seo_url_data;
	}

	public function getServiceLayouts($service_id) {
		$service_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_to_layout WHERE service_id = '" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $service_layout_data;
	}

	public function getServiceRelated($service_id) {
		$service_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_related WHERE service_id = '" . (int)$service_id . "'");

		foreach ($query->rows as $result) {
			$service_related_data[] = $result['related_id'];
		}

		return $service_related_data;
	}
	
	public function getProductRelated($service_id) {
		$service_related_product = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_related_product WHERE service_id = '" . (int)$service_id . "'");
		
		foreach ($query->rows as $result) {
			$service_related_product[] = $result['product_id'];
		}
		
		return $service_related_product;
	}

	public function getTotalServices($data = array()) {
		$sql = "SELECT COUNT(DISTINCT p.service_id) AS total FROM " . DB_PREFIX . "service p LEFT JOIN " . DB_PREFIX . "service_description pd ON (p.service_id = pd.service_id)";

		$sql .= " WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_name'])) {
			$sql .= " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND p.status = '" . (int)$data['filter_status'] . "'";
		}
		
		if (isset($data['filter_noindex']) && $data['filter_noindex'] !== null) {
			$sql .= " AND p.noindex = '" . (int)$data['filter_noindex'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalServicesByDownloadId($download_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "service_to_download WHERE download_id = '" . (int)$download_id . "'");

		return $query->row['total'];
	}

	public function getTotalServicesByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "service_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

		return $query->row['total'];
	}

	public function getServiceFieldValues($service_id) {
		$field_data = array();

		$query = $this->db->query("SELECT sf.code, sf.type, sfv.value FROM " . DB_PREFIX . "service_field sf LEFT JOIN " . DB_PREFIX . "service_field_value sfv ON (sf.service_field_id = sfv.service_field_id AND sfv.service_id = '" . (int)$service_id . "') ORDER BY sf.sort_order, sf.name");

		foreach ($query->rows as $result) {
			$field_data[$result['code']] = $this->normalizeFieldValue($result['type'], $result['value']);
		}

		return $field_data;
	}

	public function getServiceFields() {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_field WHERE status = '1' ORDER BY sort_order, name");

		return $query->rows;
	}

	private function saveServiceFieldValues($service_id, $data) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_field_value WHERE service_id = '" . (int)$service_id . "'");

		if (empty($data['service_field']) || !is_array($data['service_field'])) {
			return;
		}

		$query = $this->db->query("SELECT service_field_id, code, type FROM " . DB_PREFIX . "service_field");

		foreach ($query->rows as $field) {
			if (!array_key_exists($field['code'], $data['service_field'])) {
				continue;
			}

			$value = $this->normalizeFieldValue($field['type'], $data['service_field'][$field['code']]);

			if ($value !== '') {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_field_value SET service_id = '" . (int)$service_id . "', service_field_id = '" . (int)$field['service_field_id'] . "', value = '" . $this->db->escape($value) . "'");
			}
		}
	}

	private function normalizeFieldValue($type, $value) {
		$value = is_array($value) ? '' : trim((string)$value);

		if ($value === '') {
			return '';
		}

		if ($type == 'number') {
			return (string)(float)str_replace(',', '.', $value);
		}

		if ($type == 'date') {
			return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : '';
		}

		if ($type == 'time') {
			return preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value) ? substr($value, 0, 5) : '';
		}

		return $value;
	}
}
