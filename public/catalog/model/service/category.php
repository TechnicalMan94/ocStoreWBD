<?php
// *	@source		See SOURCE.txt for source and other copyright.
// *	@license	GNU General Public License version 3; see LICENSE.txt

class ModelServiceCategory extends Model {
	public function getCategory($service_category_id) {
		return $this->getCategories((int)$service_category_id, 'by_id');
	}

	public function getCategories($id = 0, $type = 'by_parent') {
		static $data = null;

		if ($data === null) {
			$data = array();

			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category c LEFT JOIN " . DB_PREFIX . "service_category_description cd ON (c.service_category_id = cd.service_category_id) LEFT JOIN " . DB_PREFIX . "service_category_to_store c2s ON (c.service_category_id = c2s.service_category_id) WHERE cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1' ORDER BY c.parent_id, c.sort_order, cd.name");

			foreach ($query->rows as $row) {
				$data['by_id'][$row['service_category_id']] = $row;
				$data['by_parent'][$row['parent_id']][] = $row;
			}
		}

		return ((isset($data[$type]) && isset($data[$type][$id])) ? $data[$type][$id] : array());
	}

	public function getCategoriesByParentId($service_category_id) {
		$category_data = array();

		$categories = $this->getCategories((int)$service_category_id);

		foreach ($categories as $category) {
			$category_data[] = $category['service_category_id'];

			$children = $this->getCategoriesByParentId($category['service_category_id']);

			if ($children) {
				$category_data = array_merge($children, $category_data);
			}
		}

		return $category_data;
	}

	public function getCategoryLayoutId($service_category_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "service_category_to_layout WHERE service_category_id = '" . (int)$service_category_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return $this->config->get('config_layout_category');
		}
	}

	public function getTotalCategoriesByCategoryId($parent_id = 0) {
		return count($this->getCategories((int)$parent_id));
	}
}
?>