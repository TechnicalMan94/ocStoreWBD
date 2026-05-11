<?php
class ModelCatalogVariant extends Model {
	public function addVariantGroup($data) {
		$keyword = $this->getUniqueKeyword($data['keyword'] ?? '', $data['name']);

		$this->db->query("INSERT INTO `" . DB_PREFIX . "variant_group` SET name = '" . $this->db->escape($data['name']) . "', keyword = '" . $this->db->escape($keyword) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "'");

		$variant_group_id = $this->db->getLastId();

		$this->updateVariantValues($variant_group_id, $data);

		$this->cache->delete('product');

		return $variant_group_id;
	}

	public function editVariantGroup($variant_group_id, $data) {
		$keyword = $this->getUniqueKeyword($data['keyword'] ?? '', $data['name'], 0, (int)$variant_group_id);

		$this->db->query("UPDATE `" . DB_PREFIX . "variant_group` SET name = '" . $this->db->escape($data['name']) . "', keyword = '" . $this->db->escape($keyword) . "', sort_order = '" . (int)$data['sort_order'] . "', status = '" . (int)$data['status'] . "' WHERE variant_group_id = '" . (int)$variant_group_id . "'");

		$this->updateVariantValues($variant_group_id, $data);

		$this->cache->delete('product');
	}

	public function deleteVariantGroup($variant_group_id) {
		$variant_query = $this->db->query("SELECT variant_id FROM `" . DB_PREFIX . "variant` WHERE variant_group_id = '" . (int)$variant_group_id . "'");

		foreach ($variant_query->rows as $variant) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_variant` WHERE variant_id = '" . (int)$variant['variant_id'] . "'");
		}

		$this->db->query("DELETE FROM `" . DB_PREFIX . "variant` WHERE variant_group_id = '" . (int)$variant_group_id . "'");
		$this->db->query("DELETE FROM `" . DB_PREFIX . "variant_group` WHERE variant_group_id = '" . (int)$variant_group_id . "'");

		$this->cache->delete('product');
	}

	public function getVariantGroup($variant_group_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "variant_group` WHERE variant_group_id = '" . (int)$variant_group_id . "'");

		return $query->row;
	}

	public function getVariantGroups($data = array()) {
		$sql = "SELECT * FROM `" . DB_PREFIX . "variant_group`";

		$sort_data = array(
			'name',
			'keyword',
			'sort_order',
			'status'
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

	public function getTotalVariantGroups() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "variant_group`");

		return $query->row['total'];
	}

	public function getVariantValues($variant_group_id) {
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "variant` WHERE variant_group_id = '" . (int)$variant_group_id . "' ORDER BY sort_order ASC, name ASC");

		return $query->rows;
	}

	public function getVariantValue($variant_id) {
		$query = $this->db->query("SELECT v.*, vg.name AS group_name, vg.keyword AS group_keyword, vg.sort_order AS group_sort_order FROM `" . DB_PREFIX . "variant` v LEFT JOIN `" . DB_PREFIX . "variant_group` vg ON (v.variant_group_id = vg.variant_group_id) WHERE v.variant_id = '" . (int)$variant_id . "'");

		return $query->row;
	}

	public function getVariantValuesByFilter($data = array()) {
		$sql = "SELECT v.*, vg.name AS group_name, vg.keyword AS group_keyword, vg.sort_order AS group_sort_order FROM `" . DB_PREFIX . "variant` v LEFT JOIN `" . DB_PREFIX . "variant_group` vg ON (v.variant_group_id = vg.variant_group_id) WHERE 1";

		if (!empty($data['filter_name'])) {
			$filter_name = $this->db->escape((string)$data['filter_name']);

			$sql .= " AND (v.name LIKE '%" . $filter_name . "%' OR v.keyword LIKE '%" . $filter_name . "%' OR vg.name LIKE '%" . $filter_name . "%' OR vg.keyword LIKE '%" . $filter_name . "%')";
		}

		if (isset($data['filter_status']) && $data['filter_status'] !== '') {
			$sql .= " AND v.status = '" . (int)$data['filter_status'] . "' AND vg.status = '" . (int)$data['filter_status'] . "'";
		}

		$sql .= " ORDER BY vg.sort_order ASC, vg.name ASC, v.sort_order ASC, v.name ASC";

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

	public function getAllVariantGroupsWithValues($active_only = false) {
		$sql = "SELECT vg.variant_group_id, vg.name AS group_name, vg.keyword AS group_keyword, vg.sort_order AS group_sort_order, vg.status AS group_status, v.variant_id, v.name, v.keyword, v.sort_order, v.status FROM `" . DB_PREFIX . "variant_group` vg LEFT JOIN `" . DB_PREFIX . "variant` v ON (vg.variant_group_id = v.variant_group_id)";

		if ($active_only) {
			$sql .= " WHERE vg.status = '1' AND (v.status = '1' OR v.variant_id IS NULL)";
		}

		$sql .= " ORDER BY vg.sort_order ASC, vg.name ASC, v.sort_order ASC, v.name ASC";

		$query = $this->db->query($sql);

		$groups = array();

		foreach ($query->rows as $row) {
			if (!isset($groups[$row['variant_group_id']])) {
				$groups[$row['variant_group_id']] = array(
					'variant_group_id' => $row['variant_group_id'],
					'name'             => $row['group_name'],
					'keyword'          => $row['group_keyword'],
					'sort_order'       => $row['group_sort_order'],
					'status'           => $row['group_status'],
					'values'           => array()
				);
			}

			if ($row['variant_id']) {
				$groups[$row['variant_group_id']]['values'][] = array(
					'variant_id'       => $row['variant_id'],
					'variant_group_id' => $row['variant_group_id'],
					'name'             => $row['name'],
					'keyword'          => $row['keyword'],
					'sort_order'       => $row['sort_order'],
					'status'           => $row['status']
				);
			}
		}

		return $groups;
	}

	private function updateVariantValues($variant_group_id, $data) {
		$keep = array();

		if (isset($data['variant_value'])) {
			foreach ($data['variant_value'] as $variant_value) {
				if (utf8_strlen(trim($variant_value['name'])) < 1) {
					continue;
				}

				$variant_id = !empty($variant_value['variant_id']) ? (int)$variant_value['variant_id'] : 0;
				$keyword = $this->getUniqueKeyword($variant_value['keyword'] ?? '', $variant_value['name'], $variant_id);

				if ($variant_id) {
					$this->db->query("UPDATE `" . DB_PREFIX . "variant` SET variant_group_id = '" . (int)$variant_group_id . "', name = '" . $this->db->escape($variant_value['name']) . "', keyword = '" . $this->db->escape($keyword) . "', sort_order = '" . (int)$variant_value['sort_order'] . "', status = '" . (int)$variant_value['status'] . "' WHERE variant_id = '" . (int)$variant_id . "'");
				} else {
					$this->db->query("INSERT INTO `" . DB_PREFIX . "variant` SET variant_group_id = '" . (int)$variant_group_id . "', name = '" . $this->db->escape($variant_value['name']) . "', keyword = '" . $this->db->escape($keyword) . "', sort_order = '" . (int)$variant_value['sort_order'] . "', status = '" . (int)$variant_value['status'] . "'");
					$variant_id = $this->db->getLastId();
				}

				$keep[] = (int)$variant_id;
			}
		}

		$sql = "SELECT variant_id FROM `" . DB_PREFIX . "variant` WHERE variant_group_id = '" . (int)$variant_group_id . "'";

		if ($keep) {
			$sql .= " AND variant_id NOT IN (" . implode(',', $keep) . ")";
		}

		$query = $this->db->query($sql);

		foreach ($query->rows as $row) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_variant` WHERE variant_id = '" . (int)$row['variant_id'] . "'");
			$this->db->query("DELETE FROM `" . DB_PREFIX . "variant` WHERE variant_id = '" . (int)$row['variant_id'] . "'");
		}
	}

	private function getUniqueKeyword($keyword, $name, $variant_id = 0, $variant_group_id = 0) {
		$base = trim($keyword);

		if ($base === '') {
			$base = translit($name);
		}

		$base = preg_replace('/[^a-z0-9\-_]+/i', '-', utf8_strtolower($base));
		$base = trim(preg_replace('/-+/', '-', $base), '-');

		if ($base === '') {
			$base = 'variant';
		}

		$keyword = $base;
		$suffix = 2;

		while ($this->keywordExists($keyword, $variant_id, $variant_group_id)) {
			$keyword = $base . '-' . $suffix;
			$suffix++;
		}

		return $keyword;
	}

	private function keywordExists($keyword, $variant_id = 0, $variant_group_id = 0) {
		$value_query = $this->db->query("SELECT variant_id FROM `" . DB_PREFIX . "variant` WHERE keyword = '" . $this->db->escape($keyword) . "'" . ($variant_id ? " AND variant_id != '" . (int)$variant_id . "'" : "") . " LIMIT 1");

		if ($value_query->num_rows) {
			return true;
		}

		$group_query = $this->db->query("SELECT variant_group_id FROM `" . DB_PREFIX . "variant_group` WHERE keyword = '" . $this->db->escape($keyword) . "'" . ($variant_group_id ? " AND variant_group_id != '" . (int)$variant_group_id . "'" : "") . " LIMIT 1");

		return (bool)$group_query->num_rows;
	}
}
