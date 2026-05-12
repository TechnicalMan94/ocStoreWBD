<?php
class ModelDynamicSitemap extends Model {
	public function getPages() {
		$page_data = array();

		$query = $this->db->query("SELECT p.page_id FROM " . DB_PREFIX . "dynamic_page p LEFT JOIN " . DB_PREFIX . "dynamic_page_to_store p2s ON (p.page_id = p2s.page_id) WHERE p2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND p.status = '1'");

		foreach ($query->rows as $result) {
			$page_data[] = $result['page_id'];
		}

		return $page_data;
	}

	public function getCategories($parent_id = 0) {
		$category_data = array();

		$query = $this->db->query("SELECT c.category_id FROM " . DB_PREFIX . "dynamic_category c LEFT JOIN " . DB_PREFIX . "dynamic_category_to_store c2s ON (c.category_id = c2s.category_id) WHERE c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1' AND c.parent_id = '" . (int)$parent_id . "'");

		foreach ($query->rows as $category) {
			$category_data[] = array('category_id' => $category['category_id']);
			$children = $this->getCategories($category['category_id']);
			if ($children) {
				$category_data = array_merge($children, $category_data);
			}
		}

		return $category_data;
	}
}
