<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt
class ModelServiceCategory extends Model {
	public function addCategory($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "service_category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', date_modified = NOW(), date_added = NOW()");
		$service_category_id = $this->db->getLastId();
		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "service_category SET image = '" . $this->db->escape($data['image']) . "' WHERE service_category_id = '" . (int)$service_category_id . "'");
		}
		foreach ($data['category_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "service_category_description SET service_category_id = '" . (int)$service_category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}
		// MySQL Hierarchical Data Closure Table Pattern
		$level = 0;
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$data['parent_id'] . "' ORDER BY `level` ASC");
		foreach ($query->rows as $result) {
			$this->db->query("INSERT INTO `" . DB_PREFIX . "service_category_path` SET `service_category_id` = '" . (int)$service_category_id . "', `path_id` = '" . (int)$result['path_id'] . "', `level` = '" . (int)$level . "'");
			$level++;
		}
		$this->db->query("INSERT INTO `" . DB_PREFIX . "service_category_path` SET `service_category_id` = '" . (int)$service_category_id . "', `path_id` = '" . (int)$service_category_id . "', `level` = '" . (int)$level . "'");
		if (isset($data['category_store'])) {
			foreach ($data['category_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_category_to_store SET service_category_id = '" . (int)$service_category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}
		
		if (isset($data['category_seo_url'])) {
			foreach ($data['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'service_category_id=" . (int)$service_category_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}
		// Set which layout to use with this category
		if (isset($data['category_layout'])) {
			foreach ($data['category_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_category_to_layout SET service_category_id = '" . (int)$service_category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}
		$this->cache->delete('service_category');

		return $service_category_id;
	}
	public function editCategory($service_category_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "service_category SET parent_id = '" . (int)$data['parent_id'] . "', `top` = '" . (isset($data['top']) ? (int)$data['top'] : 0) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "', noindex = '" . (int)$data['noindex'] . "', date_modified = NOW() WHERE service_category_id = '" . (int)$service_category_id . "'");
		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "service_category SET image = '" . $this->db->escape($data['image']) . "' WHERE service_category_id = '" . (int)$service_category_id . "'");
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_description WHERE service_category_id = '" . (int)$service_category_id . "'");
		foreach ($data['category_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "service_category_description SET service_category_id = '" . (int)$service_category_id . "', language_id = '" . (int)$language_id . "', name = '" . $this->db->escape($value['name']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}
		// MySQL Hierarchical Data Closure Table Pattern
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "service_category_path` WHERE path_id = '" . (int)$service_category_id . "' ORDER BY level ASC");
		if ($query->rows) {
			foreach ($query->rows as $category_path) {
				// Delete the path below the current one
				$this->db->query("DELETE FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$category_path['service_category_id'] . "' AND level < '" . (int)$category_path['level'] . "'");
				$path = array();
				// Get the nodes new parents
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");
				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}
				// Get whats left of the nodes current path
				$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$category_path['service_category_id'] . "' ORDER BY level ASC");
				foreach ($query->rows as $result) {
					$path[] = $result['path_id'];
				}
				// Combine the paths with a new level
				$level = 0;
				foreach ($path as $path_id) {
					$this->db->query("REPLACE INTO `" . DB_PREFIX . "service_category_path` SET service_category_id = '" . (int)$category_path['service_category_id'] . "', `path_id` = '" . (int)$path_id . "', level = '" . (int)$level . "'");
					$level++;
				}
			}
		} else {
			// Delete the path below the current one
			$this->db->query("DELETE FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$service_category_id . "'");
			// Fix for records with no paths
			$level = 0;
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$data['parent_id'] . "' ORDER BY level ASC");
			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "service_category_path` SET service_category_id = '" . (int)$service_category_id . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");
				$level++;
			}
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "service_category_path` SET service_category_id = '" . (int)$service_category_id . "', `path_id` = '" . (int)$service_category_id . "', level = '" . (int)$level . "'");
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_to_store WHERE service_category_id = '" . (int)$service_category_id . "'");
		if (isset($data['category_store'])) {
			foreach ($data['category_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_category_to_store SET service_category_id = '" . (int)$service_category_id . "', store_id = '" . (int)$store_id . "'");
			}
		}
		
		// SEO URL
		$this->db->query("DELETE FROM `" . DB_PREFIX . "seo_url` WHERE query = 'service_category_id=" . (int)$service_category_id . "'");
		if (isset($data['category_seo_url'])) {
			foreach ($data['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'service_category_id=" . (int)$service_category_id . "', keyword = '" . $this->db->escape(trim($keyword)) . "'");
					}
				}
			}
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_to_layout WHERE service_category_id = '" . (int)$service_category_id . "'");
		if (isset($data['category_layout'])) {
			foreach ($data['category_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "service_category_to_layout SET service_category_id = '" . (int)$service_category_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}
		$this->cache->delete('category');
	}
	
	public function editCategoryStatus($service_category_id, $status) {
        $this->db->query("UPDATE " . DB_PREFIX . "service_category SET status = '" . (int)$status . "', date_modified = NOW() WHERE service_category_id = '" . (int)$service_category_id . "'");
        
		$this->cache->delete('category');
    }
	public function deleteCategory($service_category_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_path WHERE service_category_id = '" . (int)$service_category_id . "'");
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category_path WHERE path_id = '" . (int)$service_category_id . "'");
		foreach ($query->rows as $result) {
			$this->deleteCategory($result['service_category_id']);
		}
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category WHERE service_category_id = '" . (int)$service_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_description WHERE service_category_id = '" . (int)$service_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_to_store WHERE service_category_id = '" . (int)$service_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_category_to_layout WHERE service_category_id = '" . (int)$service_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "service_to_service_category WHERE service_category_id = '" . (int)$service_category_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'service_category_id=" . (int)$service_category_id . "'");
		$this->cache->delete('service_category');
	}
	public function repairCategories($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category WHERE parent_id = '" . (int)$parent_id . "'");
		foreach ($query->rows as $category) {
			// Delete the path below the current one
			$this->db->query("DELETE FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$category['service_category_id'] . "'");
			// Fix for records with no paths
			$level = 0;
			$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "service_category_path` WHERE service_category_id = '" . (int)$parent_id . "' ORDER BY level ASC");
			foreach ($query->rows as $result) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "service_category_path` SET service_category_id = '" . (int)$category['service_category_id'] . "', `path_id` = '" . (int)$result['path_id'] . "', level = '" . (int)$level . "'");
				$level++;
			}
			$this->db->query("REPLACE INTO `" . DB_PREFIX . "service_category_path` SET service_category_id = '" . (int)$category['service_category_id'] . "', `path_id` = '" . (int)$category['service_category_id'] . "', level = '" . (int)$level . "'");
			$this->repairCategories($category['service_category_id']);
		}
	}
	public function getCategory($service_category_id) {
		$query = $this->db->query("SELECT DISTINCT *, (SELECT GROUP_CONCAT(cd1.name ORDER BY level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') FROM " . DB_PREFIX . "service_category_path cp LEFT JOIN " . DB_PREFIX . "service_category_description cd1 ON (cp.path_id = cd1.service_category_id AND cp.service_category_id != cp.path_id) WHERE cp.service_category_id = c.service_category_id AND cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY cp.service_category_id) AS path FROM " . DB_PREFIX . "service_category c LEFT JOIN " . DB_PREFIX . "service_category_description cd2 ON (c.service_category_id = cd2.service_category_id) WHERE c.service_category_id = '" . (int)$service_category_id . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		return $query->row;
	}
	public function getCategories($data = array()) {
		$sql = "SELECT cp.service_category_id AS service_category_id, GROUP_CONCAT(cd1.name ORDER BY cp.level SEPARATOR '&nbsp;&nbsp;&gt;&nbsp;&nbsp;') AS name, c1.parent_id, c1.sort_order, c1.noindex FROM " . DB_PREFIX . "service_category_path cp LEFT JOIN " . DB_PREFIX . "service_category c1 ON (cp.service_category_id = c1.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category c2 ON (cp.path_id = c2.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category_description cd1 ON (cp.path_id = cd1.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category_description cd2 ON (cp.service_category_id = cd2.service_category_id) WHERE cd1.language_id = '" . (int)$this->config->get('config_language_id') . "' AND cd2.language_id = '" . (int)$this->config->get('config_language_id') . "'";
		if (!empty($data['filter_name'])) {
			$sql .= " AND cd2.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
		}
		$sql .= " GROUP BY cp.service_category_id";
		$sort_data = array(
			'name',
			'sort_order',
			'noindex'
		);
		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY sort_order";
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
	public function getCategoryDescriptions($service_category_id) {
		$category_description_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category_description WHERE service_category_id = '" . (int)$service_category_id . "'");
		foreach ($query->rows as $result) {
			$category_description_data[$result['language_id']] = array(
				'name'             => $result['name'],
				'meta_title'       => $result['meta_title'],
				'meta_h1'      	   => $result['meta_h1'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword'],
				'description'      => $result['description']
			);
		}
		return $category_description_data;
	}
	public function getCategoryStores($service_category_id) {
		$category_store_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category_to_store WHERE service_category_id = '" . (int)$service_category_id . "'");
		foreach ($query->rows as $result) {
			$category_store_data[] = $result['store_id'];
		}
		return $category_store_data;
	}
	
	public function getCategorySeoUrls($service_category_id) {
		$category_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'service_category_id=" . (int)$service_category_id . "'");
		foreach ($query->rows as $result) {
			$category_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}
		return $category_seo_url_data;
	}
	public function getCategoryLayouts($service_category_id) {
		$category_layout_data = array();
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category_to_layout WHERE service_category_id = '" . (int)$service_category_id . "'");
		foreach ($query->rows as $result) {
			$category_layout_data[$result['store_id']] = $result['layout_id'];
		}
		return $category_layout_data;
	}
	public function getTotalCategories() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "service_category");
		return $query->row['total'];
	}
	
	public function getTotalCategoriesByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "service_category_to_layout WHERE layout_id = '" . (int)$layout_id . "'");
		return $query->row['total'];
	}
	
	public function getCategoriesByParentId($parent_id = 0) {
		$query = $this->db->query("SELECT *, (SELECT COUNT(parent_id) FROM " . DB_PREFIX . "service_category WHERE parent_id = c.service_category_id) AS children FROM " . DB_PREFIX . "service_category c LEFT JOIN " . DB_PREFIX . "service_category_description cd ON (c.service_category_id = cd.service_category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY c.sort_order, cd.name");
		return $query->rows;
	}
	
	public function getAllCategories() {
		$category_data = $this->cache->get('service_category.all.' . $this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'));
		if (!$category_data || !is_array($category_data)) {
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category c LEFT JOIN " . DB_PREFIX . "service_category_description cd ON (c.service_category_id = cd.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category_to_store c2s ON (c.service_category_id = c2s.service_category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' ORDER BY c.parent_id, c.sort_order, cd.name");
		$category_data = array();
		
		foreach ($query->rows as $row) {
			$category_data[$row['parent_id']][$row['service_category_id']] = $row;
		}
		$this->cache->set('service_category.all.' . $this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id'), $category_data);
		}
		
		return $category_data;
	}
}